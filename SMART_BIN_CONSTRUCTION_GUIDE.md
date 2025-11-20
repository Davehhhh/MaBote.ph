# SMART BIN CONSTRUCTION GUIDE
# Based on your 3D design concept

## 📋 MATERIALS LIST

### Enclosure Materials:
- **Acrylic sheets (6mm thick)** OR **Plywood (12mm)**
- **Dimensions needed:**
  - Front Panel: 40cm × 60cm
  - Back Panel: 40cm × 60cm  
  - Left Side: 30cm × 60cm
  - Right Side: 30cm × 60cm
  - Top Panel: 40cm × 30cm
  - Bottom Panel: 40cm × 30cm

### Hardware Components (You have these):
- ESP32 DevKit V1
- GM65 UART QR Code Scanner Module
- MG996R Servo Motor
- TCRT5000 IR Proximity Sensor
- Load Cell + HX711 Module
- 5V 2A Power Bank
- Micro USB Cable
- Breadboard
- Jumper Wires
- Resistors (220Ω)

### Additional Materials Needed:
- **LCD Display:** 16×2 I2C LCD (4cm × 2cm)
- **Hinges:** 2 small hinges for lid
- **Screws:** M3 screws and nuts
- **Mounting brackets:** For components
- **Wire connectors:** JST connectors
- **Clear acrylic:** For QR scanner cover
- **Rubber feet:** For base stability

## 🔧 STEP-BY-STEP CONSTRUCTION

### STEP 1: ENCLOSURE CUTTING
1. **Mark cutting lines** on acrylic/plywood
2. **Cut panels** using jigsaw or laser cutter
3. **Sand edges** for smooth finish
4. **Drill mounting holes** (3mm diameter)

### STEP 2: PANEL ASSEMBLY
1. **Start with base:** Attach bottom panel to left/right sides
2. **Add front/back panels:** Secure with screws
3. **Install top panel:** Create angled top (15° slope)
4. **Test fit:** Ensure all panels align properly

### STEP 3: COMPONENT MOUNTING

#### A. ESP32 Controller:
- **Location:** Right interior wall
- **Mounting:** Use standoffs (20mm height)
- **Orientation:** Vertical mounting
- **Access:** Ensure USB port accessible

#### B. LCD Display:
- **Location:** Front panel, top center
- **Cutout:** 8cm × 3cm rectangular opening
- **Mounting:** Flush mount with front panel
- **Wiring:** I2C connection (SDA/SCL)

#### C. GM65 QR Scanner:
- **Location:** Front panel, below LCD
- **Cutout:** 6cm × 4cm rectangular opening
- **Mounting:** Secure with brackets
- **Protection:** Add clear acrylic cover

#### D. Servo Motor:
- **Location:** Top section, near bottle entry
- **Mounting:** Secure bracket to internal frame
- **Function:** Control bottle slot gate
- **Linkage:** Connect to gate mechanism

#### E. IR Sensors:
- **Primary:** Near bottle entry (top)
- **Secondary:** Above weighing platform
- **Mounting:** Small holes (5mm diameter)
- **Positioning:** Ensure proper detection angles

#### F. Load Cell + HX711:
- **Location:** Bottom center
- **Mounting:** Secure load cell to base
- **Platform:** Create weighing surface above
- **Protection:** Cover with thin acrylic

#### G. Power Bank:
- **Location:** Right interior wall
- **Mounting:** Vertical bracket mount
- **Access:** Ensure charging port accessible
- **Security:** Prevent movement during operation

### STEP 4: WIRING CONNECTIONS

#### Wire Routing:
```
ESP32 GPIO Connections:
├── GPIO 16 → GM65 RX (Yellow wire)
├── GPIO 17 → GM65 TX (Green wire)
├── GPIO 18 → Servo PWM (Orange wire)
├── GPIO 19 → IR Sensor (Blue wire)
├── GPIO 21 → HX711 DT (Purple wire)
├── GPIO 22 → HX711 SCK (Brown wire)
├── GPIO 4  → LCD SDA (Red wire)
├── GPIO 5  → LCD SCL (Black wire)
└── GPIO 2  → Status LED (White wire)
```

#### Power Distribution:
- **ESP32:** USB power from power bank
- **Servo Motor:** 5V from power bank (with voltage regulator)
- **GM65 Scanner:** 5V from power bank
- **LCD Display:** 5V from ESP32
- **Sensors:** 3.3V from ESP32

### STEP 5: MECHANICAL SYSTEMS

#### Bottle Slot Mechanism:
1. **Create circular opening** (8cm diameter)
2. **Install servo-controlled gate**
3. **Add IR sensor** for bottle detection
4. **Test smooth bottle entry**

#### Weighing Platform:
1. **Mount load cell** to base panel
2. **Create platform** above load cell
3. **Ensure level surface** for bottles
4. **Calibrate weight readings**

#### Lid Control System:
1. **Install servo motor** bracket
2. **Connect servo arm** to lid mechanism
3. **Test opening/closing** motion
4. **Add safety stops** to prevent over-rotation

### STEP 6: USER INTERFACE

#### Front Panel Layout:
```
┌─────────────────────────────────┐
│        [LCD Display]            │
│    "Please scan your QR"        │
│                                 │
│        [QR Scanner]             │
│      (GM65 Module)              │
│                                 │
│    [Status LED]                 │
└─────────────────────────────────┘
```

#### Display Messages:
- **Idle:** "Please scan your QR"
- **QR Detected:** "QR Code Detected"
- **Lid Opening:** "Opening lid..."
- **Insert Bottles:** "Please insert bottles now"
- **Processing:** "Processing deposit..."
- **Success:** "Deposit successful!"
- **Error:** "Error occurred"

### STEP 7: TESTING & CALIBRATION

#### Component Testing:
1. **ESP32:** Upload code and test WiFi
2. **QR Scanner:** Test code reading
3. **Servo Motor:** Test lid opening/closing
4. **IR Sensors:** Test bottle detection
5. **Load Cell:** Calibrate weight readings
6. **LCD Display:** Test message display

#### System Integration:
1. **Power on** all components
2. **Test QR scanning** workflow
3. **Test bottle detection** and weighing
4. **Test API communication**
5. **Test error handling**

### STEP 8: FINAL ASSEMBLY

#### Final Steps:
1. **Secure all components** with proper mounting
2. **Route wires** neatly with cable management
3. **Test full system** operation
4. **Add safety features** (emergency stop, etc.)
5. **Apply final touches** (labels, instructions)

## 🎯 EXPECTED WORKFLOW

1. **User approaches** machine
2. **LCD displays** "Please scan your QR"
3. **User scans** QR code from MaBote.ph app
4. **System detects** QR code
5. **Lid opens** automatically
6. **User inserts** bottles
7. **System detects** bottles (IR + weight)
8. **System processes** transaction
9. **Points added** to user account
10. **Lid closes** automatically
11. **Success message** displayed

## 🔧 TROUBLESHOOTING

### Common Issues:
- **QR Scanner not working:** Check wiring and power
- **Servo not moving:** Check PWM signal and power
- **Load cell inaccurate:** Recalibrate with known weights
- **WiFi connection failed:** Check credentials and signal
- **LCD not displaying:** Check I2C connections

### Calibration Steps:
1. **Load Cell:** Use known weights (100g, 500g, 1000g)
2. **IR Sensors:** Test with different bottle sizes
3. **Servo Motor:** Adjust opening/closing angles
4. **QR Scanner:** Test with various QR codes

## 📱 INTEGRATION WITH MABOTE.PH

The machine will:
- **Connect to existing** MaBote.ph database
- **Use same user accounts** and QR codes
- **Process transactions** in real-time
- **Update LGU admin** dashboard
- **Send notifications** to users
- **Track environmental** impact metrics

This completes your smart bin construction guide!







