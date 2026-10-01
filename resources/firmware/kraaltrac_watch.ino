/*
 * KraalTrac Watch — gate & water-point counter for Kuddebestuur
 * ==============================================================
 * Fixed 134.2 kHz FDX-B reader at a trough or gate. Every tag read is
 * saved to flash and sent to https://farmtech.site in small batches.
 * Repeated reads of the same animal within ~30 min count as one visit on
 * the server, so just send everything. Pair once with the 6-digit code
 * (Kuddebestuur → KraalTrac Watch → Water points & gates).
 *
 * Hardware: ESP32 · FDX-B reader on UART2 · optional status LED on pin 2.
 * Libraries: ArduinoJson 7.
 */
#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <Preferences.h>
#include <LittleFS.h>
#include <ArduinoJson.h>
#include <sys/time.h>

const char* WIFI_SSID = "your-wifi";
const char* WIFI_PASS = "your-password";
const char* SERVER    = "https://farmtech.site";
const char* MODEL     = "KraalTrac Watch";
const char* FIRMWARE  = "1.0.0";
const char* QUEUE     = "/visits.csv";
const int   LED = 2, RFID_RX = 16, RFID_TX = 17;
const uint32_t SEND_EVERY = 60000;                 // send at most once a minute

Preferences prefs;
String token, serialNo, lastTag;
uint32_t seq = 0, lastSend = 0, lastTagAt = 0;

bool wifiUp() {
  if (WiFi.status() == WL_CONNECTED) return true;
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  for (int i = 0; i < 30 && WiFi.status() != WL_CONNECTED; i++) delay(200);
  return WiFi.status() == WL_CONNECTED;
}
int post(const String& path, const String& body, String& reply, bool auth = true) {
  WiFiClientSecure c; c.setInsecure(); HTTPClient http;
  if (!http.begin(c, String(SERVER) + path)) return -1;
  http.addHeader("Content-Type", "application/json");
  if (auth) http.addHeader("Authorization", "Bearer " + token);
  int code = http.POST(body); reply = http.getString(); http.end(); return code;
}

void pair() {
  while (token.isEmpty()) {
    if (!wifiUp()) { delay(5000); continue; }
    JsonDocument q; q["serial"] = serialNo; q["model"] = MODEL; q["firmware"] = FIRMWARE;
    String b, r; serializeJson(q, b);
    if (post("/api/v1/pair", b, r, false) != 201) { delay(5000); continue; }
    JsonDocument d; deserializeJson(d, r);
    String code = d["code"].as<String>(), secret = d["secret"].as<String>();
    Serial.println("PAIR CODE: " + code);            // no screen? read it over USB, or blink it
    uint32_t until = millis() + d["expires_in"].as<uint32_t>() * 1000UL;
    while (millis() < until) {
      delay(5000);
      JsonDocument s; s["secret"] = secret; String sb, sr; serializeJson(s, sb);
      post("/api/v1/pair/status", sb, sr, false);
      JsonDocument st; deserializeJson(st, sr);
      if (st["status"] == "paired") { token = st["token"].as<String>(); prefs.putString("token", token); return; }
      if (st["status"] != "pending") break;
    }
  }
}

String readTag() {                                  // same FDX-B decoding as the KraalTrac Pro
  static String frame;
  while (Serial2.available()) {
    char c = Serial2.read();
    if (c == 0x02) { frame = ""; continue; }
    if (c != 0x03 && c != '\n' && c != '\r') { frame += c; continue; }
    String raw = frame; frame = "";
    if (raw.length() < 14) return "";
    String idHex, ccHex;
    for (int i = 9; i >= 0; i--) idHex += raw[i];
    for (int i = 13; i >= 10; i--) ccHex += raw[i];
    char out[20]; snprintf(out, sizeof out, "%03u%012llu", (unsigned) strtoul(ccHex.c_str(), nullptr, 16), strtoull(idHex.c_str(), nullptr, 16));
    return String(out);
  }
  return "";
}

void send() {
  if (!LittleFS.exists(QUEUE) || !wifiUp()) return;
  File f = LittleFS.open(QUEUE);
  JsonDocument q; q["compact"] = true; q["device"]["firmware"] = FIRMWARE;
  JsonArray a = q["scans"].to<JsonArray>(); int n = 0;
  while (f.available() && n < 60) {
    String l = f.readStringUntil('\n'); l.trim(); if (!l.length()) continue;
    int c1 = l.indexOf(','), c2 = l.indexOf(',', c1 + 1);
    JsonObject s = a.add<JsonObject>();
    s["id"] = l.substring(0, c1); s["eid"] = l.substring(c1 + 1, c2);
    uint32_t ts = l.substring(c2 + 1).toInt(); if (ts > 1600000000UL) s["ts"] = ts;
    n++;
  }
  String rest = f.readString(); f.close();
  if (!n) return;
  String b, r; serializeJson(q, b);
  int code = post("/api/v1/scans", b, r);
  if (code == 201) { File w = LittleFS.open(QUEUE, FILE_WRITE); w.print(rest); w.close(); }
  else if (code == 401) { prefs.remove("token"); token = ""; pair(); }
}

void setup() {
  Serial.begin(115200);
  Serial2.begin(9600, SERIAL_8N1, RFID_RX, RFID_TX);
  pinMode(LED, OUTPUT);
  LittleFS.begin(true);
  prefs.begin("ktwatch", false);
  serialNo = "KTW-" + String((uint32_t) ESP.getEfuseMac(), HEX);
  token = prefs.getString("token", ""); seq = prefs.getUInt("seq", 0);
  if (token.isEmpty()) pair();
  // clock from the server so visit times are right
  WiFiClientSecure c; c.setInsecure(); HTTPClient http;
  if (wifiUp() && http.begin(c, String(SERVER) + "/api/v1/ping")) {
    http.addHeader("Authorization", "Bearer " + token);
    if (http.GET() == 200) { JsonDocument d; deserializeJson(d, http.getString()); timeval tv = {(time_t) d["epoch"].as<uint32_t>(), 0}; settimeofday(&tv, nullptr); }
    http.end();
  }
}

void loop() {
  String tag = readTag();
  // skip the same tag read again within 20 s — the server merges visits anyway
  if (tag.length() && !(tag == lastTag && millis() - lastTagAt < 20000)) {
    lastTag = tag; lastTagAt = millis(); seq++; prefs.putUInt("seq", seq);
    File f = LittleFS.open(QUEUE, FILE_APPEND);
    f.printf("%s-%lu,%s,%lu\n", serialNo.c_str(), (unsigned long) seq, tag.c_str(), (unsigned long) time(nullptr));
    f.close();
    digitalWrite(LED, HIGH); delay(80); digitalWrite(LED, LOW);
  }
  if (millis() - lastSend > SEND_EVERY) { lastSend = millis(); send(); }
}
