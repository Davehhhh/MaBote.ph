# MaBote.ph Smart Recycling Machine - Complete Guide
## Complete Wiring, Code, and Setup Instructions

---

## 📋 COMPONENTS LIST

### Hardware Components:
1. **ESP32 DevKit V1** - Main microcontroller (handles ALL components directly)
2. **MH ET Live Scanner** - QR Code Scanner Module
3. **LCD I2C 16x2** - Display module
4. **IR Sensor LM393** - Bottle detection sensor
5. **Ultrasonic Sensor HC-SR04** - Bin level detection
6. **Servo Motor MG996R** - PVC pipe tilting mechanism
7. **Proximity Sensor LJC18A3-H-Z/BY** - Object detection
8. **Breadboard** - For prototyping
9. **Jumper Wires** - Various lengths
10. **Resistors** - 220Ω for LED, 10kΩ pull-up
11. **Power Supply** - 5V 3A (for servo motor)
12. **USB Cable** - For ESP32 programming

**Note:** Arduino Uno is NOT needed. ESP32 connects directly to all components and sensors. ESP32 has enough GPIO pins and built-in WiFi to handle everything.

---

## 🔌 COMPLETE WIRING DIAGRAM

### ESP32 Pin Connections:

```
ESP32 DevKit V1 Pin Layout:
┌─────────────────────────────────────────┐
│  [USB]  [3.3V] [5V]  [GND] [EN] [GPIO0] │
│                                         │
│  [GPIO2] [GPIO4] [GPIO5] [GPIO16]      │
│  [GPIO17] [GPIO18] [GPIO19] [GPIO21]   │
│  [GPIO22] [GPIO23] [GPIO25] [GPIO26]   │
│  [GPIO27] [GPIO32] [GPIO33] [GPIO35]   │
│                                         │
│  [GND] [3.3V] [5V] [GND] [Vin] [GND]  │
└─────────────────────────────────────────┘
```

### Component Wiring:

#### 1. MH ET Live Scanner (QR Code Scanner):
```
MH ET Live Scanner:
┌─────────────────────────┐
│ VCC  GND  TX  RX  OUT   │
└─────────────────────────┘
    │    │    │   │   │
    │    │    │   │   └─── (Not used)
    │    │    │   └─────── GPIO 16 (ESP32 RX)
    │    │    └─────────── GPIO 17 (ESP32 TX)
    │    └──────────────── GND (ESP32)
    └────────────────────── 5V (ESP32)
```

#### 2. LCD I2C 16x2 Display:
```
LCD I2C Module:
┌─────────────────┐
│ VCC  GND  SDA SCL│
└─────────────────┘
    │    │    │  │
    │    │    │  └─── GPIO 22 (ESP32 SCL)
    │    │    └────── GPIO 21 (ESP32 SDA)
    │    └─────────── GND (ESP32)
    └──────────────── 5V (ESP32)
```

#### 3. IR Sensor LM393:
```
IR Sensor LM393:
┌─────────────────┐
│ VCC  GND  OUT   │
└─────────────────┘
    │    │    │
    │    │    └─── GPIO 19 (ESP32)
    │    └──────── GND (ESP32)
    └────────────── 3.3V (ESP32)
```

#### 4. Ultrasonic Sensor HC-SR04:
```
Ultrasonic Sensor:
┌─────────────────┐
│ VCC  GND  TRIG ECHO│
└─────────────────┘
    │    │    │   │
    │    │    │   └─── GPIO 25 (ESP32 ECHO)
    │    │    └─────── GPIO 26 (ESP32 TRIG)
    │    └──────────── GND (ESP32)
    └────────────────── 5V (ESP32)
```

#### 5. Servo Motor MG996R:
```
MG996R Servo Motor:
┌─────────────────┐
│ Red  Black  Yellow│
└─────────────────┘
    │     │     │
    │     │     └─── GPIO 18 (ESP32 PWM)
    │     └───────── GND (ESP32)
    └─────────────── 5V External Power Supply
                    (Use separate 5V 3A supply for servo)
```

#### 6. Proximity Sensor LJC18A3-H-Z/BY:
```
Proximity Sensor:
┌─────────────────┐
│ Brown  Blue  Black│
└─────────────────┘
    │     │     │
    │     │     └─── GPIO 23 (ESP32 Signal)
    │     └───────── GND (ESP32)
    └─────────────── 3.3V (ESP32)
```

#### 7. Status LED (Optional):
```
LED with 220Ω Resistor:
┌─────────────────┐
│ 220Ω Resistor   │
└─────────────────┘
    │
    └─── GPIO 2 (ESP32)
    │
    └─── LED Anode (+)
    │
    └─── LED Cathode (-) → GND (ESP32)
```

### Power Distribution:
```
Power Supply Setup:
┌─────────────────────────┐
│ 5V 3A Power Supply      │
└─────────────────────────┘
    │
    ├─── Servo Motor (Red wire)
    │
    └─── Common Ground (Black wire) → All GND pins

ESP32 USB Power:
┌─────────────────────────┐
│ USB Power (5V 2A)       │
└─────────────────────────┘
    │
    ├─── ESP32 USB Port
    ├─── MH ET Live Scanner (5V)
    ├─── LCD I2C (5V)
    └─── Ultrasonic Sensor (5V)

ESP32 3.3V Output:
    ├─── IR Sensor LM393 (3.3V)
    └─── Proximity Sensor (3.3V)
```

### Complete Pin Summary:
```
ESP32 GPIO Assignments:
├── GPIO 16 → MH ET Live Scanner RX
├── GPIO 17 → MH ET Live Scanner TX
├── GPIO 18 → Servo Motor MG996R (PWM)
├── GPIO 19 → IR Sensor LM393 (OUT)
├── GPIO 21 → LCD I2C SDA
├── GPIO 22 → LCD I2C SCL
├── GPIO 23 → Proximity Sensor LJC18A3 (Signal)
├── GPIO 25 → Ultrasonic Sensor ECHO
├── GPIO 26 → Ultrasonic Sensor TRIG
└── GPIO 2  → Status LED (Optional)

Power Connections:
├── 5V External → Servo Motor
├── 5V ESP32 → MH ET Live Scanner, LCD, Ultrasonic
├── 3.3V ESP32 → IR Sensor, Proximity Sensor
└── GND → All components (common ground)
```

---

## 💻 COMPLETE ESP32 CODE

See the file: `mabote_ph/mabote_smart_bin_complete.ino`

---

## 📝 STEP-BY-STEP SETUP INSTRUCTIONS

### STEP 1: Install Arduino IDE and ESP32 Board Support

1. **Download Arduino IDE** (1.8.19 or later)
   - Visit: https://www.arduino.cc/en/software
   - Install on your computer

2. **Install ESP32 Board Support:**
   - Open Arduino IDE
   - Go to: `File` → `Preferences`
   - Add this URL to "Additional Board Manager URLs":
     ```
     https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json
     ```
   - Go to: `Tools` → `Board` → `Boards Manager`
   - Search for "ESP32" and install "esp32 by Espressif Systems"

3. **Select ESP32 Board:**
   - Go to: `Tools` → `Board` → `ESP32 Arduino` → `ESP32 Dev Module`
   - Set Upload Speed: `115200`
   - Set CPU Frequency: `240MHz`
   - Set Flash Frequency: `80MHz`
   - Set Flash Size: `4MB (32Mb)`

### STEP 2: Install Required Libraries

Install these libraries via Library Manager (`Tools` → `Manage Libraries`):

1. **WiFi** (built-in)
2. **HTTPClient** (built-in)
3. **ArduinoJson** by Benoit Blanchon (Version 6.x)
4. **LiquidCrystal_I2C** by Frank de Brabander
5. **ESP32Servo** by Kevin Harrington
6. **NewPing** by Tim Eckel (for ultrasonic sensor)

### STEP 3: Hardware Assembly

#### A. Breadboard Setup:
1. Place ESP32 on breadboard
2. Connect power rails: Red = 5V, Blue = GND
3. Connect ESP32 5V to red rail
4. Connect ESP32 GND to blue rail

#### B. Component Connections (Follow wiring diagram above):

**MH ET Live Scanner:**
1. Connect VCC to 5V rail
2. Connect GND to GND rail
3. Connect TX to GPIO 17
4. Connect RX to GPIO 16

**LCD I2C:**
1. Connect VCC to 5V rail
2. Connect GND to GND rail
3. Connect SDA to GPIO 21
4. Connect SCL to GPIO 22

**IR Sensor LM393:**
1. Connect VCC to 3.3V (ESP32)
2. Connect GND to GND rail
3. Connect OUT to GPIO 19

**Ultrasonic Sensor:**
1. Connect VCC to 5V rail
2. Connect GND to GND rail
3. Connect TRIG to GPIO 26
4. Connect ECHO to GPIO 25

**Servo Motor:**
1. Connect Red wire to external 5V 3A power supply
2. Connect Black wire to GND rail
3. Connect Yellow wire to GPIO 18

**Proximity Sensor:**
1. Connect Brown wire to 3.3V (ESP32)
2. Connect Blue wire to GND rail
3. Connect Black wire to GPIO 23

### STEP 4: Upload Code

1. **Open the code file:** `mabote_smart_bin_complete.ino`
2. **Configure WiFi credentials:**
   ```cpp
   const char* ssid = "YOUR_WIFI_SSID";
   const char* password = "YOUR_WIFI_PASSWORD";
   ```
3. **Configure API endpoint:**
   ```cpp
   const char* api_base = "http://192.168.254.128/mabote_api";
   ```
4. **Configure Machine ID:**
   ```cpp
   const char* machine_id = "BIN001";
   ```
5. **Upload to ESP32:**
   - Connect ESP32 via USB
   - Select correct COM port: `Tools` → `Port`
   - Click Upload button
   - Wait for "Done uploading" message

### STEP 5: Testing Each Component

#### Test 1: LCD Display
- Upload code and check if LCD shows "MaBote.ph"
- If blank, adjust I2C address (try 0x27 or 0x3F)

#### Test 2: QR Scanner
- Open Serial Monitor (115200 baud)
- Scan a QR code
- Should see QR data in Serial Monitor

#### Test 3: IR Sensor
- Place object near sensor
- Check Serial Monitor for detection messages

#### Test 4: Proximity Sensor
- Place object near sensor
- Check Serial Monitor for proximity readings

#### Test 5: Ultrasonic Sensor
- Point sensor at wall
- Check Serial Monitor for distance readings

#### Test 6: Servo Motor
- Servo should move to center position (90°)
- Test left tilt (45°) and right tilt (135°)

### STEP 6: Calibration

#### Servo Motor Calibration:
1. **Center Position (Neutral):** 90°
2. **Left Tilt (Accept):** 45° (adjust as needed)
3. **Right Tilt (Reject):** 135° (adjust as needed)

#### Ultrasonic Sensor Calibration:
1. Measure empty bin height
2. Measure full bin height
3. Update in code:
   ```cpp
   const int EMPTY_DISTANCE = 50; // cm (adjust based on your bin)
   const int FULL_DISTANCE = 5;   // cm (adjust based on your bin)
   ```

#### Sensor Thresholds:
1. **IR Sensor:** Adjust sensitivity if needed
2. **Proximity Sensor:** Adjust detection distance

### STEP 7: API Integration

1. **Ensure API server is running:**
   - XAMPP should be running
   - Database should be set up
   - API endpoints should be accessible

2. **Test API connection:**
   - Check Serial Monitor for "WiFi connected"
   - Check for "Machine registered successfully"
   - Test QR code scan and user verification

3. **Test bottle deposit:**
   - Scan QR code
   - Insert bottle
   - Check if points are added to user account

### STEP 8: Final Assembly

1. **Mount components in enclosure:**
   - Secure ESP32 to mounting plate
   - Mount LCD on front panel
   - Mount QR scanner below LCD
   - Mount sensors in appropriate locations
   - Mount servo motor for PVC pipe mechanism

2. **Wire management:**
   - Route wires neatly
   - Use cable ties
   - Label connections
   - Ensure no loose connections

3. **Power supply:**
   - Connect external 5V 3A for servo
   - Connect ESP32 USB power
   - Test power stability

4. **Final testing:**
   - Test complete workflow
   - Test error handling
   - Test bin full detection
   - Test auto-reset after 8 seconds

---

## 🔄 WORKFLOW EXPLANATION

### Complete User Flow:

1. **Initial State:**
   - LCD: "Scan the QR code"
   - Machine waits for QR code scan

2. **QR Code Scanned:**
   - MH ET Live Scanner reads QR code
   - LCD: "Verifying..."
   - ESP32 sends QR to API for user verification
   - API checks if user exists and is active

3. **User Verified:**
   - LCD: "User Verified"
   - LCD: "Hello, [username]"
   - LCD: "Please Insert Bottle"
   - Machine ready to accept bottles

4. **Bottle Detection:**
   - Proximity Sensor detects object approaching
   - IR Sensor LM393 confirms object presence
   - Both sensors must detect for bottle acceptance

5. **Bottle Classification:**
   - System analyzes sensor readings
   - If plastic bottle detected:
     - Servo tilts LEFT (45°) → Accepted storage
     - LCD: "Bottle Accepted"
     - Points added to user account
   - If not plastic bottle:
     - Servo tilts RIGHT (135°) → Rejected storage
     - LCD: "Object Rejected"
     - No points added

6. **Points Credit:**
   - API call to add points
   - Points synchronized with database
   - User receives notification in app

7. **Continue or Reset:**
   - LCD: "Please Insert Another Plastic Bottle"
   - If idle for 8 seconds:
     - System resets
     - LCD: "Scan the QR code"
     - Wait for next user

8. **Bin Full Detection:**
   - Ultrasonic sensor checks bin level
   - If distance < FULL_DISTANCE:
     - LCD: "Bin is Full"
     - Machine stops accepting bottles
     - Servo remains in center position
     - Alert sent to admin

---

## 🛠️ TROUBLESHOOTING GUIDE

### Problem: ESP32 Not Connecting
**Solutions:**
- Hold BOOT button while uploading
- Try different USB cable
- Check COM port selection
- Install ESP32 drivers

### Problem: LCD Not Displaying
**Solutions:**
- Check I2C address (scan with I2C scanner)
- Verify SDA/SCL connections
- Check power supply (5V)
- Adjust contrast potentiometer on I2C module

### Problem: QR Scanner Not Reading
**Solutions:**
- Verify TX/RX connections (swap if needed)
- Check baud rate (9600)
- Ensure 5V power supply
- Test with known QR codes

### Problem: Servo Not Moving
**Solutions:**
- Check external 5V 3A power supply
- Verify PWM pin connection (GPIO 18)
- Test servo with simple code
- Check servo specifications

### Problem: Sensors Not Detecting
**Solutions:**
- Verify power connections (3.3V or 5V)
- Check signal pin connections
- Test sensors individually
- Adjust detection thresholds

### Problem: WiFi Not Connecting
**Solutions:**
- Verify SSID and password
- Check WiFi signal strength
- Ensure 2.4GHz network (ESP32 doesn't support 5GHz)
- Check router settings

### Problem: API Connection Failed
**Solutions:**
- Verify API server is running
- Check IP address in code
- Test API endpoint in browser
- Check firewall settings

### Problem: Bin Full Detection Not Working
**Solutions:**
- Calibrate ultrasonic sensor distances
- Check sensor mounting angle
- Verify TRIG/ECHO connections
- Test with known distances

---

## 📊 SENSOR CALIBRATION VALUES

### Default Thresholds (Adjust as needed):

```cpp
// IR Sensor LM393
const int IR_THRESHOLD = 500;  // ADC reading threshold

// Proximity Sensor
const int PROXIMITY_THRESHOLD = 100;  // Detection distance in mm

// Ultrasonic Sensor
const int EMPTY_DISTANCE = 50;  // cm (bin empty)
const int FULL_DISTANCE = 5;    // cm (bin full)

// Servo Positions
const int SERVO_CENTER = 90;   // Neutral position
const int SERVO_LEFT = 45;     // Accept (tilt left)
const int SERVO_RIGHT = 135;   // Reject (tilt right)
```

---

## 🔐 SECURITY CONSIDERATIONS

1. **WiFi Security:**
   - Use WPA2 encryption
   - Change default router password
   - Use strong WiFi password

2. **API Security:**
   - Use HTTPS in production
   - Implement API authentication
   - Validate all inputs

3. **Physical Security:**
   - Secure enclosure
   - Protect wiring
   - Prevent tampering

---

## 📱 INTEGRATION WITH MABOTE.PH APP

The machine integrates with:
- **User Authentication:** QR code verification
- **Points System:** Automatic point addition
- **Transaction Log:** All deposits recorded
- **Notifications:** Users notified of deposits
- **Admin Dashboard:** Real-time machine status
- **LGU Dashboard:** Community statistics

---

## 🎯 EXPECTED RESULTS

After complete setup, you should have:
- ✅ Working QR code scanning
- ✅ User verification via API
- ✅ Bottle detection (Proximity + IR sensors)
- ✅ Plastic bottle classification
- ✅ Servo-controlled sorting mechanism
- ✅ Points credit system
- ✅ Bin level monitoring
- ✅ LCD status messages
- ✅ Auto-reset functionality
- ✅ Full API integration

---

## 📞 SUPPORT

For issues or questions:
- Check Serial Monitor for error messages
- Verify all connections
- Test components individually
- Review API server logs
- Check database connectivity

---

**MaBote.ph Smart Recycling Machine - Complete Setup Guide**
*Version 2.0 - Updated Component List*

