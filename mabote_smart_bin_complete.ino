/*
 * MaBote.ph Smart Recycling Machine - Complete Code
 * Hardware: ESP32 + MH ET Live Scanner + LCD I2C + IR LM393 + Ultrasonic + Servo MG996R + Proximity LJC18A3
 * 
 * Workflow:
 * 1. User scans QR code → Verify user → Ready to accept bottles
 * 2. Proximity + IR sensors detect bottle
 * 3. If plastic bottle → Servo tilts LEFT → Accepted storage
 * 4. If not plastic → Servo tilts RIGHT → Rejected storage
 * 5. Points added to user account
 * 6. Ultrasonic sensor checks bin level
 * 7. LCD displays status messages
 * 8. Auto-reset after 8 seconds idle
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <LiquidCrystal_I2C.h>
#include <ESP32Servo.h>
#include <NewPing.h>

// ============================================
// WIFI CONFIGURATION
// ============================================
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// ============================================
// API CONFIGURATION
// ============================================
const char* api_base = "http://192.168.254.128/mabote_api";
const char* scan_url = "/scan.php";
const char* finalize_deposit_url = "/finalize_deposit.php";
const char* machine_status_url = "/machine_status.php";

// ============================================
// HARDWARE PIN DEFINITIONS
// ============================================
// MH ET Live Scanner (QR Code Scanner)
#define QR_RX_PIN 16
#define QR_TX_PIN 17

// LCD I2C Display
#define LCD_SDA_PIN 21
#define LCD_SCL_PIN 22

// IR Sensor LM393
#define IR_SENSOR_PIN 19

// Ultrasonic Sensor HC-SR04
#define ULTRASONIC_TRIG_PIN 26
#define ULTRASONIC_ECHO_PIN 25

// Servo Motor MG996R
#define SERVO_PIN 18

// Proximity Sensor LJC18A3-H-Z/BY
#define PROXIMITY_SENSOR_PIN 23

// Status LED (Optional)
#define LED_PIN 2

// ============================================
// SERVO POSITIONS
// ============================================
const int SERVO_CENTER = 90;   // Neutral position
const int SERVO_LEFT = 45;     // Accept (tilt left for accepted storage)
const int SERVO_RIGHT = 135;   // Reject (tilt right for rejected storage)

// ============================================
// SENSOR THRESHOLDS
// ============================================
const int IR_THRESHOLD = 500;           // IR sensor detection threshold
const int PROXIMITY_THRESHOLD = 100;    // Proximity sensor threshold (mm)
const int EMPTY_DISTANCE = 50;          // Ultrasonic: bin empty distance (cm)
const int FULL_DISTANCE = 5;            // Ultrasonic: bin full distance (cm)
const unsigned long IDLE_TIMEOUT = 8000; // 8 seconds idle timeout

// ============================================
// MACHINE CONFIGURATION
// ============================================
const char* machine_id = "BIN001";
const char* location = "Mall Entrance";

// ============================================
// HARDWARE OBJECTS
// ============================================
LiquidCrystal_I2C lcd(0x27, 16, 2); // I2C address 0x27, 16 columns, 2 rows
Servo pipeServo;                     // Servo for PVC pipe tilting
NewPing sonar(ULTRASONIC_TRIG_PIN, ULTRASONIC_ECHO_PIN, 200); // Max distance 200cm

// ============================================
// STATE VARIABLES
// ============================================
String scannedQR = "";
String verifiedUsername = "";
bool userVerified = false;
bool bottleDetected = false;
bool isPlasticBottle = false;
unsigned long lastActivityTime = 0;
unsigned long lastStatusUpdate = 0;
int binFillLevel = 0;
bool binFull = false;

// Machine states
enum MachineState {
  STATE_WAITING_QR,      // Waiting for QR code scan
  STATE_VERIFYING,       // Verifying user
  STATE_USER_VERIFIED,   // User verified, ready for bottle
  STATE_DETECTING,       // Detecting bottle
  STATE_PROCESSING,      // Processing deposit
  STATE_BIN_FULL         // Bin is full
};

MachineState currentState = STATE_WAITING_QR;

// ============================================
// SETUP FUNCTION
// ============================================
void setup() {
  Serial.begin(115200);
  Serial2.begin(9600, SERIAL_8N1, QR_RX_PIN, QR_TX_PIN); // MH ET Live Scanner
  
  // Initialize pins
  pinMode(IR_SENSOR_PIN, INPUT);
  pinMode(PROXIMITY_SENSOR_PIN, INPUT);
  pinMode(LED_PIN, OUTPUT);
  
  // Initialize LCD
  lcd.init();
  lcd.backlight();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("MaBote.ph");
  lcd.setCursor(0, 1);
  lcd.print("Initializing...");
  delay(2000);
  
  // Initialize Servo
  pipeServo.attach(SERVO_PIN);
  pipeServo.write(SERVO_CENTER); // Start in center position
  delay(500);
  
  // Connect to WiFi
  connectToWiFi();
  
  // Initial display
  displayMessage("Scan the QR code", "");
  
  Serial.println("MaBote.ph Smart Bin initialized!");
  Serial.println("Waiting for QR code scan...");
  
  lastActivityTime = millis();
}

// ============================================
// MAIN LOOP
// ============================================
void loop() {
  // Check WiFi connection
  if (WiFi.status() != WL_CONNECTED) {
    connectToWiFi();
  }
  
  // Check bin level
  checkBinLevel();
  
  // Handle state machine
  switch (currentState) {
    case STATE_WAITING_QR:
      handleWaitingQR();
      break;
      
    case STATE_VERIFYING:
      // Verification is handled in handleWaitingQR
      break;
      
    case STATE_USER_VERIFIED:
      handleUserVerified();
      break;
      
    case STATE_DETECTING:
      handleDetecting();
      break;
      
    case STATE_PROCESSING:
      handleProcessing();
      break;
      
    case STATE_BIN_FULL:
      handleBinFull();
      break;
  }
  
  // Check for idle timeout (8 seconds)
  if (currentState == STATE_USER_VERIFIED && (millis() - lastActivityTime > IDLE_TIMEOUT)) {
    resetMachine();
  }
  
  // Update machine status every 30 seconds
  if (millis() - lastStatusUpdate > 30000) {
    updateMachineStatus();
    lastStatusUpdate = millis();
  }
  
  delay(100);
}

// ============================================
// STATE HANDLERS
// ============================================
void handleWaitingQR() {
  // Read QR code from scanner
  if (Serial2.available()) {
    String qrData = Serial2.readString();
    qrData.trim();
    
    if (qrData.length() > 0 && qrData != scannedQR) {
      scannedQR = qrData;
      Serial.println("QR Code scanned: " + scannedQR);
      
      // Change state to verifying
      currentState = STATE_VERIFYING;
      displayMessage("Verifying...", "");
      
      // Verify user via API
      if (verifyUser(scannedQR)) {
        currentState = STATE_USER_VERIFIED;
        displayMessage("User Verified", "");
        delay(2000);
        displayMessage("Hello, " + verifiedUsername, "");
        delay(2000);
        displayMessage("Please Insert", "Bottle");
        lastActivityTime = millis();
      } else {
        // Verification failed
        displayMessage("Verification", "Failed");
        delay(2000);
        resetMachine();
      }
    }
  }
}

void handleUserVerified() {
  // Check for bottle detection
  bool proximityDetected = readProximitySensor();
  bool irDetected = readIRSensor();
  
  if (proximityDetected && irDetected) {
    // Both sensors detected object
    bottleDetected = true;
    currentState = STATE_DETECTING;
    lastActivityTime = millis();
  }
}

void handleDetecting() {
  // Classify bottle type
  bool proximityDetected = readProximitySensor();
  bool irDetected = readIRSensor();
  
  if (proximityDetected && irDetected) {
    // Analyze sensor readings to determine if plastic bottle
    isPlasticBottle = classifyBottle(proximityDetected, irDetected);
    
    // Move servo based on classification
    if (isPlasticBottle) {
      // Tilt left for accepted storage
      pipeServo.write(SERVO_LEFT);
      displayMessage("Bottle Accepted", "");
      delay(1000);
    } else {
      // Tilt right for rejected storage
      pipeServo.write(SERVO_RIGHT);
      displayMessage("Object Rejected", "");
      delay(1000);
    }
    
    // Process deposit
    currentState = STATE_PROCESSING;
  } else {
    // Object passed through
    delay(2000);
    pipeServo.write(SERVO_CENTER); // Return to center
    currentState = STATE_USER_VERIFIED;
    displayMessage("Please Insert", "Another Bottle");
    lastActivityTime = millis();
  }
}

void handleProcessing() {
  if (isPlasticBottle) {
    // Process deposit and add points
    if (processDeposit()) {
      displayMessage("Points Added!", "");
      delay(2000);
    } else {
      displayMessage("Error Processing", "");
      delay(2000);
    }
  }
  
  // Return servo to center
  pipeServo.write(SERVO_CENTER);
  delay(1000);
  
  // Reset for next bottle
  bottleDetected = false;
  isPlasticBottle = false;
  currentState = STATE_USER_VERIFIED;
  displayMessage("Please Insert", "Another Bottle");
  lastActivityTime = millis();
}

void handleBinFull() {
  displayMessage("Bin is Full", "Please Contact Admin");
  pipeServo.write(SERVO_CENTER); // Keep servo in center
  // Don't accept any more bottles
}

// ============================================
// WIFI FUNCTIONS
// ============================================
void connectToWiFi() {
  Serial.print("Connecting to WiFi: ");
  Serial.println(ssid);
  
  WiFi.begin(ssid, password);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println();
    Serial.println("WiFi connected!");
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println();
    Serial.println("WiFi connection failed!");
  }
}

// ============================================
// API FUNCTIONS
// ============================================
bool verifyUser(String qrCode) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi not connected!");
    return false;
  }
  
  HTTPClient http;
  String url = String(api_base) + String(scan_url);
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  
  // Create JSON request
  DynamicJsonDocument doc(512);
  doc["qr_code"] = qrCode;
  doc["machine_id"] = machine_id;
  
  String jsonString;
  serializeJson(doc, jsonString);
  
  Serial.println("Verifying user: " + qrCode);
  Serial.println("Request: " + jsonString);
  
  int httpResponseCode = http.POST(jsonString);
  
  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.println("Response code: " + String(httpResponseCode));
    Serial.println("Response: " + response);
    
    DynamicJsonDocument responseDoc(1024);
    DeserializationError error = deserializeJson(responseDoc, response);
    
    if (!error && responseDoc["success"] == true) {
      verifiedUsername = responseDoc["user"]["first_name"].as<String>();
      Serial.println("User verified: " + verifiedUsername);
      http.end();
      return true;
    } else {
      Serial.println("User verification failed");
      http.end();
      return false;
    }
  } else {
    Serial.println("Error on HTTP request: " + String(httpResponseCode));
    http.end();
    return false;
  }
}

bool processDeposit() {
  if (WiFi.status() != WL_CONNECTED) {
    return false;
  }
  
  HTTPClient http;
  String url = String(api_base) + String(finalize_deposit_url);
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  
  // Create JSON request
  DynamicJsonDocument doc(512);
  doc["user_qr"] = scannedQR;
  doc["machine_id"] = machine_id;
  doc["bottle_count"] = 1;
  doc["bottle_type"] = "plastic";
  
  String jsonString;
  serializeJson(doc, jsonString);
  
  Serial.println("Processing deposit...");
  Serial.println("Request: " + jsonString);
  
  int httpResponseCode = http.POST(jsonString);
  
  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.println("Response code: " + String(httpResponseCode));
    Serial.println("Response: " + response);
    
    DynamicJsonDocument responseDoc(1024);
    DeserializationError error = deserializeJson(responseDoc, response);
    
    if (!error && responseDoc["success"] == true) {
      Serial.println("Deposit processed successfully!");
      http.end();
      return true;
    } else {
      Serial.println("Deposit processing failed");
      http.end();
      return false;
    }
  } else {
    Serial.println("Error on HTTP request: " + String(httpResponseCode));
    http.end();
    return false;
  }
}

void updateMachineStatus() {
  if (WiFi.status() != WL_CONNECTED) {
    return;
  }
  
  HTTPClient http;
  String url = String(api_base) + String(machine_status_url);
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  
  DynamicJsonDocument doc(512);
  doc["machine_id"] = machine_id;
  doc["fill_level"] = binFillLevel;
  doc["status"] = binFull ? "full" : "active";
  doc["location"] = location;
  
  String jsonString;
  serializeJson(doc, jsonString);
  
  int httpResponseCode = http.POST(jsonString);
  
  if (httpResponseCode > 0) {
    Serial.println("Machine status updated");
  }
  
  http.end();
}

// ============================================
// SENSOR FUNCTIONS
// ============================================
bool readIRSensor() {
  int irValue = analogRead(IR_SENSOR_PIN);
  // LM393 outputs LOW when object detected (some models)
  // Adjust logic based on your sensor
  bool detected = (irValue < IR_THRESHOLD);
  return detected;
}

bool readProximitySensor() {
  int proximityValue = analogRead(PROXIMITY_SENSOR_PIN);
  // LJC18A3 proximity sensor logic
  // Adjust threshold based on your sensor specifications
  bool detected = (proximityValue > PROXIMITY_THRESHOLD);
  return detected;
}

int readUltrasonicSensor() {
  unsigned int distance = sonar.ping_cm();
  if (distance == 0) {
    distance = 200; // Max distance if no echo
  }
  return distance;
}

void checkBinLevel() {
  int distance = readUltrasonicSensor();
  
  // Calculate fill level percentage
  if (distance <= FULL_DISTANCE) {
    binFillLevel = 100;
    binFull = true;
    if (currentState != STATE_BIN_FULL) {
      currentState = STATE_BIN_FULL;
    }
  } else if (distance >= EMPTY_DISTANCE) {
    binFillLevel = 0;
    binFull = false;
  } else {
    // Linear interpolation
    binFillLevel = map(distance, FULL_DISTANCE, EMPTY_DISTANCE, 100, 0);
    binFull = false;
  }
  
  Serial.println("Bin level: " + String(binFillLevel) + "% (Distance: " + String(distance) + "cm)");
}

bool classifyBottle(bool proximity, bool ir) {
  // Simple classification logic
  // You can enhance this with more sophisticated algorithms
  
  // Both sensors detecting suggests object is present
  if (proximity && ir) {
    // Analyze sensor readings to determine if plastic
    // For now, assume all detected objects are plastic bottles
    // You can add more logic based on sensor readings
    
    // Example: Check sensor response patterns
    // Plastic bottles might have different reflection characteristics
    
    return true; // Assume plastic for now
  }
  
  return false;
}

// ============================================
// DISPLAY FUNCTIONS
// ============================================
void displayMessage(String line1, String line2) {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print(line1);
  if (line2.length() > 0) {
    lcd.setCursor(0, 1);
    lcd.print(line2);
  }
  Serial.println("LCD: " + line1 + " | " + line2);
}

// ============================================
// UTILITY FUNCTIONS
// ============================================
void resetMachine() {
  Serial.println("Resetting machine...");
  
  scannedQR = "";
  verifiedUsername = "";
  userVerified = false;
  bottleDetected = false;
  isPlasticBottle = false;
  currentState = STATE_WAITING_QR;
  
  pipeServo.write(SERVO_CENTER);
  
  displayMessage("Scan the QR code", "");
  
  lastActivityTime = millis();
  
  Serial.println("Machine reset complete");
}

// ============================================
// END OF CODE
// ============================================








