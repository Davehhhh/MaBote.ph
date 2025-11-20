/*
 * MaBote.ph Smart Bin ESP32 Code
 * Hardware: ESP32 DevKit V1 + GM65 QR Scanner + MG996R Servo + TCRT5000 + HX711
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <SoftwareSerial.h>
#include <HX711.h>

// WiFi Credentials
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// API Endpoints
const char* api_base = "http://192.168.254.119/mabote_api";
const char* register_url = "/machine_register.php";
const char* deposit_url = "/machine_deposit.php";
const char* status_url = "/machine_status.php";

// Hardware Pins - Updated for your design
#define QR_RX_PIN 16
#define QR_TX_PIN 17
#define SERVO_PIN 18
#define IR_SENSOR_PIN 19
#define HX711_DT_PIN 21
#define HX711_SCK_PIN 22
#define LED_PIN 2
#define LCD_SDA_PIN 4
#define LCD_SCL_PIN 5

// LCD Display
#include <LiquidCrystal_I2C.h>
LiquidCrystal_I2C lcd(0x27, 16, 2); // I2C address, 16 columns, 2 rows

// Machine Configuration
const char* machine_id = "BIN001";
const char* location = "Mall Entrance";

// Hardware Objects
SoftwareSerial qrSerial(QR_RX_PIN, QR_TX_PIN);
HX711 scale;

// Servo Motor
#include <ESP32Servo.h>
Servo lidServo;

// Variables
String scannedQR = "";
bool bottleDetected = false;
float currentWeight = 0;
float emptyWeight = 0;
int bottlesCount = 0;
unsigned long lastStatusUpdate = 0;
unsigned long lastWeightCheck = 0;
bool machineRegistered = false;

// Calibration values (adjust based on your load cell)
const float calibration_factor = 1000.0; // Adjust this value

void setup() {
  Serial.begin(115200);
  qrSerial.begin(9600);
  
  // Initialize pins
  pinMode(IR_SENSOR_PIN, INPUT);
  pinMode(LED_PIN, OUTPUT);
  
  // Initialize LCD display
  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("MaBote.ph");
  lcd.setCursor(0, 1);
  lcd.print("Initializing...");
  
  // Initialize servo
  lidServo.attach(SERVO_PIN);
  lidServo.write(0); // Close lid initially
  
  // Initialize load cell
  scale.begin(HX711_DT_PIN, HX711_SCK_PIN);
  scale.set_scale(calibration_factor);
  scale.tare(); // Reset to zero
  
  // Connect to WiFi
  connectToWiFi();
  
  // Register machine
  registerMachine();
  
  // Calibrate empty weight
  calibrateEmptyWeight();
  
  // Display ready message
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Please scan your");
  lcd.setCursor(0, 1);
  lcd.print("QR code");
  
  Serial.println("Smart Bin initialized successfully!");
}

void loop() {
  // Check WiFi connection
  if (WiFi.status() != WL_CONNECTED) {
    connectToWiFi();
  }
  
  // Read QR code
  readQRCode();
  
  // Check for bottle detection
  checkBottleDetection();
  
  // Update machine status every 30 seconds
  if (millis() - lastStatusUpdate > 30000) {
    updateMachineStatus();
    lastStatusUpdate = millis();
  }
  
  // Process bottle deposit if QR scanned and bottle detected
  if (!scannedQR.isEmpty() && bottleDetected) {
    processBottleDeposit();
  }
  
  delay(100);
}

void connectToWiFi() {
  Serial.print("Connecting to WiFi...");
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.print(".");
  }
  
  Serial.println();
  Serial.println("WiFi connected!");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());
}

void registerMachine() {
  if (WiFi.status() != WL_CONNECTED) return;
  
  HTTPClient http;
  http.begin(String(api_base) + register_url);
  http.addHeader("Content-Type", "application/json");
  
  DynamicJsonDocument doc(1024);
  doc["machine_id"] = machine_id;
  doc["location"] = location;
  doc["status"] = "active";
  
  String jsonString;
  serializeJson(doc, jsonString);
  
  int httpResponseCode = http.POST(jsonString);
  
  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.println("Machine registration response: " + response);
    
    DynamicJsonDocument responseDoc(1024);
    deserializeJson(responseDoc, response);
    
    if (responseDoc["success"]) {
      machineRegistered = true;
      Serial.println("Machine registered successfully!");
    }
  } else {
    Serial.println("Error registering machine: " + String(httpResponseCode));
  }
  
  http.end();
}

void calibrateEmptyWeight() {
  Serial.println("Calibrating empty weight...");
  delay(2000);
  
  emptyWeight = scale.get_units(10); // Average of 10 readings
  Serial.println("Empty weight calibrated: " + String(emptyWeight));
}

void readQRCode() {
  if (qrSerial.available()) {
    String qrData = qrSerial.readString();
    qrData.trim();
    
    if (qrData.length() > 0 && qrData != scannedQR) {
      scannedQR = qrData;
      Serial.println("QR Code scanned: " + scannedQR);
      
      // Update LCD display
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("QR Code Detected");
      lcd.setCursor(0, 1);
      lcd.print("Opening lid...");
      
      // Blink LED to indicate QR scan
      digitalWrite(LED_PIN, HIGH);
      delay(200);
      digitalWrite(LED_PIN, LOW);
      
      // Open lid for 5 seconds
      openLid();
      delay(5000);
      closeLid();
      
      // Reset display
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Please insert");
      lcd.setCursor(0, 1);
      lcd.print("bottles now");
    }
  }
}

void checkBottleDetection() {
  // Check IR sensor for bottle presence
  bool irDetected = digitalRead(IR_SENSOR_PIN) == LOW; // TCRT5000 is active LOW
  
  // Check weight change
  if (millis() - lastWeightCheck > 1000) {
    currentWeight = scale.get_units(5);
    lastWeightCheck = millis();
  }
  
  // Detect bottle if weight increased significantly
  float weightDifference = currentWeight - emptyWeight;
  bool weightDetected = weightDifference > 10.0; // 10g threshold
  
  bottleDetected = irDetected || weightDetected;
  
  if (bottleDetected) {
    // Estimate bottle count based on weight (assuming 25g per bottle)
    bottlesCount = max(1, (int)(weightDifference / 25.0));
    Serial.println("Bottle detected! Count: " + String(bottlesCount));
  }
}

void processBottleDeposit() {
  if (WiFi.status() != WL_CONNECTED) return;
  
  Serial.println("Processing bottle deposit...");
  
  HTTPClient http;
  http.begin(String(api_base) + deposit_url);
  http.addHeader("Content-Type", "application/json");
  
  DynamicJsonDocument doc(1024);
  doc["machine_id"] = machine_id;
  doc["user_qr"] = scannedQR;
  doc["bottles_detected"] = bottlesCount;
  doc["weight_grams"] = currentWeight - emptyWeight;
  doc["timestamp"] = getCurrentTimestamp();
  
  String jsonString;
  serializeJson(doc, jsonString);
  
  int httpResponseCode = http.POST(jsonString);
  
  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.println("Deposit response: " + response);
    
    DynamicJsonDocument responseDoc(1024);
    deserializeJson(responseDoc, response);
    
    if (responseDoc["success"]) {
      Serial.println("Bottle deposit successful!");
      
      // Success indication
      for (int i = 0; i < 3; i++) {
        digitalWrite(LED_PIN, HIGH);
        delay(200);
        digitalWrite(LED_PIN, LOW);
        delay(200);
      }
      
      // Reset for next transaction
      scannedQR = "";
      bottleDetected = false;
      bottlesCount = 0;
      
      // Recalibrate weight
      delay(2000);
      calibrateEmptyWeight();
    } else {
      Serial.println("Deposit failed: " + String(responseDoc["message"]));
    }
  } else {
    Serial.println("Error processing deposit: " + String(httpResponseCode));
  }
  
  http.end();
}

void updateMachineStatus() {
  if (WiFi.status() != WL_CONNECTED) return;
  
  HTTPClient http;
  http.begin(String(api_base) + status_url);
  http.addHeader("Content-Type", "application/json");
  
  // Calculate fill level based on weight (assuming 30L bin capacity)
  int fillLevel = min(100, (int)((currentWeight - emptyWeight) / 300.0 * 100));
  
  DynamicJsonDocument doc(1024);
  doc["machine_id"] = machine_id;
  doc["fill_level"] = fillLevel;
  doc["status"] = "active";
  doc["battery_level"] = 100; // Assuming power bank
  doc["temperature"] = 25; // Room temperature
  
  String jsonString;
  serializeJson(doc, jsonString);
  
  int httpResponseCode = http.POST(jsonString);
  
  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.println("Status update response: " + response);
  }
  
  http.end();
}

void openLid() {
  Serial.println("Opening lid...");
  lidServo.write(90); // Open position
}

void closeLid() {
  Serial.println("Closing lid...");
  lidServo.write(0); // Close position
}

String getCurrentTimestamp() {
  // Get current time from NTP server
  struct tm timeinfo;
  if (!getLocalTime(&timeinfo)) {
    return "2024-01-01 00:00:00";
  }
  
  char buffer[20];
  strftime(buffer, sizeof(buffer), "%Y-%m-%d %H:%M:%S", &timeinfo);
  return String(buffer);
}
