# BEGINNER'S STEP-BY-STEP GUIDE
# Building Your First Smart Bin Machine

## 📋 COMPONENT CHECKLIST

### ✅ Hardware Components (You have these):
- [ ] ESP32 DevKit V1
- [ ] GM65 UART QR Code Scanner Module
- [ ] MG996R Servo Motor
- [ ] TCRT5000 IR Proximity Sensor
- [ ] Load Cell + HX711 Module
- [ ] 5V 2A Power Bank
- [ ] Micro USB Cable
- [ ] Breadboard
- [ ] Jumper Wires (Male-Male, Male-Female)
- [ ] Resistors (220Ω)

### 📦 Additional Components Needed:
- [ ] LCD Display 16x2 I2C (4cm × 2cm)
- [ ] Small breadboard (for testing)
- [ ] LED (any color)
- [ ] Resistor 220Ω (for LED)
- [ ] Jumper wires (more if needed)

## 🔌 STEP 2: BASIC WIRING SETUP

### A. ESP32 Power Connection:
```
ESP32 DevKit V1:
├── USB Port → Connect to Power Bank
├── 3.3V Pin → For sensors
├── 5V Pin → For servo motor
└── GND Pin → Common ground
```

### B. Component Connections:

#### 1. GM65 QR Scanner:
```
GM65 QR Scanner → ESP32:
├── VCC (Red) → 5V
├── GND (Black) → GND
├── TX (Yellow) → GPIO 16
└── RX (Green) → GPIO 17
```

#### 2. MG996R Servo Motor:
```
MG996R Servo → ESP32:
├── Red Wire → 5V
├── Black Wire → GND
└── Yellow Wire → GPIO 18
```

#### 3. TCRT5000 IR Sensor:
```
TCRT5000 IR Sensor → ESP32:
├── VCC → 3.3V
├── GND → GND
└── OUT → GPIO 19
```

#### 4. HX711 Load Cell:
```
HX711 Module → ESP32:
├── VCC → 3.3V
├── GND → GND
├── DT → GPIO 21
└── SCK → GPIO 22
```

#### 5. LCD Display (I2C):
```
LCD Display → ESP32:
├── VCC → 5V
├── GND → GND
├── SDA → GPIO 4
└── SCL → GPIO 5
```

#### 6. Status LED:
```
LED → ESP32:
├── Anode (+) → GPIO 2
├── Cathode (-) → GND (through 220Ω resistor)
```

## 🧪 STEP 3: TESTING EACH COMPONENT

### Test 1: ESP32 Basic Test
```cpp
// Upload this code first to test ESP32
void setup() {
  Serial.begin(115200);
  Serial.println("ESP32 is working!");
}

void loop() {
  Serial.println("ESP32 running...");
  delay(1000);
}
```

### Test 2: LED Test
```cpp
// Test LED connection
#define LED_PIN 2

void setup() {
  pinMode(LED_PIN, OUTPUT);
  Serial.begin(115200);
}

void loop() {
  digitalWrite(LED_PIN, HIGH);
  Serial.println("LED ON");
  delay(1000);
  
  digitalWrite(LED_PIN, LOW);
  Serial.println("LED OFF");
  delay(1000);
}
```

### Test 3: Servo Motor Test
```cpp
// Test servo motor
#include <ESP32Servo.h>

Servo myServo;
#define SERVO_PIN 18

void setup() {
  myServo.attach(SERVO_PIN);
  Serial.begin(115200);
}

void loop() {
  myServo.write(0);    // Close position
  Serial.println("Servo: 0 degrees");
  delay(2000);
  
  myServo.write(90);    // Open position
  Serial.println("Servo: 90 degrees");
  delay(2000);
  
  myServo.write(180);   // Full open
  Serial.println("Servo: 180 degrees");
  delay(2000);
}
```

### Test 4: IR Sensor Test
```cpp
// Test IR sensor
#define IR_PIN 19

void setup() {
  pinMode(IR_PIN, INPUT);
  Serial.begin(115200);
}

void loop() {
  int irValue = digitalRead(IR_PIN);
  Serial.println("IR Sensor: " + String(irValue));
  delay(500);
}
```

### Test 5: Load Cell Test
```cpp
// Test load cell
#include "HX711.h"

#define HX711_DT_PIN 21
#define HX711_SCK_PIN 22

HX711 scale;

void setup() {
  Serial.begin(115200);
  scale.begin(HX711_DT_PIN, HX711_SCK_PIN);
  scale.set_scale(1000.0); // Adjust this value
  scale.tare();
  Serial.println("Load cell ready!");
}

void loop() {
  float weight = scale.get_units(5);
  Serial.println("Weight: " + String(weight) + " grams");
  delay(1000);
}
```

### Test 6: QR Scanner Test
```cpp
// Test QR scanner
#include <SoftwareSerial.h>

SoftwareSerial qrSerial(16, 17); // RX, TX

void setup() {
  Serial.begin(115200);
  qrSerial.begin(9600);
  Serial.println("QR Scanner ready!");
}

void loop() {
  if (qrSerial.available()) {
    String qrData = qrSerial.readString();
    qrData.trim();
    Serial.println("QR Code: " + qrData);
  }
  delay(100);
}
```

### Test 7: LCD Display Test
```cpp
// Test LCD display
#include <LiquidCrystal_I2C.h>

LiquidCrystal_I2C lcd(0x27, 16, 2);

void setup() {
  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("Hello World!");
  lcd.setCursor(0, 1);
  lcd.print("MaBote.ph");
}

void loop() {
  // LCD test - display will show static text
  delay(1000);
}
```

## 🔧 STEP 4: ASSEMBLY ORDER

### Phase 1: Basic Setup
1. **Connect ESP32 to power bank**
2. **Upload basic test code**
3. **Test each component individually**
4. **Fix any connection issues**

### Phase 2: Component Integration
1. **Connect all components to ESP32**
2. **Upload integrated test code**
3. **Test all components together**
4. **Calibrate sensors**

### Phase 3: Full System
1. **Upload complete smart bin code**
2. **Test full workflow**
3. **Connect to WiFi**
4. **Test API communication**

## 🎯 STEP 5: COMPLETE TESTING CODE

```cpp
// Complete testing code for all components
#include <WiFi.h>
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
  
  // Initialize components
  pinMode(LED_PIN, OUTPUT);
  pinMode(IR_PIN, INPUT);
  
  qrSerial.begin(9600);
  myServo.attach(SERVO_PIN);
  scale.begin(HX711_DT_PIN, HX711_SCK_PIN);
  lcd.init();
  lcd.backlight();
  
  // Calibrate load cell
  scale.set_scale(1000.0);
  scale.tare();
  
  Serial.println("All components initialized!");
  
  // Display welcome message
  lcd.setCursor(0, 0);
  lcd.print("MaBote.ph");
  lcd.setCursor(0, 1);
  lcd.print("Testing...");
}

void loop() {
  // Test LED
  digitalWrite(LED_PIN, HIGH);
  delay(500);
  digitalWrite(LED_PIN, LOW);
  delay(500);
  
  // Test servo
  myServo.write(0);
  delay(1000);
  myServo.write(90);
  delay(1000);
  
  // Test IR sensor
  int irValue = digitalRead(IR_PIN);
  Serial.println("IR: " + String(irValue));
  
  // Test load cell
  float weight = scale.get_units(5);
  Serial.println("Weight: " + String(weight));
  
  // Test QR scanner
  if (qrSerial.available()) {
    String qrData = qrSerial.readString();
    qrData.trim();
    Serial.println("QR: " + qrData);
    
    // Update LCD
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("QR Code:");
    lcd.setCursor(0, 1);
    lcd.print(qrData);
  }
  
  delay(1000);
}
```

## 🚨 TROUBLESHOOTING GUIDE

### Common Issues:

#### 1. ESP32 Not Connecting:
- Check USB cable
- Press and hold BOOT button while uploading
- Try different USB port

#### 2. Components Not Working:
- Check power connections (5V/3.3V)
- Verify ground connections
- Check pin assignments

#### 3. Load Cell Issues:
- Adjust calibration factor
- Check wiring (DT/SCK)
- Ensure stable power

#### 4. QR Scanner Problems:
- Check baud rate (9600)
- Verify TX/RX connections
- Test with known QR codes

#### 5. Servo Not Moving:
- Check power supply (5V)
- Verify PWM pin connection
- Test with simple code

## 📱 STEP 6: WIFI SETUP

```cpp
// WiFi connection test
const char* ssid = "YOUR_WIFI_NAME";
const char* password = "YOUR_WIFI_PASSWORD";

void setup() {
  Serial.begin(115200);
  
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.println("Connecting to WiFi...");
  }
  
  Serial.println("WiFi connected!");
  Serial.println("IP address: " + WiFi.localIP().toString());
}
```

## 🎯 NEXT STEPS

1. **Start with ESP32 basic test**
2. **Test each component individually**
3. **Fix any connection issues**
4. **Upload complete testing code**
5. **Test all components together**
6. **Connect to WiFi**
7. **Upload final smart bin code**

This guide will help you build your smart bin step by step!







