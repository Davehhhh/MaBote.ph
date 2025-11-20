# MaBote.ph Smart Recycling Machine - Complete Documentation

## 📚 Documentation Overview

This folder contains all the documentation and code needed to build and operate the MaBote.ph Smart Recycling Machine.

---

## 📁 Files Included

### 1. **COMPLETE_MABOTE_MACHINE_GUIDE.md**
   - **Complete setup guide** with all components
   - Step-by-step instructions
   - Component specifications
   - Calibration procedures
   - Troubleshooting guide
   - **START HERE** for detailed instructions

### 2. **WIRING_DIAGRAM_COMPLETE.md**
   - **Visual wiring diagrams** for all components
   - Pin connection tables
   - Power distribution diagrams
   - Breadboard layout
   - Connection checklists
   - **Use this** for hardware assembly

### 3. **mabote_smart_bin_complete.ino**
   - **Complete ESP32 code** with full workflow
   - User verification via API
   - Bottle detection and classification
   - Servo control for sorting
   - Bin level monitoring
   - LCD status messages
   - Auto-reset functionality
   - **Upload this** to your ESP32

### 4. **QUICK_START_GUIDE.md**
   - **30-minute quick setup** guide
   - Essential steps only
   - Component testing code
   - Common issues and fixes
   - **Use this** if you're experienced

---

## 🚀 Quick Start

### For Beginners:
1. Read: `COMPLETE_MABOTE_MACHINE_GUIDE.md`
2. Follow: `WIRING_DIAGRAM_COMPLETE.md` for connections
3. Upload: `mabote_smart_bin_complete.ino` to ESP32
4. Test: Follow testing procedures in guide

### For Experienced Users:
1. Read: `QUICK_START_GUIDE.md`
2. Wire: Follow pin assignments
3. Upload: `mabote_smart_bin_complete.ino`
4. Test: Verify all components work

---

## 🔧 Components Required

### Hardware:
- ESP32 DevKit V1 (connects directly to ALL components - no Arduino Uno needed)
- MH ET Live Scanner (QR Code Scanner)
- LCD I2C 16x2 Display
- IR Sensor LM393
- Ultrasonic Sensor HC-SR04
- Servo Motor MG996R
- Proximity Sensor LJC18A3-H-Z/BY
- Breadboard
- Jumper Wires
- Resistors (220Ω)
- Power Supply (5V 3A for servo)
- USB Cable

**Important:** ESP32 handles everything directly. Arduino Uno is NOT required.

### Software:
- Arduino IDE 1.8.19+
- ESP32 Board Support
- Required Libraries (see guide)

---

## 📋 Workflow

### Complete User Flow:

1. **Initial State**
   - LCD: "Scan the QR code"
   - Machine waits for QR scan

2. **QR Code Scanned**
   - Scanner reads QR code
   - LCD: "Verifying..."
   - API verifies user

3. **User Verified**
   - LCD: "User Verified"
   - LCD: "Hello, [username]"
   - LCD: "Please Insert Bottle"
   - Machine ready

4. **Bottle Detection**
   - Proximity sensor detects object
   - IR sensor confirms presence
   - System classifies bottle

5. **Bottle Sorting**
   - **Plastic Bottle:**
     - Servo tilts LEFT (45°)
     - LCD: "Bottle Accepted"
     - Points added to account
   - **Not Plastic:**
     - Servo tilts RIGHT (135°)
     - LCD: "Object Rejected"
     - No points added

6. **Continue or Reset**
   - LCD: "Please Insert Another Bottle"
   - If idle 8 seconds → Reset
   - Wait for next user

7. **Bin Full Detection**
   - Ultrasonic sensor monitors level
   - If full: LCD: "Bin is Full"
   - Machine stops accepting bottles

---

## 🔌 Pin Assignments

```
GPIO 16 → QR Scanner RX
GPIO 17 → QR Scanner TX
GPIO 18 → Servo Motor (PWM)
GPIO 19 → IR Sensor (OUT)
GPIO 21 → LCD SDA (I2C)
GPIO 22 → LCD SCL (I2C)
GPIO 23 → Proximity Sensor
GPIO 25 → Ultrasonic ECHO
GPIO 26 → Ultrasonic TRIG
GPIO 2  → Status LED (Optional)
```

---

## ⚙️ Configuration

### Before Uploading Code:

1. **WiFi Credentials:**
   ```cpp
   const char* ssid = "YOUR_WIFI_SSID";
   const char* password = "YOUR_WIFI_PASSWORD";
   ```

2. **API Endpoint:**
   ```cpp
   const char* api_base = "http://YOUR_SERVER_IP/mabote_api";
   ```

3. **Machine ID:**
   ```cpp
   const char* machine_id = "BIN001";
   ```

4. **Servo Positions** (adjust if needed):
   ```cpp
   const int SERVO_LEFT = 45;   // Accept position
   const int SERVO_RIGHT = 135; // Reject position
   ```

5. **Sensor Thresholds** (calibrate as needed):
   ```cpp
   const int EMPTY_DISTANCE = 50; // cm
   const int FULL_DISTANCE = 5;   // cm
   ```

---

## 🧪 Testing

### Test Each Component:

1. **LCD:** Should display "MaBote.ph" on startup
2. **QR Scanner:** Scan QR code, check Serial Monitor
3. **Servo:** Should move to center (90°)
4. **IR Sensor:** Place object, check Serial Monitor
5. **Proximity Sensor:** Place object, check readings
6. **Ultrasonic:** Point at wall, check distance
7. **WiFi:** Check Serial Monitor for "WiFi connected"
8. **API:** Test user verification

### Test Complete Workflow:

1. Scan QR code → Should verify user
2. Insert bottle → Should detect and classify
3. Check points → Should be added to account
4. Test bin full → Should stop accepting
5. Test auto-reset → Should reset after 8 seconds

---

## 🛠️ Troubleshooting

### Common Issues:

1. **ESP32 Won't Upload**
   - Hold BOOT button during upload
   - Try different USB cable

2. **LCD Blank**
   - Check I2C address (0x27 or 0x3F)
   - Verify SDA/SCL connections

3. **QR Scanner Not Working**
   - Swap TX/RX connections
   - Check baud rate (9600)

4. **Servo Not Moving**
   - Connect external 5V 3A power
   - Verify PWM pin connection

5. **WiFi Not Connecting**
   - Use 2.4GHz network
   - Check credentials

6. **API Not Responding**
   - Verify server IP address
   - Check API server is running
   - Test endpoint in browser

---

## 📊 Integration

### The machine integrates with:

- **MaBote.ph Mobile App** - User QR codes
- **MaBote.ph API Server** - User verification, points
- **MaBote.ph Database** - Transaction logging
- **Admin Dashboard** - Machine status monitoring
- **LGU Dashboard** - Community statistics

---

## 📞 Support

### Need Help?

1. Check Serial Monitor for error messages
2. Review troubleshooting sections in guides
3. Test components individually
4. Verify all connections
5. Check API server logs

### Documentation Files:

- **Detailed Guide:** `COMPLETE_MABOTE_MACHINE_GUIDE.md`
- **Wiring Diagram:** `WIRING_DIAGRAM_COMPLETE.md`
- **Quick Start:** `QUICK_START_GUIDE.md`
- **Code File:** `mabote_smart_bin_complete.ino`

---

## ✅ Checklist

### Before First Use:

- [ ] All components wired correctly
- [ ] Code uploaded to ESP32
- [ ] WiFi credentials configured
- [ ] API endpoint configured
- [ ] All components tested individually
- [ ] Complete workflow tested
- [ ] User verification working
- [ ] Points system working
- [ ] Bin level detection working
- [ ] Auto-reset working

---

## 🎯 Expected Results

After complete setup:

✅ QR code scanning works  
✅ User verification via API  
✅ Bottle detection (Proximity + IR)  
✅ Plastic bottle classification  
✅ Servo-controlled sorting  
✅ Points credit system  
✅ Bin level monitoring  
✅ LCD status messages  
✅ Auto-reset functionality  
✅ Full API integration  

---

**MaBote.ph Smart Recycling Machine - Complete Documentation**  
*Version 2.0 - Updated Component List*

