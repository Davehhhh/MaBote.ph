# MaBote.ph Smart Machine - Complete Wiring Diagram
## Visual Connection Guide

---

## 📐 ESP32 PINOUT REFERENCE

```
                    ESP32 DevKit V1
┌─────────────────────────────────────────────────────┐
│                                                     │
│  [USB]  [3.3V] [5V]  [GND] [EN] [GPIO0] [GPIO2]   │
│                                                     │
│  [GPIO4] [GPIO5] [GPIO16] [GPIO17] [GPIO18]        │
│  [GPIO19] [GPIO21] [GPIO22] [GPIO23] [GPIO25]      │
│  [GPIO26] [GPIO27] [GPIO32] [GPIO33] [GPIO35]      │
│                                                     │
│  [GND] [3.3V] [5V] [GND] [Vin] [GND]              │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## 🔌 COMPLETE WIRING CONNECTIONS

### Connection Table:

| Component | Pin/Wire | ESP32 GPIO | Power | Notes |
|-----------|----------|------------|-------|-------|
| **MH ET Live Scanner** | VCC | - | 5V | Red wire to 5V rail |
| | GND | - | GND | Black wire to GND rail |
| | TX | GPIO 17 | - | Yellow wire |
| | RX | GPIO 16 | - | Green wire |
| **LCD I2C 16x2** | VCC | - | 5V | Red wire to 5V rail |
| | GND | - | GND | Black wire to GND rail |
| | SDA | GPIO 21 | - | Blue wire |
| | SCL | GPIO 22 | - | White wire |
| **IR Sensor LM393** | VCC | - | 3.3V | Red wire to 3.3V |
| | GND | - | GND | Black wire to GND rail |
| | OUT | GPIO 19 | - | Yellow wire |
| **Ultrasonic HC-SR04** | VCC | - | 5V | Red wire to 5V rail |
| | GND | - | GND | Black wire to GND rail |
| | TRIG | GPIO 26 | - | Orange wire |
| | ECHO | GPIO 25 | - | Yellow wire |
| **Servo MG996R** | Red | - | 5V External | Separate 5V 3A supply |
| | Black | - | GND | Common ground |
| | Yellow | GPIO 18 | - | PWM signal |
| **Proximity LJC18A3** | Brown | - | 3.3V | Power wire |
| | Blue | - | GND | Ground wire |
| | Black | GPIO 23 | - | Signal wire |
| **Status LED** | Anode | GPIO 2 | - | Through 220Ω resistor |
| | Cathode | - | GND | Direct to GND |

---

## 🔋 POWER DISTRIBUTION DIAGRAM

```
                    POWER SUPPLY SETUP
┌─────────────────────────────────────────────────────┐
│                                                     │
│  External 5V 3A Power Supply                       │
│  ┌─────────────────────────────────────┐           │
│  │  +5V (Red)  →  Servo Motor (Red)   │           │
│  │  GND (Black) →  Common Ground Rail │           │
│  └─────────────────────────────────────┘           │
│                                                     │
│  ESP32 USB Power (5V 2A)                           │
│  ┌─────────────────────────────────────┐           │
│  │  ESP32 5V Pin → 5V Power Rail       │           │
│  │  ├── MH ET Live Scanner (VCC)      │           │
│  │  ├── LCD I2C (VCC)                 │           │
│  │  └── Ultrasonic Sensor (VCC)       │           │
│  │                                     │           │
│  │  ESP32 3.3V Pin → 3.3V Power Rail  │           │
│  │  ├── IR Sensor LM393 (VCC)        │           │
│  │  └── Proximity Sensor (Brown)      │           │
│  │                                     │           │
│  │  ESP32 GND → GND Power Rail       │           │
│  │  └── All components (GND)          │           │
│  └─────────────────────────────────────┘           │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## 📊 BREADBOARD LAYOUT

```
                    BREADBOARD VIEW
┌─────────────────────────────────────────────────────┐
│  Power Rails (Top)                                 │
│  [5V] ───────────────────────────────────────────── │
│  [GND] ─────────────────────────────────────────── │
│  [3.3V] ─────────────────────────────────────────── │
│                                                     │
│  Component Area                                    │
│  ┌─────────────────────────────────────────────┐  │
│  │  ESP32 DevKit V1                            │  │
│  │  [Mounted on breadboard]                     │  │
│  └─────────────────────────────────────────────┘  │
│                                                     │
│  ┌─────────────────────────────────────────────┐  │
│  │  LCD I2C Module                             │  │
│  │  [Connected via I2C]                         │  │
│  └─────────────────────────────────────────────┘  │
│                                                     │
│  ┌─────────────────────────────────────────────┐  │
│  │  MH ET Live Scanner                          │  │
│  │  [Connected via Serial2]                    │  │
│  └─────────────────────────────────────────────┘  │
│                                                     │
│  Sensors (Bottom)                                  │
│  ┌─────────────────────────────────────────────┐  │
│  │  IR Sensor LM393                            │  │
│  │  Proximity Sensor LJC18A3                   │  │
│  │  Ultrasonic Sensor HC-SR04                   │  │
│  └─────────────────────────────────────────────┘  │
│                                                     │
│  Power Rails (Bottom)                             │
│  [5V] ───────────────────────────────────────────── │
│  [GND] ─────────────────────────────────────────── │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## 🔗 DETAILED CONNECTION DIAGRAMS

### 1. MH ET Live Scanner Connection:

```
MH ET Live Scanner Module
┌─────────────────────────┐
│  VCC  GND  TX  RX     │
└─────────────────────────┘
    │    │    │   │
    │    │    │   │
    │    │    │   └─────── GPIO 16 (ESP32 RX)
    │    │    └─────────── GPIO 17 (ESP32 TX)
    │    └──────────────── GND Rail
    └────────────────────── 5V Rail
```

### 2. LCD I2C Connection:

```
LCD I2C Module
┌─────────────────┐
│  VCC  GND  SDA SCL│
└─────────────────┘
    │    │    │  │
    │    │    │  │
    │    │    │  └─── GPIO 22 (ESP32 SCL)
    │    │    └────── GPIO 21 (ESP32 SDA)
    │    └─────────── GND Rail
    └──────────────── 5V Rail
```

### 3. IR Sensor LM393 Connection:

```
IR Sensor LM393
┌─────────────────┐
│  VCC  GND  OUT  │
└─────────────────┘
    │    │    │
    │    │    │
    │    │    └─────── GPIO 19 (ESP32)
    │    └──────────── GND Rail
    └────────────────── 3.3V (ESP32)
```

### 4. Ultrasonic Sensor Connection:

```
Ultrasonic Sensor HC-SR04
┌─────────────────────┐
│  VCC  GND  TRIG ECHO│
└─────────────────────┘
    │    │    │    │
    │    │    │    │
    │    │    │    └─── GPIO 25 (ESP32 ECHO)
    │    │    └──────── GPIO 26 (ESP32 TRIG)
    │    └───────────── GND Rail
    └─────────────────── 5V Rail
```

### 5. Servo Motor Connection:

```
Servo Motor MG996R
┌─────────────────────┐
│  Red  Black  Yellow │
└─────────────────────┘
    │     │      │
    │     │      │
    │     │      └─────── GPIO 18 (ESP32 PWM)
    │     └──────────── GND Rail (Common)
    └─────────────────── External 5V 3A Supply
```

### 6. Proximity Sensor Connection:

```
Proximity Sensor LJC18A3-H-Z/BY
┌─────────────────────┐
│  Brown  Blue  Black │
└─────────────────────┘
    │     │      │
    │     │      │
    │     │      └─────── GPIO 23 (ESP32 Signal)
    │     └────────────── GND Rail
    └───────────────────── 3.3V (ESP32)
```

---

## ⚡ POWER REQUIREMENTS

### Total Power Consumption:

| Component | Voltage | Current | Notes |
|-----------|---------|---------|-------|
| ESP32 | 5V | 500mA | USB powered |
| MH ET Live Scanner | 5V | 200mA | Peak current |
| LCD I2C | 5V | 20mA | Backlight on |
| Ultrasonic Sensor | 5V | 15mA | Active |
| IR Sensor LM393 | 3.3V | 5mA | Low power |
| Proximity Sensor | 3.3V | 10mA | Active |
| Servo Motor | 5V | 1-2A | Peak during movement |
| **Total (without servo)** | 5V | ~750mA | ESP32 USB power |
| **Servo (separate)** | 5V | 1-2A | External supply |

### Power Supply Recommendations:

1. **ESP32 USB Power:** 5V 2A USB power bank or adapter
2. **Servo Motor:** Separate 5V 3A power supply (required for stable operation)
3. **Common Ground:** Connect all GND together (critical!)

---

## 🔍 TROUBLESHOOTING CONNECTIONS

### Check These First:

1. **Power Connections:**
   - ✅ All VCC/GND connections secure
   - ✅ Correct voltage levels (5V vs 3.3V)
   - ✅ Common ground established

2. **Signal Connections:**
   - ✅ GPIO pins match code
   - ✅ No loose connections
   - ✅ Proper wire routing

3. **I2C Connections:**
   - ✅ SDA/SCL not swapped
   - ✅ Pull-up resistors (usually on I2C module)
   - ✅ Correct I2C address (0x27 or 0x3F)

4. **Serial Connections:**
   - ✅ TX/RX not swapped
   - ✅ Correct baud rate (9600)
   - ✅ Proper voltage levels

5. **Servo Connection:**
   - ✅ External power supply connected
   - ✅ PWM signal on correct pin
   - ✅ Common ground with ESP32

---

## 📝 WIRING CHECKLIST

Before powering on, verify:

- [ ] ESP32 mounted securely on breadboard
- [ ] All power connections (5V, 3.3V, GND) verified
- [ ] MH ET Live Scanner TX/RX connected correctly
- [ ] LCD I2C SDA/SCL connected correctly
- [ ] IR Sensor OUT pin connected to GPIO 19
- [ ] Ultrasonic TRIG/ECHO connected correctly
- [ ] Servo motor has external power supply
- [ ] Servo PWM signal on GPIO 18
- [ ] Proximity sensor signal on GPIO 23
- [ ] All GND connections to common ground
- [ ] No short circuits
- [ ] All connections secure and not loose

---

## 🎯 QUICK REFERENCE

### Pin Summary:
```
GPIO 16 → QR Scanner RX
GPIO 17 → QR Scanner TX
GPIO 18 → Servo Motor (PWM)
GPIO 19 → IR Sensor (OUT)
GPIO 21 → LCD SDA (I2C)
GPIO 22 → LCD SCL (I2C)
GPIO 23 → Proximity Sensor (Signal)
GPIO 25 → Ultrasonic ECHO
GPIO 26 → Ultrasonic TRIG
GPIO 2  → Status LED (Optional)
```

### Power Summary:
```
5V Rail → QR Scanner, LCD, Ultrasonic
3.3V Rail → IR Sensor, Proximity Sensor
External 5V 3A → Servo Motor
Common GND → All components
```

---

**Complete Wiring Diagram for MaBote.ph Smart Recycling Machine**








