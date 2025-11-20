/*
 * Complete Component Testing Code
 * Test all components together
 */

#include <SoftwareSerial.h>
#include <ESP32Servo.h>
#include <HX711.h>
#include <LiquidCrystal_I2C.h>

// Pin definitions
#define LED_PIN 2
#define SERVO_PIN 18
#define IR_PIN 19
#define HX711_DT_PIN 21
#define HX711_SCK_PIN 22
#define QR_RX_PIN 16
#define QR_TX_PIN 17

// Objects
SoftwareSerial qrSerial(QR_RX_PIN, QR_TX_PIN);
Servo myServo;
HX711 scale;
LiquidCrystal_I2C lcd(0x27, 16, 2);

void setup() {
  Serial.begin(115200);
  
  // Initialize pins
  pinMode(LED_PIN, OUTPUT);
  pinMode(IR_PIN, INPUT);
  
  // Initialize components
  qrSerial.begin(9600);
  myServo.attach(SERVO_PIN);
  scale.begin(HX711_DT_PIN, HX711_SCK_PIN);
  lcd.init();
  lcd.backlight();
  
  // Calibrate load cell
  scale.set_scale(1000.0);
  scale.tare();
  
  Serial.println("✅ All components initialized!");
  
  // Display welcome message
  lcd.setCursor(0, 0);
  lcd.print("MaBote.ph");
  lcd.setCursor(0, 1);
  lcd.print("Testing...");
}

void loop() {
  // Test LED (blink every 2 seconds)
  static unsigned long lastLedTime = 0;
  if (millis() - lastLedTime > 2000) {
    digitalWrite(LED_PIN, !digitalRead(LED_PIN));
    lastLedTime = millis();
  }
  
  // Test servo (cycle every 6 seconds)
  static unsigned long lastServoTime = 0;
  static int servoPos = 0;
  if (millis() - lastServoTime > 6000) {
    myServo.write(servoPos);
    servoPos = (servoPos + 90) % 180;
    lastServoTime = millis();
    Serial.println("🔧 Servo position: " + String(servoPos));
  }
  
  // Test IR sensor
  int irValue = digitalRead(IR_PIN);
  if (irValue == LOW) {
    Serial.println("🔴 IR: Object detected!");
  }
  
  // Test load cell
  float weight = scale.get_units(5);
  if (weight > 10) { // If weight > 10g
    Serial.println("⚖️ Weight: " + String(weight) + "g");
  }
  
  // Test QR scanner
  if (qrSerial.available()) {
    String qrData = qrSerial.readString();
    qrData.trim();
    Serial.println("📱 QR Code: " + qrData);
    
    // Update LCD
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("QR Code:");
    lcd.setCursor(0, 1);
    lcd.print(qrData);
  }
  
  delay(100);
}







