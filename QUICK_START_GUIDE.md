# MaBote.ph Smart Machine - Quick Start Guide
## Get Your Machine Running in 30 Minutes

---

## ⚡ QUICK SETUP (5 Steps)

### Step 1: Install Software (10 minutes)
1. Download Arduino IDE: https://www.arduino.cc/en/software
2. Install ESP32 Board Support:
   - File → Preferences → Add URL:
     ```
     https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json
     ```
   - Tools → Board → Boards Manager → Search "ESP32" → Install
3. Install Libraries:
   - Tools → Manage Libraries → Install:
     - ArduinoJson (by Benoit Blanchon)
     - LiquidCrystal_I2C (by Frank de Brabander)
     - ESP32Servo (by Kevin Harrington)
     - NewPing (by Tim Eckel)

### Step 2: Wire Components (10 minutes)
Follow the wiring diagram in `WIRING_DIAGRAM_COMPLETE.md`

**Quick Connections:**
- QR Scanner: TX→GPIO17, RX→GPIO16, VCC→5V, GND→GND
- LCD I2C: SDA→GPIO21, SCL→GPIO22, VCC→5V, GND→GND
- IR Sensor: OUT→GPIO19, VCC→3.3V, GND→GND
- Ultrasonic: TRIG→GPIO26, ECHO→GPIO25, VCC→5V, GND→GND
- Servo: Signal→GPIO18, Power→External 5V 3A, GND→GND
- Proximity: Signal→GPIO23, VCC→3.3V, GND→GND

### Step 3: Configure Code (5 minutes)
1. Open `mabote_smart_bin_complete.ino`
2. Update WiFi credentials:
   ```cpp
   const char* ssid = "YOUR_WIFI_SSID";
   const char* password = "YOUR_WIFI_PASSWORD";
   ```
3. Update API endpoint:
   ```cpp
   const char* api_base = "http://YOUR_SERVER_IP/mabote_api";
   ```
4. Update Machine ID:
   ```cpp
   const char* machine_id = "BIN001";
   ```

### Step 4: Upload Code (3 minutes)
1. Connect ESP32 via USB
2. Select Board: Tools → Board → ESP32 Dev Module
3. Select Port: Tools → Port → COMx
4. Click Upload
5. Wait for "Done uploading"

### Step 5: Test (2 minutes)
1. Open Serial Monitor (115200 baud)
2. Check for "WiFi connected"
3. Check LCD shows "Scan the QR code"
4. Test QR scanner with a QR code

---

## 🧪 COMPONENT TESTING

### Test Each Component Individually:

#### 1. LCD Test
```cpp
#include <LiquidCrystal_I2C.h>
LiquidCrystal_I2C lcd(0x27, 16, 2);
void setup() {
  lcd.init();
  lcd.backlight();
  lcd.print("LCD Test OK!");
}
void loop() {}
```

#### 2. QR Scanner Test
```cpp
void setup() {
  Serial.begin(115200);
  Serial2.begin(9600, SERIAL_8N1, 16, 17);
}
void loop() {
  if (Serial2.available()) {
    Serial.println(Serial2.readString());
  }
}
```

#### 3. Servo Test
```cpp
#include <ESP32Servo.h>
Servo servo;
void setup() {
  servo.attach(18);
  servo.write(90);
}
void loop() {
  servo.write(45); delay(1000);
  servo.write(135); delay(1000);
  servo.write(90); delay(1000);
}
```

#### 4. IR Sensor Test
```cpp
void setup() {
  Serial.begin(115200);
  pinMode(19, INPUT);
}
void loop() {
  int value = analogRead(19);
  Serial.println("IR: " + String(value));
  delay(500);
}
```

#### 5. Ultrasonic Test
```cpp
#include <NewPing.h>
NewPing sonar(26, 25, 200);
void setup() {
  Serial.begin(115200);
}
void loop() {
  int distance = sonar.ping_cm();
  Serial.println("Distance: " + String(distance) + "cm");
  delay(500);
}
```

---

## 🔧 COMMON ISSUES & FIXES

### Issue: ESP32 Won't Upload
**Fix:**
- Hold BOOT button while clicking Upload
- Release BOOT when "Connecting..." appears
- Try different USB cable/port

### Issue: LCD Blank
**Fix:**
- Check I2C address (try 0x27 or 0x3F)
- Verify SDA/SCL connections
- Check power supply

### Issue: QR Scanner Not Reading
**Fix:**
- Swap TX/RX connections
- Check baud rate (9600)
- Verify 5V power

### Issue: Servo Not Moving
**Fix:**
- Connect external 5V 3A power supply
- Verify PWM pin (GPIO 18)
- Check common ground

### Issue: WiFi Not Connecting
**Fix:**
- Verify SSID and password
- Use 2.4GHz network (not 5GHz)
- Check signal strength

---

## 📋 WORKFLOW CHECKLIST

### Initial Setup:
- [ ] Arduino IDE installed
- [ ] ESP32 board support installed
- [ ] Libraries installed
- [ ] Components wired correctly
- [ ] Code configured with WiFi/API
- [ ] Code uploaded successfully

### Testing:
- [ ] LCD displays messages
- [ ] QR scanner reads codes
- [ ] Servo moves correctly
- [ ] IR sensor detects objects
- [ ] Proximity sensor works
- [ ] Ultrasonic sensor measures distance
- [ ] WiFi connects
- [ ] API communication works

### Final Check:
- [ ] Complete workflow tested
- [ ] User verification works
- [ ] Bottle detection works
- [ ] Points added correctly
- [ ] Bin level detection works
- [ ] Auto-reset after 8 seconds works

---

## 🎯 EXPECTED BEHAVIOR

### Normal Operation:
1. LCD: "Scan the QR code"
2. User scans QR → LCD: "Verifying..."
3. LCD: "User Verified" → "Hello, [username]"
4. LCD: "Please Insert Bottle"
5. Bottle detected → LCD: "Bottle Accepted" or "Object Rejected"
6. Points added (if accepted)
7. LCD: "Please Insert Another Bottle"
8. Auto-reset after 8 seconds idle

### Bin Full:
- LCD: "Bin is Full"
- Machine stops accepting bottles
- Servo stays in center position

---

## 📞 NEED HELP?

1. Check Serial Monitor for error messages
2. Verify all connections
3. Test components individually
4. Review `COMPLETE_MABOTE_MACHINE_GUIDE.md` for detailed instructions
5. Check `WIRING_DIAGRAM_COMPLETE.md` for connection details

---

**Quick Start Guide - MaBote.ph Smart Recycling Machine**








