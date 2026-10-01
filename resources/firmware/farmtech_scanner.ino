/*
 * Farmtech RFID Scanner — reference firmware for ESP32 (Arduino core 3.x)
 * ----------------------------------------------------------------------
 * What it does:
 *   1. Pairs with Kuddebestuur once (shows a 6-digit code; the farmer types it
 *      into Kuddebestuur → Toestelle → "Koppel met kode"). The key is saved in
 *      flash — nothing to hard-code.
 *   2. Every tag + weight read goes into a queue file in flash FIRST, so no
 *      read is ever lost when there's no Wi-Fi in the kraal.
 *   3. Whenever Wi-Fi is up, the queue is sent in batches of up to 50 with a
 *      unique id per read — retries can never create duplicates.
 *   4. Each reply says which animal it was, the weight change since last time
 *      and the first alert — show it on your screen.
 *   5. The clock is set from the server (/api/v1/ping), so times are right
 *      even without an RTC battery.
 *
 * Libraries: ArduinoJson (v7) — everything else ships with the ESP32 core.
 * Replace readTag() and readWeight() with your reader/load-cell code.
 */
#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <Preferences.h>
#include <LittleFS.h>
#include <ArduinoJson.h>
#include <sys/time.h>

// ── Settings ─────────────────────────────────────────────────────────────
const char* WIFI_SSID   = "jou-wifi";
const char* WIFI_PASS   = "jou-wagwoord";
const char* SERVER      = "https://farmtech.site";
const char* MODEL       = "RFID Scanner V1";
const char* FIRMWARE    = "1.0.0";
const char* QUEUE_FILE  = "/queue.csv";      // one line per read: id,eid,weight,epoch
const size_t BATCH_MAX  = 50;
const uint32_t SEND_EVERY_MS = 15000;        // try to flush the queue this often

Preferences prefs;
String deviceToken;                          // from pairing, kept in NVS
String serialNo;
uint32_t seq = 0;                            // read counter, survives reboots
uint32_t lastSend = 0;

// ── Your hardware ────────────────────────────────────────────────────────
// Return the 15-digit ISO 11784 tag number when a tag is read, else "".
String readTag() { return ""; }
// Return a STABLE weight in kg (wait for the load cell to settle), or NAN.
float readWeight() { return NAN; }
// Show text on your display (OLED/LCD). Two short lines.
void show(const String& line1, const String& line2 = "") { Serial.println(line1 + " | " + line2); }

// ── HTTP helpers ─────────────────────────────────────────────────────────
// NOTE: setInsecure() skips certificate checks — fine while you build. For
// production, load the CA bundle that ships with the ESP32 core instead.
int postJson(const String& path, const String& body, String& reply, bool auth = true) {
  WiFiClientSecure client;
  client.setInsecure();
  HTTPClient http;
  http.setTimeout(10000);
  if (!http.begin(client, String(SERVER) + path)) return -1;
  http.addHeader("Content-Type", "application/json");
  if (auth && deviceToken.length()) http.addHeader("Authorization", "Bearer " + deviceToken);
  int code = http.POST(body);
  reply = http.getString();
  http.end();
  return code;
}

int getJson(const String& path, String& reply) {
  WiFiClientSecure client;
  client.setInsecure();
  HTTPClient http;
  if (!http.begin(client, String(SERVER) + path)) return -1;
  http.addHeader("Authorization", "Bearer " + deviceToken);
  int code = http.GET();
  reply = http.getString();
  http.end();
  return code;
}

bool wifiUp() {
  if (WiFi.status() == WL_CONNECTED) return true;
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  for (int i = 0; i < 40 && WiFi.status() != WL_CONNECTED; i++) delay(250);
  return WiFi.status() == WL_CONNECTED;
}

// ── Pairing (runs once, when no key is stored) ───────────────────────────
void pairDevice() {
  while (deviceToken.isEmpty()) {
    if (!wifiUp()) { show("Geen Wi-Fi", "Probeer weer…"); delay(5000); continue; }

    JsonDocument req;
    req["serial"] = serialNo; req["model"] = MODEL; req["firmware"] = FIRMWARE;
    String body, reply; serializeJson(req, body);
    if (postJson("/api/v1/pair", body, reply, false) != 201) { delay(5000); continue; }

    JsonDocument res; deserializeJson(res, reply);
    String code = res["code"].as<String>(), secret = res["secret"].as<String>();
    uint32_t until = millis() + res["expires_in"].as<uint32_t>() * 1000UL;
    show("Koppel-kode:", code.substring(0, 3) + " " + code.substring(3));

    while (millis() < until) {
      delay(res["poll_every"].as<uint32_t>() * 1000UL);
      String st, stReply; JsonDocument s;
      s["secret"] = secret; serializeJson(s, st);
      postJson("/api/v1/pair/status", st, stReply, false);
      JsonDocument r; deserializeJson(r, stReply);
      if (r["status"] == "paired") {
        deviceToken = r["token"].as<String>();
        prefs.putString("token", deviceToken);
        show("Gekoppel!", r["farm"].as<String>());
        return;
      }
      if (r["status"] != "pending") break;   // expired → start again with a new code
    }
  }
}

// ── Clock from the server ────────────────────────────────────────────────
void syncClock() {
  String reply;
  if (getJson("/api/v1/ping", reply) == 401) {         // key revoked → pair again
    prefs.remove("token"); deviceToken = ""; pairDevice(); return;
  }
  JsonDocument r;
  if (!deserializeJson(r, reply) && r["epoch"].is<uint32_t>()) {
    timeval tv = { (time_t) r["epoch"].as<uint32_t>(), 0 };
    settimeofday(&tv, nullptr);
  }
}

// ── Queue: write first, send later ───────────────────────────────────────
void enqueue(const String& eid, float kg) {
  seq++; prefs.putUInt("seq", seq);
  File f = LittleFS.open(QUEUE_FILE, FILE_APPEND);
  f.printf("%s-%lu,%s,%s,%lu\n", serialNo.c_str(), (unsigned long) seq, eid.c_str(),
           isnan(kg) ? "" : String(kg, 1).c_str(), (unsigned long) time(nullptr));
  f.close();
}

void flushQueue() {
  if (!LittleFS.exists(QUEUE_FILE) || !wifiUp()) return;

  File f = LittleFS.open(QUEUE_FILE, FILE_READ);
  JsonDocument req;
  req["compact"] = true;
  req["device"]["battery"] = 100;                      // replace with your battery %
  req["device"]["firmware"] = FIRMWARE;
  JsonArray scans = req["scans"].to<JsonArray>();
  size_t sent = 0;
  while (f.available() && sent < BATCH_MAX) {
    String line = f.readStringUntil('\n'); line.trim();
    if (!line.length()) continue;
    int a = line.indexOf(','), b = line.indexOf(',', a + 1), c = line.indexOf(',', b + 1);
    JsonObject s = scans.add<JsonObject>();
    s["id"] = line.substring(0, a);
    s["eid"] = line.substring(a + 1, b);
    if (c > b + 1) s["weight"] = line.substring(b + 1, c).toFloat();
    uint32_t ts = line.substring(c + 1).toInt();
    if (ts > 1600000000UL) s["ts"] = ts;                // only if the clock was set
    sent++;
  }
  String rest = f.readString();                         // anything beyond this batch
  f.close();
  if (!sent) return;

  String body, reply; serializeJson(req, body);
  int code = postJson("/api/v1/scans", body, reply);

  if (code == 201) {
    // Stored (duplicates count as stored) → keep only what wasn't in this batch.
    File w = LittleFS.open(QUEUE_FILE, FILE_WRITE); w.print(rest); w.close();
    JsonDocument r; deserializeJson(r, reply);
    JsonArray rs = r["r"].as<JsonArray>();
    if (rs.size()) {                                     // show the latest read's result
      JsonObject last = rs[rs.size() - 1];
      String l1 = last["a"].as<String>() + "  " + String(last["w"].as<float>(), 1) + "kg";
      String l2 = last["c"].is<float>() ? (last["c"].as<float>() >= 0 ? "+" : "") + String(last["c"].as<float>(), 1) + "kg" : "eerste weging";
      if (last["t"].is<const char*>()) l2 = "! " + last["t"].as<String>();
      show(l1, l2);
    }
  } else if (code == 401) {
    prefs.remove("token"); deviceToken = ""; pairDevice();
  }
  // Any other code (no signal, server busy): leave the queue alone, try again later.
}

// ── Main ─────────────────────────────────────────────────────────────────
void setup() {
  Serial.begin(115200);
  LittleFS.begin(true);
  prefs.begin("farmtech", false);
  serialNo = "FT1-" + String((uint32_t) ESP.getEfuseMac(), HEX);
  deviceToken = prefs.getString("token", "");
  seq = prefs.getUInt("seq", 0);

  show("Farmtech", serialNo);
  if (deviceToken.isEmpty()) pairDevice();
  if (wifiUp()) syncClock();
}

void loop() {
  String eid = readTag();
  if (eid.length()) {
    float kg = readWeight();
    enqueue(eid, kg);                                   // saved before anything else
    show(eid.substring(eid.length() - 6), isnan(kg) ? "gelees" : String(kg, 1) + " kg");
    flushQueue();                                       // instant feedback when online
  }
  if (millis() - lastSend > SEND_EVERY_MS) { lastSend = millis(); flushQueue(); }
  delay(20);
}
