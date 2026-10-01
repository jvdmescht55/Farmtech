/*
 * KraalTrac Pro — firmware for Kuddebestuur on farmtech.site
 * ==========================================================
 * Hardware: ESP32 · 20x4 I2C LCD · sealed 3x3 matrix keypad ·
 *           134.2 kHz FDX-B reader module on UART2 (e.g. WL-134 style)
 *
 * Flow in the kraal:
 *   1. Scan a tag       → LCD shows the animal's tag
 *   2. Punch the weight → digits on the keypad
 *   3. Hold 9 (OK)      → saved to flash, synced to farmtech.site when in Wi-Fi
 *   The LCD then shows the animal ID, kg change since last time, and any alert.
 *
 * Nothing runs on a local PC any more — no XAMPP. The device talks to
 * https://farmtech.site directly and pairs with a 6-digit code.
 *
 * Keypad (3x3 = keys 1–9). Tap = digit. HOLD (0.6 s) for the extras:
 *   hold 1 = 0          hold 2 = decimal point   hold 3 = backspace
 *   hold 4 = weigh type (Birth/Wean/Post-wean/Mature/Routine)
 *   hold 5 = sex (-/M/F)   hold 7 = cancel        hold 9 = OK / save
 * Change the pins/layout below to match your build.
 *
 * Libraries (Arduino Library Manager): ArduinoJson 7, LiquidCrystal_I2C, Keypad
 */
#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <Preferences.h>
#include <LittleFS.h>
#include <ArduinoJson.h>
#include <LiquidCrystal_I2C.h>
#include <Keypad.h>
#include <sys/time.h>

// ── Settings ─────────────────────────────────────────────────────────────
const char* WIFI_SSID  = "your-wifi";
const char* WIFI_PASS  = "your-password";
const char* SERVER     = "https://farmtech.site";
const char* MODEL      = "KraalTrac Pro";
const char* FIRMWARE   = "2.0.0";
const char* QUEUE_FILE = "/queue.csv";
const int   RFID_RX    = 16;               // reader TX -> ESP32 RX2
const int   RFID_TX    = 17;
const bool  READER_DECIMAL = false;        // true if your module already outputs the 15-digit number

LiquidCrystal_I2C lcd(0x27, 20, 4);
const byte ROWS = 3, COLS = 3;
char keys[ROWS][COLS] = {{'1','2','3'},{'4','5','6'},{'7','8','9'}};
byte rowPins[ROWS] = {13, 12, 14};
byte colPins[COLS] = {27, 26, 25};
Keypad keypad = Keypad(makeKeymap(keys), rowPins, colPins, ROWS, COLS);

const char* TYPES[] = {"wean", "birth", "post_wean", "mature", "routine"};
const char* TYPE_LABEL[] = {"WEAN", "BIRTH", "POSTWN", "MATURE", "ROUTINE"};
int typeIdx = 0;
char sexSel = '-';

Preferences prefs;
String token, serialNo, currentTag, weightBuf;
uint32_t seq = 0, lastFlush = 0;

// ── LCD helpers ──────────────────────────────────────────────────────────
void line(int row, String text) {
  text = text.substring(0, 20);
  while (text.length() < 20) text += ' ';
  lcd.setCursor(0, row); lcd.print(text);
}
size_t queued() {
  if (!LittleFS.exists(QUEUE_FILE)) return 0;
  File f = LittleFS.open(QUEUE_FILE); size_t n = 0;
  while (f.available()) if (f.read() == '\n') n++;
  f.close(); return n;
}
void idleScreen() {
  line(0, "KraalTrac Pro " + String(WiFi.status() == WL_CONNECTED ? " WiFi" : "  ---"));
  line(1, "Scan a tag...");
  line(2, "Queue: " + String(queued()));
  line(3, String("Type:") + TYPE_LABEL[typeIdx] + " Sex:" + sexSel);
}

// ── Network ──────────────────────────────────────────────────────────────
bool wifiUp() {
  if (WiFi.status() == WL_CONNECTED) return true;
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  for (int i = 0; i < 30 && WiFi.status() != WL_CONNECTED; i++) delay(200);
  return WiFi.status() == WL_CONNECTED;
}
// setInsecure() skips the certificate check — fine for a first build. For
// production, load the ESP32 core's CA bundle instead.
int post(const String& path, const String& body, String& reply, bool auth = true) {
  WiFiClientSecure c; c.setInsecure();
  HTTPClient http; http.setTimeout(10000);
  if (!http.begin(c, String(SERVER) + path)) return -1;
  http.addHeader("Content-Type", "application/json");
  if (auth && token.length()) http.addHeader("Authorization", "Bearer " + token);
  int code = http.POST(body); reply = http.getString(); http.end();
  return code;
}

// ── Pairing: shows a code, farmer types it into Kuddebestuur → Devices ──
void pair() {
  while (token.isEmpty()) {
    if (!wifiUp()) { line(0, "No Wi-Fi"); line(1, "Retrying..."); delay(4000); continue; }
    JsonDocument q; q["serial"] = serialNo; q["model"] = MODEL; q["firmware"] = FIRMWARE;
    String body, reply; serializeJson(q, body);
    if (post("/api/v1/pair", body, reply, false) != 201) { delay(4000); continue; }
    JsonDocument r; deserializeJson(r, reply);
    String code = r["code"].as<String>(), secret = r["secret"].as<String>();
    uint32_t until = millis() + r["expires_in"].as<uint32_t>() * 1000UL;
    lcd.clear();
    line(0, "Pair at farmtech.site");
    line(1, "Devices > Pair:");
    line(2, "     " + code.substring(0, 3) + " " + code.substring(3));
    line(3, "Waiting...");
    while (millis() < until) {
      delay(5000);
      JsonDocument s; s["secret"] = secret; String sb, sr; serializeJson(s, sb);
      post("/api/v1/pair/status", sb, sr, false);
      JsonDocument st; deserializeJson(st, sr);
      if (st["status"] == "paired") {
        token = st["token"].as<String>(); prefs.putString("token", token);
        line(3, "Paired! Lekker."); delay(1500); return;
      }
      if (st["status"] != "pending") break;
    }
  }
}

void syncClock() {
  WiFiClientSecure c; c.setInsecure(); HTTPClient http;
  if (!http.begin(c, String(SERVER) + "/api/v1/ping")) return;
  http.addHeader("Authorization", "Bearer " + token);
  int code = http.GET(); String reply = http.getString(); http.end();
  if (code == 401) { prefs.remove("token"); token = ""; pair(); return; }
  JsonDocument r;
  if (!deserializeJson(r, reply) && r["epoch"].is<uint32_t>()) { timeval tv = {(time_t) r["epoch"].as<uint32_t>(), 0}; settimeofday(&tv, nullptr); }
}

// ── Queue: every read is written to flash before anything else ──────────
void enqueue(const String& eid, const String& kg) {
  seq++; prefs.putUInt("seq", seq);
  File f = LittleFS.open(QUEUE_FILE, FILE_APPEND);
  f.printf("%s-%lu,%s,%s,%s,%c,%lu\n", serialNo.c_str(), (unsigned long) seq, eid.c_str(), kg.c_str(), TYPES[typeIdx], sexSel, (unsigned long) time(nullptr));
  f.close();
}

void flush() {
  if (!LittleFS.exists(QUEUE_FILE) || queued() == 0 || !wifiUp()) return;
  File f = LittleFS.open(QUEUE_FILE);
  JsonDocument q; q["compact"] = true; q["device"]["firmware"] = FIRMWARE;
  JsonArray scans = q["scans"].to<JsonArray>();
  int n = 0;
  while (f.available() && n < 40) {
    String l = f.readStringUntil('\n'); l.trim(); if (!l.length()) continue;
    String part[7]; int p = 0, from = 0;
    for (int i = 0; i <= (int) l.length() && p < 7; i++) if (i == (int) l.length() || l[i] == ',') { part[p++] = l.substring(from, i); from = i + 1; }
    JsonObject s = scans.add<JsonObject>();
    s["id"] = part[0]; s["eid"] = part[1];
    if (part[2].length()) s["weight"] = part[2].toFloat();
    s["weight_type"] = part[3];
    if (part[4] != "-") s["sex"] = part[4];
    if (part[5].toInt() > 1600000000) s["ts"] = part[5].toInt();
    n++;
  }
  String rest = f.readString(); f.close();
  String body, reply; serializeJson(q, body);
  int code = post("/api/v1/scans", body, reply);
  if (code == 201) {
    File w = LittleFS.open(QUEUE_FILE, FILE_WRITE); w.print(rest); w.close();
    JsonDocument r; deserializeJson(r, reply);
    JsonArray rs = r["r"].as<JsonArray>();
    if (rs.size()) {
      JsonObject last = rs[rs.size() - 1];
      line(0, last["a"].as<String>());
      line(1, last["w"].is<float>() ? String(last["w"].as<float>(), 1) + " kg" : "Saved");
      line(2, last["c"].is<float>() ? String(last["c"].as<float>() >= 0 ? "+" : "") + String(last["c"].as<float>(), 1) + " kg vs last" : "First weighing");
      line(3, last["t"].is<const char*>() ? "! " + last["t"].as<String>() : "All lekker");
      delay(2500);
    }
  } else if (code == 401) { prefs.remove("token"); token = ""; pair(); }
  // anything else: keep the queue, try again later
}

// ── RFID: FDX-B frames → 15-digit ISO 11784 number ───────────────────────
String readTag() {
  static String frame;
  while (Serial2.available()) {
    char c = Serial2.read();
    if (c == 0x02) { frame = ""; continue; }
    if (c != 0x03 && c != '\n' && c != '\r') { frame += c; continue; }
    String raw = frame; frame = "";
    if (READER_DECIMAL) { raw.trim(); return raw.length() >= 15 ? raw.substring(0, 15) : ""; }
    if (raw.length() < 14) return "";
    // WL-134 style: 10 hex chars of ID then 4 of country, both least-significant first
    String idHex, ccHex;
    for (int i = 9; i >= 0; i--) idHex += raw[i];
    for (int i = 13; i >= 10; i--) ccHex += raw[i];
    unsigned long long id = strtoull(idHex.c_str(), nullptr, 16);
    unsigned int cc = strtoul(ccHex.c_str(), nullptr, 16);
    char out[20]; snprintf(out, sizeof out, "%03u%012llu", cc, id);
    return String(out);
  }
  return "";
}

// ── Keypad ───────────────────────────────────────────────────────────────
void onKey(char k, bool held) {
  if (!held) { if (currentTag.length() && weightBuf.length() < 6) weightBuf += k; }
  else switch (k) {
    case '1': if (currentTag.length()) weightBuf += '0'; break;
    case '2': if (currentTag.length() && weightBuf.indexOf('.') < 0) weightBuf += '.'; break;
    case '3': if (weightBuf.length()) weightBuf.remove(weightBuf.length() - 1); break;
    case '4': typeIdx = (typeIdx + 1) % 5; break;
    case '5': sexSel = sexSel == '-' ? 'M' : (sexSel == 'M' ? 'F' : '-'); break;
    case '7': currentTag = ""; weightBuf = ""; break;
    case '9':
      if (currentTag.length()) {
        enqueue(currentTag, weightBuf);
        line(3, "Saved (" + String(queued()) + " queued)");
        currentTag = ""; weightBuf = "";
        flush();
      }
      break;
  }
  if (currentTag.length()) {
    line(0, "Tag " + currentTag.substring(currentTag.length() - 8));
    line(1, "Weight: " + weightBuf + "_ kg");
    line(2, String("Type:") + TYPE_LABEL[typeIdx] + " Sex:" + sexSel);
    line(3, "Hold 9=save 7=cancel");
  } else idleScreen();
}

void setup() {
  Serial.begin(115200);
  Serial2.begin(9600, SERIAL_8N1, RFID_RX, RFID_TX);
  lcd.init(); lcd.backlight();
  LittleFS.begin(true);
  prefs.begin("kraaltrac", false);
  serialNo = "KT-" + String((uint32_t) ESP.getEfuseMac(), HEX);
  token = prefs.getString("token", "");
  seq = prefs.getUInt("seq", 0);
  keypad.setHoldTime(600);
  line(0, "KraalTrac Pro"); line(1, serialNo); line(2, "Starting...");
  if (token.isEmpty()) pair();
  if (wifiUp()) syncClock();
  idleScreen();
}

void loop() {
  String tag = readTag();
  if (tag.length() && !currentTag.length()) { currentTag = tag; weightBuf = ""; onKey(0, false); }

  if (keypad.getKeys()) {
    for (int i = 0; i < LIST_MAX; i++) {
      Key &k = keypad.key[i];
      if (!k.stateChanged) continue;
      if (k.kstate == HOLD) { onKey(k.kchar, true); k.kchar = 0; }      // consumed as a hold
      else if (k.kstate == RELEASED && k.kchar) onKey(k.kchar, false);
    }
  }
  if (millis() - lastFlush > 20000) { lastFlush = millis(); flush(); if (!currentTag.length()) idleScreen(); }
}
