# VISUAL WIRING DIAGRAM
# ESP32 Smart Bin Component Connections

## 🔌 EXACT WIRING CONNECTIONS

### ESP32 DevKit V1 Pin Layout:
```
ESP32 DevKit V1:
┌─────────────────────────────────┐
│  [USB]  [3.3V] [5V]  [GND]      │
│                                 │
│  [GPIO2] [GPIO4] [GPIO5]        │
│  [GPIO16] [GPIO17] [GPIO18]     │
│  [GPIO19] [GPIO21] [GPIO22]     │
│                                 │
│  [GND] [3.3V] [5V] [GND]        │
└─────────────────────────────────┘
```

### Component Connections:

#### 1. GM65 QR Scanner:
```
GM65 QR Scanner Module:
┌─────────────────┐
│ VCC  GND  TX RX │
└─────────────────┘
    │    │    │  │
    │    │    │  └─── GPIO 17 (ESP32)
    │    │    └────── GPIO 16 (ESP32)
    │    └─────────── GND (ESP32)
    └──────────────── 5V (ESP32)
```

#### 2. MG996R Servo Motor:
```
MG996R Servo Motor:
┌─────────────────┐
│ Red  Black  Yel │
└─────────────────┘
    │     │     │
    │     │     └─── GPIO 18 (ESP32)
    │     └───────── GND (ESP32)
    └─────────────── 5V (ESP32)
```

#### 3. TCRT5000 IR Sensor:
```
TCRT5000 IR Sensor:
┌─────────────────┐
│ VCC  GND  OUT   │
└─────────────────┘
    │    │    │
    │    │    └─── GPIO 19 (ESP32)
    │    └──────── GND (ESP32)
    └────────────── 3.3V (ESP32)
```

#### 4. HX711 Load Cell Module:
```
HX711 Module:
┌─────────────────┐
│ VCC  GND  DT SCK│
└─────────────────┘
    │    │    │  │
    │    │    │  └─── GPIO 22 (ESP32)
    │    │    └────── GPIO 21 (ESP32)
    │    └─────────── GND (ESP32)
    └──────────────── 3.3V (ESP32)
```

#### 5. LCD Display (I2C):
```
LCD Display 16x2:
┌─────────────────┐
│ VCC  GND  SDA SCL│
└─────────────────┘
    │    │    │  │
    │    │    │  └─── GPIO 5 (ESP32)
    │    │    └────── GPIO 4 (ESP32)
    │    └─────────── GND (ESP32)
    └──────────────── 5V (ESP32)
```

#### 6. Status LED:
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

## 🔋 POWER CONNECTIONS

### Power Bank Connection:
```
Power Bank (5V 2A):
┌─────────────────┐
│ USB Output      │
└─────────────────┘
    │
    └─── Micro USB Cable → ESP32 USB Port
```

### Power Distribution:
```
ESP32 Power Distribution:
├── 5V Pin → Servo Motor, QR Scanner, LCD
├── 3.3V Pin → IR Sensor, HX711 Module
└── GND Pin → All components (common ground)
```

## 🧪 TESTING ORDER

### Step 1: Basic ESP32 Test
1. Connect ESP32 to power bank
2. Upload `test_esp32_basic.ino`
3. Open Serial Monitor (115200 baud)
4. Should see "ESP32 is working!"

### Step 2: LED Test
1. Connect LED to GPIO 2 with 220Ω resistor
2. Upload `test_led.ino`
3. LED should blink every second
4. Serial Monitor should show "LED ON/OFF"

### Step 3: Servo Test
1. Connect servo: Red→5V, Black→GND, Yellow→GPIO 18
2. Upload `test_servo.ino`
3. Servo should move: 0° → 90° → 180°
4. Serial Monitor should show position changes

### Step 4: IR Sensor Test
1. Connect IR sensor: VCC→3.3V, GND→GND, OUT→GPIO 19
2. Upload `test_ir_sensor.ino`
3. Place object near sensor
4. Serial Monitor should show "Object DETECTED!"

### Step 5: Load Cell Test
1. Connect HX711: VCC→3.3V, GND→GND, DT→GPIO 21, SCK→GPIO 22
2. Upload `test_load_cell.ino`
3. Place objects on load cell
4. Serial Monitor should show weight readings

### Step 6: QR Scanner Test
1. Connect GM65: VCC→5V, GND→GND, TX→GPIO 16, RX→GPIO 17
2. Upload `test_qr_scanner.ino`
3. Scan QR codes
4. Serial Monitor should show QR data

### Step 7: LCD Display Test
1. Connect LCD: VCC→5V, GND→GND, SDA→GPIO 4, SCL→GPIO 5
2. Upload `test_lcd.ino`
3. LCD should display "MaBote.ph" and "LCD Test OK!"

### Step 8: Complete System Test
1. Connect ALL components
2. Upload `test_all_components.ino`
3. All components should work together
4. Serial Monitor should show all sensor readings

## 🚨 TROUBLESHOOTING

### Common Issues:

#### ESP32 Not Connecting:
- Check USB cable connection
- Press and hold BOOT button while uploading
- Try different USB port
- Check power bank is charged

#### Components Not Working:
- Verify power connections (5V/3.3V)
- Check all ground connections
- Verify pin assignments
- Test with multimeter

#### Load Cell Issues:
- Adjust calibration factor in code
- Check DT/SCK wiring
- Ensure stable power supply
- Test with known weights

#### QR Scanner Problems:
- Verify baud rate is 9600
- Check TX/RX connections (swap if needed)
- Test with known QR codes
- Check power supply

#### Servo Not Moving:
- Verify 5V power supply
- Check PWM pin connection
- Test with simple servo code
- Check servo specifications

## 📱 FINAL INTEGRATION

Once all components are tested:
1. Upload the complete `esp32_smart_bin_code.ino`
2. Configure WiFi credentials
3. Test full smart bin workflow
4. Connect to MaBote.ph API
5. Test real transactions

This wiring diagram will help you connect everything correctly!







