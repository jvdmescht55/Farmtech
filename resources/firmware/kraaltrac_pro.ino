/* ============================================================================
 *  KraalTrac Pro — keypad + RFID + LCD scale, synced to Herd Manager on
 *  https://farmtech.site
 * ----------------------------------------------------------------------------
 *  Your sketch, moved off the PC/XAMPP setup onto the website. Pins, keypad
 *  layout, LCD, screen flow and step order are unchanged.
 *
 *  WHAT CHANGED (October 2026)
 *   1. Talks to https://farmtech.site directly. No PC, XAMPP, router port or
 *      DuckDNS needed — any Wi-Fi or phone hotspot with internet works.
 *   2. No more shared API key typed into the code. First time it starts, the
 *      LCD shows a 6-digit PAIR CODE. Open farmtech.site/pair on your phone
 *      and type it. The scale saves its own key in flash.
 *      (Idle screen → D shows device status; from there # + PIN re-pairs.)
 *   3. NEW SHEEP NUMBERS ARE BIRTHDAY CODES: 6 digits, YYMMNN.
 *        250912 = born 2025, September (09), 12th lamb that month.
 *      When you register a new sheep the scale fills in the next free number
 *      for this month — press # to accept, or C to change it. The website
 *      reads the birth month straight out of the number.
 *   4. Every queued record now carries its own reference number, so a retry
 *      after a dropped connection can never create a duplicate weighing.
 *   5. The RFID tag number is sent along with the sheep number, so the
 *      website learns which tag is on which sheep — and hands that list back
 *      to every scale on the farm (tags registered on one scale work on all).
 *   6. Weigh types are sent as birth / wean / post_wean / mature.
 *   7. Uses HTTPS. setInsecure() skips the certificate check — fine to start;
 *      later swap in the ESP32 CA bundle.
 *
 *  3.4.0: keeps a list of your animals (id, tag, sex, last weight, warnings)
 *  so a scan shows "Last 42.5kg 12/09" and the gain after weighing, even
 *  offline. Never drops old records: when memory is full it tells you to
 *  sync. D → D shows animals known, memory used and room left.
 *  3.3.0: queue, tag list and flock list are stored in files (LittleFS) so a
 *  full 300-record queue fits; records upload in batches of 40; pairing can
 *  be skipped with * (weigh offline, pair later via D → #).
 *
 *  KEPT: offline queue (300 records), auto Wi-Fi from knownNetworks[],
 *  background sync, 8 s re-scan cooldown, B = dump queue over USB,
 *  A = clear queue with PIN, tag → sheep map on the device.
 *
 *  NO WI-FI AT THE KRAAL?
 *   - Just keep weighing; it syncs by itself next time it sees Wi-Fi.
 *   - Or plug it into a laptop, press B, copy the lines between BEGIN/END
 *     and paste them into Herd Manager → Import & export → "Paste from the
 *     scale". Or run sync_from_scale.ps1 (download from the Devices page).
 *
 *  Libraries: LiquidCrystal_I2C, Keypad, ArduinoJson 7 (Library Manager)
 *  Board: ESP32 Dev Module · Partition scheme: Minimal SPIFFS (1.9MB APP with OTA/190KB SPIFFS)
 * ==========================================================================*/

#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <Keypad.h>
#include <WiFi.h>
#include <WiFiMulti.h>
#include <WebServer.h>
#include <DNSServer.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <Preferences.h>
#include <LittleFS.h>
#include <ArduinoJson.h>
#include <sys/time.h>
#include <time.h>

// --- LCD & STORAGE SETUP ---
LiquidCrystal_I2C lcd(0x27, 20, 4);
Preferences prefs;

// --- NETWORK CONFIG ---
// You don't have to edit these any more: on first start the scale opens its own
// Wi-Fi ("KraalTrac-xxxx") and you choose your network from your phone. Networks
// you add there are saved on the device (up to 5). You may still list some here.
struct WifiNet { const char* ssid; const char* password; };
WifiNet knownNetworks[] = {
  { "YOUR_FARM_WIFI",      "YOUR_FARM_WIFI_PASSWORD" },
  { "YOUR_PHONE_HOTSPOT",  "YOUR_HOTSPOT_PASSWORD" },
};
WiFiMulti wifiMulti;

const char* SERVER    = "https://farmtech.site";
const char* MODEL     = "KraalTrac Pro";
const char* FIRMWARE  = "3.4.0";
// Optional: paste a device key from Herd Manager → Devices → "Add a device by
// hand" here to skip pairing. Leave empty to pair with a 6-digit code.
const char* DEVICE_KEY = "";

const char* CLEAR_PIN = "1379";   // PIN to clear the queue / re-pair on the keypad
const char* ANIMAL    = "Sheep";  // word on the screens: "Sheep", "Cattle", "Goat" or "Animal"

// --- LIMITS / TIMING ---
const int MAX_ID_LEN            = 10;     // existing numbers can be 4–10 digits (2415, 21270, 197013…)
const int NEW_SHEEP_ID_LEN      = 6;      // new sheep: birthday code YYMMNN
const int PIN_LEN               = 4;
const int MAX_WEIGHT_LEN        = 6;
const unsigned long RESCAN_COOLDOWN_MS = 8000;
const unsigned long SYNC_INTERVAL_MS   = 60000UL;
const unsigned long FLOCK_REFRESH_MS   = 6UL * 60 * 60 * 1000UL;
const unsigned long WIFI_RETRY_MS      = 30000UL;   // only retried while the scale is idle
const int SYNC_BATCH                = 40;       // records per upload
const int RECORD_BYTES           = 80;       // average size of one queued record
const size_t STORAGE_RESERVE     = 12288;    // always keep this free (animal list updates, safety)

// --- KEYPAD SETUP (unchanged) ---
const byte ROWS = 4;
const byte COLS = 4;
char keys[ROWS][COLS] = {
  {'1','2','3','A'},
  {'4','5','6','B'},
  {'7','8','9','C'},
  {'*','0','#','D'}
};
byte rowPins[ROWS] = {26, 25, 33, 32};
byte colPins[COLS] = {13, 12, 14, 27};
Keypad keypad = Keypad(makeKeymap(keys), rowPins, colPins, ROWS, COLS);

// --- OFFLINE CACHE STORAGE ---
String localFlockCache = "";   // "250901,250902,2415,"
String deviceKey = "";
String serialNo = "";
uint32_t refSeq = 0;

// --- STATE MACHINE ---
enum State {
  ENTER_ID,
  UNKNOWN_TAG_PROMPT,
  ENTER_NEW_SHEEP_ID,
  CONFIRM_CLEAR_PIN,
  SELECT_WEIGHT_TYPE,
  SELECT_GENDER,
  ENTER_SIRE_ID,
  ENTER_DAM_ID,
  ENTER_WEIGHT,
  SHOW_SUMMARY,
  DEVICE_STATUS,
  CONFIRM_REPAIR_PIN
};
State state = ENTER_ID;

String currentID = "";
String currentTag = "";      // RFID tag of the sheep being weighed (sent to the website)
String pendingRawTag = "";
String newSheepIdBuf = "";
String currentWeightType = "";   // birth / wean / post_wean / mature
String currentGender = "";
String currentSireID = "";
String currentDamID = "";
String currentWeightValue = "";
String scannedRawTag = "";

String lastSavedID = "";
unsigned long lastSaveMillis = 0;
unsigned long lastWifiAttempt = 0;
unsigned long lastSyncAttempt = 0;
unsigned long lastFlockRefresh = 0;
bool wifiWasConnected = false;
bool pairingSkipped = false;   // * on the pairing screen: weigh offline, pair later (D → #)
String tagMapCache = "";       // "982000123456789=250912\n…" (also in /tags.txt)
String pinBuf = "";

void syncOfflineLogs(int maxBatches = 2);   // defined further down

// ---------------------------------------------------------------------------
// Small helpers
// ---------------------------------------------------------------------------
void updateDisplay(String r0, String r1, String r2, String r3) {
  lcd.clear();
  lcd.setCursor(0, 0); lcd.print(r0.substring(0, 20));
  lcd.setCursor(0, 1); lcd.print(r1.substring(0, 20));
  lcd.setCursor(0, 2); lcd.print(r2.substring(0, 20));
  lcd.setCursor(0, 3); lcd.print(r3.substring(0, 20));
}

String typeLabel(String t) {
  if (t == "birth") return "Birth";
  if (t == "wean") return "Wean";
  if (t == "post_wean") return "PWean";
  if (t == "mature") return "Mature";
  return "";
}

bool clockIsSet() { return time(nullptr) > 1700000000; }

void showWeightTypeMenu() { updateDisplay("Select Weight Type:", "A:Birth  B:Wean", "C:Post-W D:Mature", "ID: " + currentID); }
void showWeightEntry()    { updateDisplay("Enter Weight (kg):", "Wt: " + currentWeightValue + (currentWeightValue.length() ? " kg" : ""), "A:. C:Del #:Save", "*: Back"); }

// ---------------------------------------------------------------------------
// HTTPS
// ---------------------------------------------------------------------------
int httpGet(String path, String& reply) {
  WiFiClientSecure c; c.setInsecure();
  HTTPClient http;
  if (!http.begin(c, String(SERVER) + path)) return -1;
  if (deviceKey.length()) http.addHeader("Authorization", "Bearer " + deviceKey);
  http.setConnectTimeout(4000); http.setTimeout(8000);
  int code = http.GET();
  reply = code > 0 ? http.getString() : "";
  http.end();
  return code;
}

int httpPost(String path, String body, String contentType, String& reply, bool auth = true) {
  WiFiClientSecure c; c.setInsecure();
  HTTPClient http;
  if (!http.begin(c, String(SERVER) + path)) return -1;
  http.addHeader("Content-Type", contentType);
  if (auth && deviceKey.length()) http.addHeader("Authorization", "Bearer " + deviceKey);
  http.setConnectTimeout(4000); http.setTimeout(8000);
  int code = http.POST(body);
  reply = code > 0 ? http.getString() : "";
  http.end();
  return code;
}

String urlEncode(String s) {
  String o; char b[4];
  for (unsigned int i = 0; i < s.length(); i++) {
    char c = s[i];
    if (isalnum(c) || c == '-' || c == '_' || c == '.') o += c;
    else { snprintf(b, sizeof b, "%%%02X", (unsigned char) c); o += b; }
  }
  return o;
}

// ---------------------------------------------------------------------------
// Storage: the queue, tag list and flock list live in files (LittleFS), not in
// Preferences — Preferences can't hold a string over ~4 000 bytes, which a
// full queue (300 records) or a big tag list easily is. Small settings stay in
// Preferences. Files are replaced safely: write .tmp, then rename.
// ---------------------------------------------------------------------------
const char* Q_FILE = "/queue.txt";
const char* TAG_FILE = "/tags.txt";
const char* FLOCK_FILE = "/flock.txt";
const char* INFO_FILE = "/animals.txt";   // id|tag|sex|last kg|dd/mm|warning — last line for an id wins

// What the scale knows about the animal on screen (from /animals.txt)
String curSex = "", curLastKg = "", curLastDate = "", curWarn = "";

String readFile(const char* path) {
  File f = LittleFS.open(path, "r");
  if (!f) return "";
  String s = f.readString();
  f.close();
  return s;
}

bool writeFile(const char* path, const String &data) {
  String tmp = String(path) + ".tmp";
  File f = LittleFS.open(tmp, "w");
  if (!f) return false;
  size_t n = f.print(data);
  f.close();
  if (n != data.length()) { LittleFS.remove(tmp); return false; }
  LittleFS.remove(path);
  return LittleFS.rename(tmp, path);
}

bool appendFile(const char* path, const String &data) {
  File f = LittleFS.open(path, "a");
  if (!f) return false;
  size_t n = f.print(data);
  f.close();
  return n == data.length();
}

/** One-time move from the old Preferences keys into files. */
void migrateStorage() {
  const char* keys[] = { "queue", "tagmap", "flock" };
  const char* files[] = { Q_FILE, TAG_FILE, FLOCK_FILE };
  for (int i = 0; i < 3; i++) {
    if (!prefs.isKey(keys[i])) continue;
    String v = prefs.getString(keys[i], "");
    if (v.length()) appendFile(files[i], v);
    prefs.remove(keys[i]);
  }
}

size_t storageFree() {
  size_t total = LittleFS.totalBytes(), used = LittleFS.usedBytes();
  return used >= total ? 0 : total - used;
}

/** Roughly how many more weighings fit before it must sync. */
int recordsRoom() {
  size_t f = storageFree();
  return f <= STORAGE_RESERVE ? 0 : (int) ((f - STORAGE_RESERVE) / RECORD_BYTES);
}

int storagePct() {
  size_t total = LittleFS.totalBytes();
  return total ? (int) (LittleFS.usedBytes() * 100 / total) : 100;
}

int countLines(const char* path) {
  File f = LittleFS.open(path, "r");
  if (!f) return 0;
  int c = 0;
  while (f.available()) if (f.read() == '\n') c++;
  f.close();
  return c;
}

/** Looks the animal up in /animals.txt and fills cur* (the last line for that id wins). */
void loadAnimalInfo(const String &id) {
  curSex = ""; curLastKg = ""; curLastDate = ""; curWarn = "";
  File f = LittleFS.open(INFO_FILE, "r");
  if (!f) return;
  String prefix = id + "|";
  while (f.available()) {
    String line = f.readStringUntil('\n');
    if (!line.startsWith(prefix)) continue;
    String p[6]; int n = 0, from = 0;
    for (int i = 0; i <= (int) line.length() && n < 6; i++) {
      if (i == (int) line.length() || line[i] == '|') { p[n++] = line.substring(from, i); from = i + 1; }
    }
    if (p[2].length()) curSex = p[2];
    if (p[3].length()) { curLastKg = p[3]; curLastDate = p[4]; }
    curWarn = p[5];
  }
  f.close();
}

/** Remember today's weight on the scale, so the next scan shows it even before syncing. */
void noteWeighed(const String &id, const String &tag, const String &sex, const String &kg) {
  String date = "";
  if (clockIsSet()) { time_t now = time(nullptr); struct tm t; localtime_r(&now, &t); char d[6]; snprintf(d, sizeof d, "%02d/%02d", t.tm_mday, t.tm_mon + 1); date = d; }
  appendFile(INFO_FILE, id + "|" + tag + "|" + sex + "|" + kg + "|" + date + "|" + curWarn + "\n");
}

/** Download the farm's animal list (id, tag, sex, last weight, warnings) straight into flash. */
void downloadAnimalInfo() {
  if (deviceKey.length() == 0) return;
  WiFiClientSecure c; c.setInsecure();
  HTTPClient http;
  if (!http.begin(c, String(SERVER) + "/api/v1/flock?format=info")) return;
  http.addHeader("Authorization", "Bearer " + deviceKey);
  http.setConnectTimeout(4000); http.setTimeout(15000);
  if (http.GET() == 200) {
    String tmp = String(INFO_FILE) + ".tmp";
    File f = LittleFS.open(tmp, "w");
    if (f) {
      int n = http.writeToStream(&f);
      f.close();
      if (n > 0) { LittleFS.remove(INFO_FILE); LittleFS.rename(tmp, INFO_FILE); }
      else LittleFS.remove(tmp);
    }
  }
  http.end();
}

/** The animal's card: who it is, what it weighed last, any warning — then the weigh-type menu. */
void showAnimalCard(const String &headline) {
  loadAnimalInfo(currentID);
  String l1 = curWarn.length() ? "!" + curWarn
            : (curLastKg.length() ? "Last " + curLastKg + "kg " + curLastDate : "No weight yet");
  updateDisplay(headline + (curSex.length() ? " " + curSex : ""), l1, "A:Birth  B:Wean", "C:Post-W D:Mature");
}

// ---------------------------------------------------------------------------
// Wi-Fi setup from your phone (no code editing): the scale opens an open Wi-Fi
// "KraalTrac-xxxx", your phone shows a page to pick your network, it's saved and
// the scale restarts. Saved networks live in flash (keys w0s/w0p … w4s/w4p).
// ---------------------------------------------------------------------------
int loadNetworks() {
  int n = 0;
  for (int i = 0; i < 5; i++) {
    String ss = prefs.getString(("w" + String(i) + "s").c_str(), "");
    if (ss.length()) { wifiMulti.addAP(ss.c_str(), prefs.getString(("w" + String(i) + "p").c_str(), "").c_str()); n++; }
  }
  for (auto &k : knownNetworks) {
    if (strncmp(k.ssid, "YOUR_", 5) != 0 && strlen(k.ssid)) { wifiMulti.addAP(k.ssid, k.password); n++; }
  }
  return n;
}

void saveNetwork(const String &ssid, const String &pass) {
  String ss[5], pp[5]; int n = 0;
  ss[n] = ssid; pp[n++] = pass;                      // newest first
  for (int i = 0; i < 5 && n < 5; i++) {
    String s2 = prefs.getString(("w" + String(i) + "s").c_str(), "");
    if (s2.length() && s2 != ssid) { ss[n] = s2; pp[n++] = prefs.getString(("w" + String(i) + "p").c_str(), ""); }
  }
  for (int i = 0; i < 5; i++) {
    prefs.putString(("w" + String(i) + "s").c_str(), i < n ? ss[i] : "");
    prefs.putString(("w" + String(i) + "p").c_str(), i < n ? pp[i] : "");
  }
}

String htmlEscape(const String &in) {
  String o; for (char c : in) { if (c == '<') o += "&lt;"; else if (c == '>') o += "&gt;"; else if (c == '&') o += "&amp;"; else if (c == '"') o += "&quot;"; else o += c; }
  return o;
}

void wifiSetupPortal() {
  String ap = "KraalTrac-" + serialNo.substring(serialNo.length() - 4);
  WiFi.disconnect(true);
  WiFi.mode(WIFI_AP_STA);
  WiFi.softAP(ap.c_str());
  delay(300);
  updateDisplay("Wi-Fi setup. On your", "phone join Wi-Fi:", ap, "*: Cancel");
  int found = WiFi.scanNetworks();

  DNSServer dns; dns.start(53, "*", WiFi.softAPIP());
  WebServer web(80);
  bool saved = false;

  auto page = [&]() {
    String opts;
    for (int i = 0; i < found && i < 20; i++) {
      String ss = htmlEscape(WiFi.SSID(i));
      if (ss.length() && opts.indexOf(">" + ss + "<") < 0) opts += "<option>" + ss + "</option>";
    }
    String h = F("<!doctype html><html><head><meta name=viewport content='width=device-width,initial-scale=1'><title>KraalTrac Wi-Fi</title>"
      "<style>body{font-family:system-ui,sans-serif;background:#F4F1EA;color:#15140F;margin:0;padding:28px}h1{font-family:Georgia,serif;font-weight:400;font-size:34px;margin:0 0 6px}"
      "p{color:#6b665b}label{display:block;font-size:14px;margin:18px 0 6px}select,input{width:100%;box-sizing:border-box;height:48px;border:1px solid #d9d2c3;border-radius:12px;padding:0 14px;font-size:16px;background:#fff}"
      "button{margin-top:22px;width:100%;height:52px;border:0;border-radius:99px;background:#15140F;color:#F4F1EA;font-size:16px}small{color:#8b8577}</style></head><body>"
      "<h1>Connect your KraalTrac</h1><p>Choose the Wi-Fi it should use: farm Wi-Fi or your phone's hotspot.</p>"
      "<form method=post action=/save><label>Network</label><select name=s>");
    h += opts;
    h += F("</select><label>Or type the name</label><input name=m placeholder='e.g. My iPhone'><label>Password</label><input name=p type=password>"
      "<button>Save &amp; connect</button></form><p><small>Saved on the scale only. You can add up to 5 networks.</small></p></body></html>");
    web.send(200, "text/html", h);
  };
  web.on("/", page);
  web.on("/save", HTTP_POST, [&]() {
    String ss = web.arg("m").length() ? web.arg("m") : web.arg("s");
    if (!ss.length()) { page(); return; }
    saveNetwork(ss, web.arg("p"));
    web.send(200, "text/html", "<!doctype html><meta name=viewport content='width=device-width,initial-scale=1'><body style='font-family:system-ui;padding:28px;background:#F4F1EA'><h1 style='font-family:Georgia;font-weight:400'>Lekker, saved.</h1><p>The scale restarts and connects to <b>" + htmlEscape(ss) + "</b>. You can close this page.</p>");
    saved = true;
  });
  web.onNotFound([&]() { web.sendHeader("Location", "http://192.168.4.1/", true); web.send(302, "text/plain", ""); });
  web.begin();

  unsigned long until = millis() + 10UL * 60UL * 1000UL;   // 10 minutes
  while (!saved && millis() < until) {
    dns.processNextRequest();
    web.handleClient();
    if (keypad.getKey() == '*') break;
    delay(2);
  }
  if (saved) {
    updateDisplay("Wi-Fi saved!", "Restarting...", "", "");
    delay(1500);
    ESP.restart();
  }
  web.stop(); dns.stop(); WiFi.softAPdisconnect(true);
  WiFi.mode(WIFI_STA);
  resetScreen();
}

// ---------------------------------------------------------------------------
// Pairing — shows a 6-digit code, farmer types it into Herd Manager
// ---------------------------------------------------------------------------
/** Waits up to ms while still reading the keypad; true if * was pressed. */
bool waitOrCancel(unsigned long ms) {
  unsigned long until = millis() + ms;
  while (millis() < until) { if (keypad.getKey() == '*') return true; delay(20); }
  return false;
}

void pairDevice() {
  pairingSkipped = false;
  while (deviceKey.length() == 0) {
    if (WiFi.status() != WL_CONNECTED) {
      updateDisplay("KraalTrac Pro", "Needs Wi-Fi once", "to pair. Waiting...", "*: Weigh offline");
      wifiMulti.run(3000);
      if (waitOrCancel(2000)) { pairingSkipped = true; return; }
      continue;
    }
    JsonDocument q; q["serial"] = serialNo; q["model"] = MODEL; q["firmware"] = FIRMWARE;
    String body, reply; serializeJson(q, body);
    if (httpPost("/api/v1/pair", body, "application/json", reply, false) != 201) {
      updateDisplay("KraalTrac Pro", "Can't reach website", "Trying again...", "*: Weigh offline");
      if (waitOrCancel(4000)) { pairingSkipped = true; return; }
      continue;
    }
    JsonDocument r; deserializeJson(r, reply);
    String code = r["code"].as<String>(), secret = r["secret"].as<String>();
    unsigned long until = millis() + r["expires_in"].as<unsigned long>() * 1000UL;
    updateDisplay("Go to farmtech.site", "/pair and type:", "      " + code.substring(0, 3) + " " + code.substring(3), "*: Weigh offline");
    while (millis() < until) {
      if (waitOrCancel(4000)) { pairingSkipped = true; return; }
      JsonDocument s; s["secret"] = secret; String sb, sr; serializeJson(s, sb);
      httpPost("/api/v1/pair/status", sb, "application/json", sr, false);
      JsonDocument st; deserializeJson(st, sr);
      if (st["status"] == "paired") {
        deviceKey = st["token"].as<String>();
        prefs.putString("devkey", deviceKey);
        updateDisplay("Paired! Lekker.", st["farm"].as<String>(), "", "");
        delay(2000);
        return;
      }
      if (st["status"] != "pending") break;   // expired -> new code
    }
  }
}

void syncClock() {
  String reply;
  int code = httpGet("/api/v1/ping", reply);
  if (code == 401) { deviceKey = ""; prefs.remove("devkey"); pairDevice(); return; }   // key was revoked on the website
  JsonDocument r;
  if (code == 200 && !deserializeJson(r, reply) && r["epoch"].is<uint32_t>()) {
    timeval tv = { (time_t) r["epoch"].as<uint32_t>(), 0 };
    settimeofday(&tv, nullptr);
    setenv("TZ", "SAST-2", 1); tzset();
  }
}

// ---------------------------------------------------------------------------
// Birthday numbers: YYMMNN
// ---------------------------------------------------------------------------
bool isBirthdayId(String id) {
  if (id.length() != 6) return false;
  for (unsigned int i = 0; i < 6; i++) if (!isDigit(id[i])) return false;
  int mm = id.substring(2, 4).toInt();
  return mm >= 1 && mm <= 12;
}

/** Next free number for this month, from the flock list + what's queued here. */
String suggestBirthdayId() {
  if (!clockIsSet()) return "";
  time_t now = time(nullptr); struct tm t; localtime_r(&now, &t);
  char ym[5]; snprintf(ym, sizeof ym, "%02d%02d", (t.tm_year + 1900) % 100, t.tm_mon + 1);
  String prefix = String(ym);
  int maxN = 0;
  String hay = "," + localFlockCache;
  int pos = 0;
  while ((pos = hay.indexOf("," + prefix, pos)) >= 0) {
    String cand = hay.substring(pos + 1, pos + 7);
    if (isBirthdayId(cand) && (pos + 7 >= (int) hay.length() || hay[pos + 7] == ',')) maxN = max(maxN, (int) cand.substring(4).toInt());
    pos += 1;
  }
  char out[7]; snprintf(out, sizeof out, "%s%02d", ym, min(maxN + 1, 99));
  return String(out);
}

// ---------------------------------------------------------------------------
// Setup / loop
// ---------------------------------------------------------------------------
void setup() {
  Serial.begin(115200);
  Serial2.begin(9600, SERIAL_8N1, 16, 17); // RFID reader on GPIO 16 (RX) / 17 (TX)
  Wire.begin();
  lcd.init();
  lcd.backlight();

  prefs.begin("livestock", false);
  if (!LittleFS.begin(true)) updateDisplay("Storage error!", "Records can't be", "saved. Re-flash the", "scale or call us.");
  migrateOldQueueFormat();
  migrateStorage();
  localFlockCache = readFile(FLOCK_FILE);
  tagMapCache = readFile(TAG_FILE);
  refSeq = prefs.getUInt("refseq", 0);
  serialNo = "KT-" + String((uint32_t) ESP.getEfuseMac(), HEX);
  deviceKey = prefs.getString("devkey", "");
  if (deviceKey.length() == 0 && strlen(DEVICE_KEY) > 0) deviceKey = DEVICE_KEY;

  if (queueCount() > 0) {
    Serial.println(String(queueCount()) + " record(s) still waiting to sync.");
    dumpQueueToSerial();
  }

  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  int nets = loadNetworks();
  if (nets == 0) wifiSetupPortal();          // brand new: choose Wi-Fi on your phone (restarts when saved)
  updateDisplay("KraalTrac Pro", "Connecting...", "", serialNo);
  unsigned long t0 = millis();
  while (wifiMulti.run() != WL_CONNECTED && millis() - t0 < 10000) { delay(200); }
  if (WiFi.status() != WL_CONNECTED && deviceKey.length() == 0) {
    // Never paired and no Wi-Fi in range: offer the phone setup instead of waiting forever.
    updateDisplay("No Wi-Fi found.", "A: Set up Wi-Fi", "*: Carry on offline", "");
    unsigned long w = millis();
    while (millis() - w < 20000) {
      char k = keypad.getKey();
      if (k == 'A') wifiSetupPortal();
      if (k == '*') break;
      delay(20);
    }
  }

  if (WiFi.status() == WL_CONNECTED) {
    if (deviceKey.length() == 0) pairDevice();
    syncClock();
    syncOfflineLogs(8);
    downloadFlockCache();
  }
  resetScreen();
}

void loop() {
  maintainWiFi();
  periodicSync();
  periodicFlockRefresh();
  handleSerialCommands();

  switch (state) {
    case ENTER_ID:
      // 1. Automatic RFID scan
      if (Serial2.available()) {
        scannedRawTag = "";
        delay(50);
        while (Serial2.available()) {
          char c = Serial2.read();
          if (isDigit(c)) scannedRawTag += c;
        }
        if (scannedRawTag.length() >= 4) {
          String resolvedId = lookupTagMap(scannedRawTag);
          if (resolvedId.length() > 0) {
            bool justSaved = (resolvedId == lastSavedID) && (millis() - lastSaveMillis < RESCAN_COOLDOWN_MS);
            if (!justSaved) {
              currentID = resolvedId;
              currentTag = scannedRawTag;
              state = SELECT_WEIGHT_TYPE;
              showAnimalCard(String(ANIMAL) + ": " + currentID);
            }
          } else {
            pendingRawTag = scannedRawTag;
            currentID = "";
            state = UNKNOWN_TAG_PROMPT;
            updateDisplay("New tag scanned", "Not known: " + String(ANIMAL), "A: Give it a number", "*: Cancel / Retry");
          }
        }
      }

      // 2. Manual keypad entry
      {
        char key = keypad.getKey();
        if (!key) break;

        if (key >= '0' && key <= '9') {
          if (currentID.length() < MAX_ID_LEN) {
            currentID += key;
            updateDisplay("Enter Animal ID:", "ID: " + currentID, "Press '#' when done", "*: Clear");
          }
        }
        else if (key == 'C') {
          if (currentID.length() > 0) {
            currentID.remove(currentID.length() - 1);
            updateDisplay("Enter Animal ID:", "ID: " + currentID, "Press '#' when done", "*: Clear");
          }
        }
        else if (key == '#') {
          if (currentID.length() > 0) {
            currentTag = "";
            if (validateIDOffline(currentID)) {
              state = SELECT_WEIGHT_TYPE;
              showAnimalCard(String(ANIMAL) + ": " + currentID);
            } else {
              pendingRawTag = "";
              state = UNKNOWN_TAG_PROMPT;
              updateDisplay("Unknown ID:", currentID, "A: Register New", "*: Cancel / Retry");
            }
          }
        }
        else if (key == '*') { currentID = ""; resetScreen(); }
        else if (key == 'B') {
          dumpQueueToSerial();
          updateDisplay("Queue sent to USB", "(115200 baud).", "Paste it into Herd", "Manager > Import");
          delay(2500);
          resetScreen();
        }
        else if (key == 'A') {
          if (queueCount() == 0) {
            updateDisplay("Nothing queued.", "All caught up.", "", "");
            delay(1500);
            resetScreen();
          } else {
            pinBuf = "";
            state = CONFIRM_CLEAR_PIN;
            updateDisplay("Enter PIN to clear", "the queue:", "PIN: ", "#: OK  *: Cancel");
          }
        }
        else if (key == 'D') {
          state = DEVICE_STATUS;
          updateDisplay(WiFi.status() == WL_CONNECTED ? "Wi-Fi: " + WiFi.SSID() : "Wi-Fi: not connected",
                        deviceKey.length() ? "Paired: yes" : "Paired: NO (#)",
                        "Queued " + String(queueCount()) + " Room " + String(recordsRoom()),
                        "#:Pair A:WiFi D:More");
        }
      }
      break;

    case DEVICE_STATUS:
      {
        char key = keypad.getKey();
        if (!key) break;
        if (key == '*') { state = ENTER_ID; resetScreen(); }
        else if (key == 'D') {
          updateDisplay("Animals known: " + String(countLines(INFO_FILE) > 0 ? countLines(INFO_FILE) : countLines(TAG_FILE)),
                        "Memory used: " + String(storagePct()) + "%",
                        "Room for ~" + String(recordsRoom()) + " recs",
                        "fw " + String(FIRMWARE) + "  *:Back");
        }
        else if (key == 'A') { wifiSetupPortal(); }
        else if (key == '#') { pinBuf = ""; state = CONFIRM_REPAIR_PIN; updateDisplay("PIN to re-pair:", "", "PIN: ", "#: OK  *: Cancel"); }
      }
      break;

    case CONFIRM_REPAIR_PIN:
    case CONFIRM_CLEAR_PIN:
      {
        char key = keypad.getKey();
        if (!key) break;
        bool repair = (state == CONFIRM_REPAIR_PIN);

        if (key >= '0' && key <= '9') {
          if (pinBuf.length() < PIN_LEN) pinBuf += key;
        }
        else if (key == 'C') {
          if (pinBuf.length() > 0) pinBuf.remove(pinBuf.length() - 1);
        }
        else if (key == '*') { pinBuf = ""; state = ENTER_ID; resetScreen(); break; }
        else if (key == '#') {
          if (pinBuf == CLEAR_PIN) {
            if (repair) {
              deviceKey = ""; prefs.remove("devkey");
              pairDevice();
            } else {
              writeFile(Q_FILE, "");
              updateDisplay("Queue cleared.", "Only do this AFTER", "the import worked", "on the website!");
              delay(2000);
            }
          } else {
            updateDisplay("Wrong PIN.", "Nothing changed.", "", "");
            delay(1500);
          }
          pinBuf = ""; state = ENTER_ID; resetScreen();
          break;
        }
        String stars = "";
        for (unsigned int i = 0; i < pinBuf.length(); i++) stars += "*";
        updateDisplay(repair ? "PIN to re-pair:" : "Enter PIN to clear", repair ? "" : "the queue:", "PIN: " + stars, "#: OK  *: Cancel");
      }
      break;

    case UNKNOWN_TAG_PROMPT:
      {
        char key = keypad.getKey();
        if (!key) break;

        if (key == 'A') {
          // typed by hand already? keep it. Otherwise suggest the next birthday number.
          newSheepIdBuf = (currentID.length() > 0 && currentID.length() <= NEW_SHEEP_ID_LEN) ? currentID : suggestBirthdayId();
          state = ENTER_NEW_SHEEP_ID;
          updateDisplay("New " + String(ANIMAL) + " number:", "ID: " + newSheepIdBuf, "YYMMNN e.g. 250912", "#: OK C:Del *:Cancel");
        }
        else if (key == '*') {
          currentID = ""; pendingRawTag = ""; state = ENTER_ID; resetScreen();
        }
      }
      break;

    case ENTER_NEW_SHEEP_ID:
      {
        char key = keypad.getKey();
        if (!key) break;

        if (key >= '0' && key <= '9') {
          if (newSheepIdBuf.length() < NEW_SHEEP_ID_LEN) newSheepIdBuf += key;
        }
        else if (key == 'C') {
          if (newSheepIdBuf.length() > 0) newSheepIdBuf.remove(newSheepIdBuf.length() - 1);
        }
        else if (key == '*') {
          newSheepIdBuf = ""; pendingRawTag = ""; currentID = ""; state = ENTER_ID; resetScreen();
          break;
        }
        else if (key == '#') {
          if (isBirthdayId(newSheepIdBuf)) {
            if (pendingRawTag.length() > 0) saveTagMap(pendingRawTag, newSheepIdBuf);
            currentTag = pendingRawTag;
            pendingRawTag = "";
            currentID = newSheepIdBuf;
            newSheepIdBuf = "";
            if (validateIDOffline(currentID)) {
              state = SELECT_WEIGHT_TYPE;
              showAnimalCard("Linked " + currentID);
            } else {
              curSex = ""; curLastKg = ""; curLastDate = ""; curWarn = "";
              state = SELECT_GENDER;
              updateDisplay("New " + String(ANIMAL) + " " + currentID, "Select Gender:", "A: Male", "B: Female");
            }
          } else {
            updateDisplay("Needs 6 digits:", "YY MM NN", "e.g. 250912 = 2025,", "Sep, 12th. C:Del");
            delay(2200);
          }
          if (state != ENTER_NEW_SHEEP_ID) break;
        }
        updateDisplay("New " + String(ANIMAL) + " number:", "ID: " + newSheepIdBuf, "YYMMNN e.g. 250912", "#: OK C:Del *:Cancel");
      }
      break;

    case SELECT_WEIGHT_TYPE:
      {
        char key = keypad.getKey();
        if (!key) break;

        if (key == 'A') {
          currentWeightType = "birth";
          state = SELECT_GENDER;
          updateDisplay("Select Gender:", "A: Male", "B: Female", "*: Back");
        }
        else if (key == 'B') { currentWeightType = "wean";      state = ENTER_WEIGHT; showWeightEntry(); }
        else if (key == 'C') { currentWeightType = "post_wean"; state = ENTER_WEIGHT; showWeightEntry(); }
        else if (key == 'D') { currentWeightType = "mature";    state = ENTER_WEIGHT; showWeightEntry(); }
        else if (key == '*') { currentID = ""; currentTag = ""; state = ENTER_ID; resetScreen(); }
      }
      break;

    case SELECT_GENDER:
      {
        char key = keypad.getKey();
        if (!key) break;

        if (key == 'A' || key == 'B') {
          currentGender = (key == 'A') ? "M" : "F";
          state = ENTER_SIRE_ID;
          updateDisplay("Enter Father ID", "Sire ID: ", "D: Skip / #: Done", "*: Back");
        }
        else if (key == '*') { state = SELECT_WEIGHT_TYPE; showWeightTypeMenu(); }
      }
      break;

    case ENTER_SIRE_ID:
    case ENTER_DAM_ID:
      {
        char key = keypad.getKey();
        if (!key) break;
        bool sire = (state == ENTER_SIRE_ID);
        String &buf = sire ? currentSireID : currentDamID;

        if (key >= '0' && key <= '9') {
          if (buf.length() < MAX_ID_LEN) buf += key;
        }
        else if (key == 'C') {
          if (buf.length() > 0) buf.remove(buf.length() - 1);
        }
        else if (key == '*') {
          if (sire) { state = SELECT_GENDER; updateDisplay("Select Gender:", "A: Male", "B: Female", "ID: " + currentID); }
          else { state = ENTER_SIRE_ID; updateDisplay("Enter Father ID:", "Sire ID: " + currentSireID, "Press '#' when done", "*: Back  D: Skip"); }
          break;
        }
        else if (key == 'D' || (key == '#' && buf.length() > 0)) {
          if (key == 'D') buf = "";
          if (sire) { state = ENTER_DAM_ID; updateDisplay("Enter Mother ID", "Dam ID: ", "D: Skip / #: Done", "*: Back"); }
          else { state = ENTER_WEIGHT; showWeightEntry(); }
          break;
        }
        updateDisplay(sire ? "Enter Father ID:" : "Enter Mother ID:", (sire ? "Sire ID: " : "Dam ID: ") + buf, "Press '#' when done", "*: Back  D: Skip");
      }
      break;

    case ENTER_WEIGHT:
      {
        char key = keypad.getKey();
        if (!key) break;

        if (key >= '0' && key <= '9') {
          if (currentWeightValue.length() < MAX_WEIGHT_LEN) currentWeightValue += key;
        }
        else if (key == 'A') {
          if (currentWeightValue.indexOf('.') == -1 && currentWeightValue.length() > 0 && currentWeightValue.length() < MAX_WEIGHT_LEN) currentWeightValue += ".";
        }
        else if (key == 'C') {
          if (currentWeightValue.length() > 0) currentWeightValue.remove(currentWeightValue.length() - 1);
        }
        else if (key == '*') {
          currentWeightValue = "";
          state = SELECT_WEIGHT_TYPE;
          showWeightTypeMenu();
          break;
        }
        else if (key == '#') {
          float wv = currentWeightValue.toFloat();
          if (currentWeightValue.length() > 0 && wv > 0.0) {
            state = SHOW_SUMMARY;
            String gain = "";
            if (curLastKg.length() && currentWeightType != "birth") {
              float d = wv - curLastKg.toFloat();
              gain = String(d >= 0 ? "+" : "") + String(d, 1) + "kg since " + (curLastDate.length() ? curLastDate : "last");
            }
            String status = saveRecordLocallyAndSend();
            updateDisplay("Saved: " + currentID, currentWeightValue + "kg  " + typeLabel(currentWeightType), gain.length() ? gain : status, gain.length() ? status : "");
            delay(gain.length() ? 3000 : 2500);
            currentID = ""; currentTag = ""; currentWeightType = ""; currentGender = "";
            currentSireID = ""; currentDamID = ""; currentWeightValue = "";
            pendingRawTag = ""; newSheepIdBuf = "";
            state = ENTER_ID;
            resetScreen();
          } else {
            updateDisplay("Invalid weight!", "Enter a number > 0", "Wt: " + currentWeightValue, "*: Back  C: Del");
          }
          break;
        }
        showWeightEntry();
      }
      break;

    case SHOW_SUMMARY:
      break;
  }
}

// ---------------------------------------------------------------------------
// RFID tag -> sheep number (on this device, merged with the website's list)
// ---------------------------------------------------------------------------
String lookupTagMap(String rawTag) {
  String hay = "\n" + tagMapCache;
  String needle = "\n" + rawTag + "=";
  int pos = hay.lastIndexOf(needle);
  if (pos < 0) return "";
  int start = pos + needle.length();
  int end = hay.indexOf('\n', start);
  if (end < 0) end = hay.length();
  return hay.substring(start, end);
}

void saveTagMap(String rawTag, String farmId) {
  if (rawTag.length() == 0) return;
  if (lookupTagMap(rawTag) == farmId) return;
  String line = rawTag + "=" + farmId + "\n";
  tagMapCache += line;          // a later line wins over an older one for the same tag
  appendFile(TAG_FILE, line);
}

// ---------------------------------------------------------------------------
// Flock cache
// ---------------------------------------------------------------------------
bool validateIDOffline(String idToCheck) {
  if (idToCheck.length() == 0) return false;
  String hay = "," + localFlockCache;
  if (hay.charAt(hay.length() - 1) != ',') hay += ",";
  return hay.indexOf("," + idToCheck + ",") != -1;
}

void downloadFlockCache() {
  if (deviceKey.length() == 0) return;
  downloadAnimalInfo();
  String reply;
  if (httpGet("/api/v1/flock", reply) == 200 && reply.length() > 0 && !reply.startsWith("ERROR")) {
    localFlockCache = reply;
    writeFile(FLOCK_FILE, localFlockCache);
  }
  // tags registered anywhere on the farm (other scales, the website) work here too
  if (httpGet("/api/v1/flock?format=tags", reply) == 200 && !reply.startsWith("ERROR")) {
    int start = 0;
    while (start < (int) reply.length()) {
      int nl = reply.indexOf('\n', start); if (nl < 0) nl = reply.length();
      String line = reply.substring(start, nl); start = nl + 1;
      int eq = line.indexOf('=');
      if (eq > 0) {
        String tag = line.substring(0, eq), id = line.substring(eq + 1);
        if (tag != id && lookupTagMap(tag).length() == 0) saveTagMap(tag, id);
      }
    }
  }
}

// ---------------------------------------------------------------------------
// Offline queue — one record per line:
//   ref|id|tag|type|gender|sire|dam|weight|epoch
// (old 6-field lines "id|type|gender|sire|dam|weight" are still understood)
// ---------------------------------------------------------------------------
/** Never throws records away: when memory is full the new one is refused (and you're told), not the oldest. */
bool queueAppend(String rec) {
  if (storageFree() < rec.length() + 4096 || !appendFile(Q_FILE, rec + "\n")) {
    updateDisplay("MEMORY FULL!", "Record NOT saved.", "Sync first (Wi-Fi", "or plug in USB).");
    delay(3000);
    return false;
  }
  return true;
}

int queueCount() {
  File f = LittleFS.open(Q_FILE, "r");
  if (!f) return 0;
  int c = 0;
  while (f.available()) if (f.read() == '\n') c++;
  f.close();
  return c;
}

void dumpQueueToSerial() {
  String q = readFile(Q_FILE);
  Serial.println();
  Serial.println("----BEGIN QUEUE----");
  if (q.length() > 0) Serial.print(q); else Serial.println("(empty - nothing queued)");
  Serial.println("----END QUEUE----");
  Serial.println("Paste the lines between BEGIN/END into Herd Manager > Import & export > Paste from the scale.");
}

// USB commands (115200 baud), used by Herd Manager's "Plug in the scale" page
// and sync_from_scale.ps1:
//   HELLO    -> "KRAALTRAC PRO <fw> QUEUE <n>"
//   DUMP     -> the queue between ----BEGIN QUEUE---- / ----END QUEUE----
//   CLEAR n  -> drops only the FIRST n records (the ones that were dumped and
//               confirmed saved), so anything scanned meanwhile is kept.
//   CLEAR    -> drops everything (old behaviour)
void dropFirstRecords(int n) {
  String q = readFile(Q_FILE);
  int pos = 0;
  for (int i = 0; i < n && pos < (int)q.length(); i++) {
    int nl = q.indexOf('\n', pos);
    if (nl < 0) { pos = q.length(); break; }
    pos = nl + 1;
  }
  writeFile(Q_FILE, q.substring(pos));
}

void handleSerialCommands() {
  if (!Serial.available()) return;
  String cmd = Serial.readStringUntil('\n');
  cmd.trim();
  if (cmd == "HELLO") {
    Serial.println("KRAALTRAC PRO " + String(FIRMWARE) + " QUEUE " + String(queueCount()) + " ROOM " + String(recordsRoom()));
  } else if (cmd == "DUMP") {
    dumpQueueToSerial();
  } else if (cmd.startsWith("CLEAR")) {
    int n = cmd.length() > 5 ? cmd.substring(6).toInt() : -1;
    if (n > 0) dropFirstRecords(n); else writeFile(Q_FILE, "");
    Serial.println("CLEARED " + String(n > 0 ? n : 0) + " LEFT " + String(queueCount()));
    updateDisplay("USB sync done!", "Saved on farmtech", "Scale memory cleared", "Lekker!");
    delay(2500);
    resetScreen();
  }
}

void migrateOldQueueFormat() {
  int oldCount = prefs.getInt("q_count", 0);
  if (oldCount <= 0) return;
  String migrated = "";
  for (int i = 0; i < oldCount; i++) {
    String key = "q_" + String(i);
    String rec = prefs.getString(key.c_str(), "");
    if (rec.length() > 0) migrated += rec + "\n";
    prefs.remove(key.c_str());
  }
  appendFile(Q_FILE, migrated);
  prefs.remove("q_count");
}

/** Queue line → JSON scan for the website. False if the line is malformed. */
bool recordToJson(const String &rec, JsonObject o) {
  String f[9]; int n = 0, from = 0;
  for (int i = 0; i <= (int) rec.length() && n < 9; i++) {
    if (i == (int) rec.length() || rec[i] == '|') { f[n++] = rec.substring(from, i); from = i + 1; }
  }
  if (n == 6) { // old format from before the October 2026 update
    o["ref"] = serialNo + "-old-" + f[0] + "-" + f[5];
    o["id"] = f[0]; o["type"] = f[1]; o["gender"] = f[2]; o["sire"] = f[3]; o["dam"] = f[4]; o["weight"] = f[5];
    return true;
  }
  if (n != 9) return false;
  o["ref"] = f[0]; o["id"] = f[1]; o["type"] = f[3]; o["weight"] = f[7];
  if (f[2].length()) o["tag"] = f[2];
  if (f[4].length()) o["gender"] = f[4];
  if (f[5].length()) o["sire"] = f[5];
  if (f[6].length()) o["dam"] = f[6];
  if (f[8].toInt() > 1700000000) o["ts"] = f[8];
  return true;
}

/**
 * Sends the queue in batches of SYNC_BATCH (one HTTPS request each) and removes
 * a batch only after the website answers SUCCESS. Stops at the first failure so
 * nothing is lost; maxBatches keeps the keypad responsive (the rest goes next time).
 */
void syncOfflineLogs(int maxBatches) {
  if (WiFi.status() != WL_CONNECTED || deviceKey.length() == 0) return;
  for (int b = 0; b < maxBatches; b++) {
    String q = readFile(Q_FILE);
    if (q.length() == 0) return;

    JsonDocument doc; doc["compact"] = true;
    JsonArray arr = doc["scans"].to<JsonArray>();
    int taken = 0, pos = 0;
    while (taken < SYNC_BATCH && pos < (int) q.length()) {
      int nl = q.indexOf('\n', pos); if (nl < 0) nl = q.length();
      String rec = q.substring(pos, nl); pos = nl + 1; taken++;
      if (rec.length()) { JsonObject o = arr.add<JsonObject>(); if (!recordToJson(rec, o)) arr.remove(arr.size() - 1); }
    }

    if (arr.size() > 0) {
      String body, reply; serializeJson(doc, body);
      int code = httpPost("/api/v1/scans", body, "application/json", reply);
      if (code == 401) { deviceKey = ""; prefs.remove("devkey"); return; }   // key revoked: pair again (D → #)
      if (code != 201 || reply.indexOf("SUCCESS") < 0) return;                 // offline / server busy: try later
    }
    dropFirstRecords(taken);
  }
}

String saveRecordLocallyAndSend() {
  refSeq++; prefs.putUInt("refseq", refSeq);
  String ts = clockIsSet() ? String((unsigned long) time(nullptr)) : "";
  String record = serialNo + "-" + String(refSeq) + "|" + currentID + "|" + currentTag + "|" + currentWeightType + "|" +
                  currentGender + "|" + currentSireID + "|" + currentDamID + "|" + currentWeightValue + "|" + ts;
  if (!queueAppend(record)) return "NOT SAVED: full";
  noteWeighed(currentID, currentTag, currentGender.length() ? currentGender : curSex, currentWeightValue);

  if (!validateIDOffline(currentID)) {
    localFlockCache += currentID + ",";
    appendFile(FLOCK_FILE, currentID + ",");
  }

  lastSavedID = currentID;
  lastSaveMillis = millis();

  if (WiFi.status() == WL_CONNECTED) syncOfflineLogs(1);

  int qc = queueCount();
  return qc == 0 ? "Synced OK" : ("Queued: " + String(qc));
}

// ---------------------------------------------------------------------------
// Background housekeeping
// ---------------------------------------------------------------------------
void doSyncAndAnnounce() {
  int before = queueCount();
  if (before == 0) return;
  syncOfflineLogs();
  int after = queueCount();
  if (after == before) return;

  if (state == ENTER_ID) {
    if (after == 0) updateDisplay("Sync complete!", String(before) + " record(s) sent", "All caught up.", "");
    else updateDisplay("Synced " + String(before - after) + " record(s)", String(after) + " still queued", "Will keep trying...", "");
    delay(2000);
    resetScreen();
  }
}

void maintainWiFi() {
  bool nowConnected = (WiFi.status() == WL_CONNECTED);
  bool idle = (state == ENTER_ID && currentID.length() == 0);
  // A Wi-Fi search freezes the keypad for a moment, so only look while nobody is typing.
  if (!nowConnected && idle && millis() - lastWifiAttempt > WIFI_RETRY_MS) {
    lastWifiAttempt = millis();
    wifiMulti.run(2500);
    nowConnected = (WiFi.status() == WL_CONNECTED);
  }
  if (nowConnected && !wifiWasConnected) {
    if (deviceKey.length() == 0 && idle && !pairingSkipped) pairDevice();
    if (!clockIsSet()) syncClock();
    doSyncAndAnnounce();
    downloadFlockCache();
  }
  wifiWasConnected = nowConnected;
}

void periodicSync() {
  if (millis() - lastSyncAttempt > SYNC_INTERVAL_MS && state == ENTER_ID && currentID.length() == 0) { lastSyncAttempt = millis(); doSyncAndAnnounce(); }
}

void periodicFlockRefresh() {
  if (millis() - lastFlockRefresh > FLOCK_REFRESH_MS) {
    lastFlockRefresh = millis();
    if (WiFi.status() == WL_CONNECTED) downloadFlockCache();
  }
}

void resetScreen() {
  int qc = queueCount();
  int room = recordsRoom();
  if (room < 150) updateDisplay(room == 0 ? "!! MEMORY FULL !!" : "Memory low: " + String(room) + " left", "Scan Tag or Type ID", "ID: ", "Sync soon! D:Info");
  else if (qc > 0) updateDisplay(">> " + String(qc) + " QUEUED <<", "Scan Tag or Type ID", "ID: ", "B:Dump A:Clear D:Info");
  else updateDisplay("KraalTrac Pro", "Scan Tag or Type ID", "ID: ", deviceKey.length() ? "All synced. D:Info" : "Not paired. D:Info");
}
