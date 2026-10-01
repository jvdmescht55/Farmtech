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
 *      LCD shows a 6-digit PAIR CODE. Type it in Herd Manager → KraalTrac Pro
 *      → More → Devices → "Pair it". The scale saves its own key in flash.
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
 * ==========================================================================*/

#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <Keypad.h>
#include <WiFi.h>
#include <WiFiMulti.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <Preferences.h>
#include <ArduinoJson.h>
#include <sys/time.h>
#include <time.h>

// --- LCD & STORAGE SETUP ---
LiquidCrystal_I2C lcd(0x27, 20, 4);
Preferences prefs;

// --- NETWORK CONFIG ---
// Every network it might be near — farm Wi-Fi, your phone's hotspot (give
// the hotspot a FIXED name/password in your phone's settings first).
struct WifiNet { const char* ssid; const char* password; };
WifiNet knownNetworks[] = {
  { "YOUR_FARM_WIFI",      "YOUR_FARM_WIFI_PASSWORD" },
  { "YOUR_PHONE_HOTSPOT",  "YOUR_HOTSPOT_PASSWORD" },
};
WiFiMulti wifiMulti;

const char* SERVER    = "https://farmtech.site";
const char* MODEL     = "KraalTrac Pro";
const char* FIRMWARE  = "3.0.0";
// Optional: paste a device key from Herd Manager → Devices → "Add a device by
// hand" here to skip pairing. Leave empty to pair with a 6-digit code.
const char* DEVICE_KEY = "";

const char* CLEAR_PIN = "1379";   // PIN to clear the queue / re-pair on the keypad

// --- LIMITS / TIMING ---
const int MAX_ID_LEN            = 10;     // existing numbers can be 4–10 digits (2415, 21270, 197013…)
const int NEW_SHEEP_ID_LEN      = 6;      // new sheep: birthday code YYMMNN
const int PIN_LEN               = 4;
const int MAX_WEIGHT_LEN        = 6;
const unsigned long RESCAN_COOLDOWN_MS = 8000;
const unsigned long SYNC_INTERVAL_MS   = 60000UL;
const unsigned long FLOCK_REFRESH_MS   = 6UL * 60 * 60 * 1000UL;
const unsigned long WIFI_RETRY_MS      = 15000UL;
const int MAX_QUEUE_LINES        = 300;

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
String pinBuf = "";

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
// Pairing — shows a 6-digit code, farmer types it into Herd Manager
// ---------------------------------------------------------------------------
void pairDevice() {
  while (deviceKey.length() == 0) {
    if (WiFi.status() != WL_CONNECTED) {
      updateDisplay("KraalTrac Pro", "Needs Wi-Fi once", "to pair with the", "website. Waiting...");
      wifiMulti.run(); delay(3000);
      continue;
    }
    JsonDocument q; q["serial"] = serialNo; q["model"] = MODEL; q["firmware"] = FIRMWARE;
    String body, reply; serializeJson(q, body);
    if (httpPost("/api/v1/pair", body, "application/json", reply, false) != 201) { delay(4000); continue; }
    JsonDocument r; deserializeJson(r, reply);
    String code = r["code"].as<String>(), secret = r["secret"].as<String>();
    unsigned long until = millis() + r["expires_in"].as<unsigned long>() * 1000UL;
    updateDisplay("Pair at farmtech.site", "Devices > Pair it:", "      " + code.substring(0, 3) + " " + code.substring(3), "Waiting...");
    while (millis() < until) {
      delay(5000);
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
  if (code == 401) { deviceKey = ""; prefs.remove("devkey"); pairDevice(); return; }
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
  migrateOldQueueFormat();
  localFlockCache = prefs.getString("flock", "");
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
  for (auto &n : knownNetworks) wifiMulti.addAP(n.ssid, n.password);
  updateDisplay("KraalTrac Pro", "Connecting...", "", serialNo);
  unsigned long t0 = millis();
  while (wifiMulti.run() != WL_CONNECTED && millis() - t0 < 8000) { delay(200); }

  if (WiFi.status() == WL_CONNECTED) {
    if (deviceKey.length() == 0) pairDevice();
    syncClock();
    syncOfflineLogs();
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
              updateDisplay("Sheep: " + currentID, "Select Weight Type:", "A:Birth  B:Wean", "C:Post-W D:Mature");
            }
          } else {
            pendingRawTag = scannedRawTag;
            currentID = "";
            state = UNKNOWN_TAG_PROMPT;
            updateDisplay("New tag scanned", "Not a known sheep", "A: Give it a number", "*: Cancel / Retry");
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
              showWeightTypeMenu();
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
                        deviceKey.length() ? "Paired: yes" : "Paired: NO",
                        "Queued: " + String(queueCount()) + "  fw " + FIRMWARE,
                        "#: Re-pair  *: Back");
        }
      }
      break;

    case DEVICE_STATUS:
      {
        char key = keypad.getKey();
        if (!key) break;
        if (key == '*') { state = ENTER_ID; resetScreen(); }
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
              prefs.putString("queue", "");
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
          updateDisplay("New sheep's number:", "ID: " + newSheepIdBuf, "YYMMNN e.g. 250912", "#: OK C:Del *:Cancel");
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
              updateDisplay("Linked to " + currentID, "Select Weight Type:", "A:Birth  B:Wean", "C:Post-W D:Mature");
            } else {
              state = SELECT_GENDER;
              updateDisplay("New sheep " + currentID, "Select Gender:", "A: Male", "B: Female");
            }
          } else {
            updateDisplay("Needs 6 digits:", "YY MM NN", "e.g. 250912 = 2025,", "Sep, 12th. C:Del");
            delay(2200);
          }
          if (state != ENTER_NEW_SHEEP_ID) break;
        }
        updateDisplay("New sheep's number:", "ID: " + newSheepIdBuf, "YYMMNN e.g. 250912", "#: OK C:Del *:Cancel");
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
            String status = saveRecordLocallyAndSend();
            updateDisplay("Saved: " + currentID, currentWeightValue + "kg  " + typeLabel(currentWeightType), status, "");
            delay(2500);
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
  String hay = "\n" + prefs.getString("tagmap", "");
  String needle = "\n" + rawTag + "=";
  int pos = hay.indexOf(needle);
  if (pos < 0) return "";
  int start = pos + needle.length();
  int end = hay.indexOf('\n', start);
  if (end < 0) end = hay.length();
  return hay.substring(start, end);
}

void saveTagMap(String rawTag, String farmId) {
  if (rawTag.length() == 0) return;
  if (lookupTagMap(rawTag) == farmId) return;
  String map = prefs.getString("tagmap", "");
  map += rawTag + "=" + farmId + "\n";
  prefs.putString("tagmap", map);
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
  String reply;
  if (httpGet("/api/v1/flock", reply) == 200 && reply.length() > 0 && !reply.startsWith("ERROR")) {
    localFlockCache = reply;
    prefs.putString("flock", localFlockCache);
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
void queueAppend(String rec) {
  String q = prefs.getString("queue", "");
  int lines = 0;
  for (unsigned int i = 0; i < q.length(); i++) if (q[i] == '\n') lines++;
  if (lines >= MAX_QUEUE_LINES) {
    int firstNL = q.indexOf('\n');
    if (firstNL >= 0) q = q.substring(firstNL + 1);
  }
  q += rec + "\n";
  prefs.putString("queue", q);
}

int queueCount() {
  String q = prefs.getString("queue", "");
  int c = 0;
  for (unsigned int i = 0; i < q.length(); i++) if (q[i] == '\n') c++;
  return c;
}

void dumpQueueToSerial() {
  String q = prefs.getString("queue", "");
  Serial.println();
  Serial.println("----BEGIN QUEUE----");
  if (q.length() > 0) Serial.print(q); else Serial.println("(empty - nothing queued)");
  Serial.println("----END QUEUE----");
  Serial.println("Paste the lines between BEGIN/END into Herd Manager > Import & export > Paste from the scale.");
}

void handleSerialCommands() {
  if (!Serial.available()) return;
  String cmd = Serial.readStringUntil('\n');
  cmd.trim();
  if (cmd == "DUMP") dumpQueueToSerial();
  else if (cmd == "CLEAR") {
    prefs.putString("queue", "");
    Serial.println("Queue cleared (confirmed saved by sync_from_scale.ps1).");
    if (state == ENTER_ID) resetScreen();
  }
}

void migrateOldQueueFormat() {
  int oldCount = prefs.getInt("q_count", 0);
  if (oldCount <= 0) return;
  String migrated = prefs.getString("queue", "");
  for (int i = 0; i < oldCount; i++) {
    String key = "q_" + String(i);
    String rec = prefs.getString(key.c_str(), "");
    if (rec.length() > 0) migrated += rec + "\n";
    prefs.remove(key.c_str());
  }
  prefs.putString("queue", migrated);
  prefs.remove("q_count");
}

/** Send one queued record. True only when the website confirms it (201 + SUCCESS). */
bool postOneRecord(String rec) {
  String f[9]; int n = 0, from = 0;
  for (int i = 0; i <= (int) rec.length() && n < 9; i++) {
    if (i == (int) rec.length() || rec[i] == '|') { f[n++] = rec.substring(from, i); from = i + 1; }
  }
  String ref, id, tag, type, gender, sire, dam, weight, ts;
  if (n == 6) { // old format from before this update
    id = f[0]; type = f[1]; gender = f[2]; sire = f[3]; dam = f[4]; weight = f[5];
    ref = serialNo + "-old-" + id + "-" + weight;
  } else if (n == 9) {
    ref = f[0]; id = f[1]; tag = f[2]; type = f[3]; gender = f[4]; sire = f[5]; dam = f[6]; weight = f[7]; ts = f[8];
  } else {
    return true; // malformed — drop rather than jam the queue
  }

  String body = "ref=" + urlEncode(ref) + "&id=" + urlEncode(id) + "&weight=" + urlEncode(weight) + "&type=" + urlEncode(type);
  if (tag.length())    body += "&tag=" + urlEncode(tag);
  if (gender.length()) body += "&gender=" + urlEncode(gender);
  if (sire.length())   body += "&sire=" + urlEncode(sire);
  if (dam.length())    body += "&dam=" + urlEncode(dam);
  if (ts.toInt() > 1700000000) body += "&ts=" + ts;

  String reply;
  int code = httpPost("/api/v1/scans", body, "application/x-www-form-urlencoded", reply);
  if (code == 401) { deviceKey = ""; prefs.remove("devkey"); return false; }
  return (code == 201 && reply.indexOf("SUCCESS") >= 0);
}

void syncOfflineLogs() {
  if (WiFi.status() != WL_CONNECTED || deviceKey.length() == 0) return;
  String q = prefs.getString("queue", "");
  if (q.length() == 0) return;

  String remaining = "";
  int start = 0;
  while (start < (int) q.length()) {
    int nl = q.indexOf('\n', start);
    if (nl < 0) break;
    String rec = q.substring(start, nl);
    start = nl + 1;
    if (rec.length() == 0) continue;
    if (!postOneRecord(rec)) remaining += rec + "\n";
  }
  prefs.putString("queue", remaining);
}

String saveRecordLocallyAndSend() {
  refSeq++; prefs.putUInt("refseq", refSeq);
  String ts = clockIsSet() ? String((unsigned long) time(nullptr)) : "";
  String record = serialNo + "-" + String(refSeq) + "|" + currentID + "|" + currentTag + "|" + currentWeightType + "|" +
                  currentGender + "|" + currentSireID + "|" + currentDamID + "|" + currentWeightValue + "|" + ts;
  queueAppend(record);

  if (!validateIDOffline(currentID)) {
    localFlockCache += currentID + ",";
    prefs.putString("flock", localFlockCache);
  }

  lastSavedID = currentID;
  lastSaveMillis = millis();

  if (WiFi.status() == WL_CONNECTED) syncOfflineLogs();

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
  if (!nowConnected && millis() - lastWifiAttempt > WIFI_RETRY_MS) {
    lastWifiAttempt = millis();
    wifiMulti.run();
  }
  if (nowConnected && !wifiWasConnected) {
    if (deviceKey.length() == 0 && state == ENTER_ID) pairDevice();
    if (!clockIsSet()) syncClock();
    doSyncAndAnnounce();
    downloadFlockCache();
  }
  wifiWasConnected = nowConnected;
}

void periodicSync() {
  if (millis() - lastSyncAttempt > SYNC_INTERVAL_MS) { lastSyncAttempt = millis(); doSyncAndAnnounce(); }
}

void periodicFlockRefresh() {
  if (millis() - lastFlockRefresh > FLOCK_REFRESH_MS) {
    lastFlockRefresh = millis();
    if (WiFi.status() == WL_CONNECTED) downloadFlockCache();
  }
}

void resetScreen() {
  int qc = queueCount();
  if (qc > 0) updateDisplay(">> " + String(qc) + " QUEUED <<", "Scan Tag or Type ID", "ID: ", "B:Dump A:Clear D:Info");
  else updateDisplay("KraalTrac Pro", "Scan Tag or Type ID", "ID: ", deviceKey.length() ? "All synced. D:Info" : "Not paired. D:Info");
}
