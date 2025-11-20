# 📱 HOW TO CONNECT ANOTHER DEVICE (CELLPHONE) TO MABOTE SYSTEM

## 🔍 CURRENT SETUP ANALYSIS

### Your Current Configuration:
- **Your PC IP Address:** `10.162.14.120`
- **Current API Base URL:** `http://192.168.254.119/mabote_api` (in Flutter app)
- **XAMPP Server:** Running on your PC
- **Flutter App:** Currently configured for a different IP

## 🚨 ISSUE IDENTIFIED

Your Flutter app is configured to connect to `192.168.254.119` but your actual IP is `10.162.14.120`. This mismatch is why other devices can't connect!

## 🔧 SOLUTION: CONNECT ANOTHER DEVICE

### Method 1: Update Flutter App Configuration (Recommended)
    
#### Step 1: Update API Base URL in Flutter App
```bash
# Run Flutter app with correct IP address
flutter run --dart-define=API_BASE_URL=http://10.162.14.120/mabote_api
```

#### Step 2: For Physical Android Device
1. **Connect your phone to the same WiFi network** as your PC
2. **Run the Flutter app** with the correct IP:
   ```bash
   flutter run --dart-define=API_BASE_URL=http://10.162.14.120/mabote_api
   ```
3. **Install the app** on your phone
4. **Test the connection** by logging in

#### Step 3: For iOS Device
1. **Connect iPhone/iPad to same WiFi**
2. **Run Flutter app** with correct IP:
   ```bash
   flutter run --dart-define=API_BASE_URL=http://10.162.14.120/mabote_api
   ```
3. **Install via Xcode** or TestFlight

### Method 2: Update Default Configuration

#### Update the default API URL in your Flutter files:

**File: `lib/screens/rewards_screen.dart` (Line 29)**
```dart
const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://10.162.14.120/mabote_api');
```

**File: `lib/screens/home_screen.dart` (Line 59)**
```dart
const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://10.162.14.120/mabote_api');
```

**File: `lib/screens/notifications_screen.dart` (Line 33)**
```dart
const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://10.162.14.120/mabote_api');
```

**File: `lib/services/notification_service.dart` (Line 119)**
```dart
const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://10.162.14.120/mabote_api');
```

## 🌐 NETWORK REQUIREMENTS

### For Multiple Devices to Connect:

1. **Same WiFi Network:** All devices must be on the same WiFi network
2. **Firewall Settings:** Ensure Windows Firewall allows XAMPP connections
3. **XAMPP Configuration:** Make sure Apache is accessible from other devices

### Check XAMPP Configuration:
1. **Open XAMPP Control Panel**
2. **Click "Config" next to Apache**
3. **Select "httpd.conf"**
4. **Find line:** `Listen 80`
5. **Change to:** `Listen 0.0.0.0:80` (allows external connections)
6. **Restart Apache**

## 📱 DEVICE-SPECIFIC INSTRUCTIONS

### Android Device:
```bash
# Connect Android device via USB
flutter devices

# Run with correct IP
flutter run --dart-define=API_BASE_URL=http://10.162.14.120/mabote_api

# Or build APK for installation
flutter build apk --dart-define=API_BASE_URL=http://10.162.14.120/mabote_api
```

### iOS Device:
```bash
# Connect iOS device via USB
flutter devices

# Run with correct IP
flutter run --dart-define=API_BASE_URL=http://10.162.14.120/mabote_api

# Or build for iOS
flutter build ios --dart-define=API_BASE_URL=http://10.162.14.120/mabote_api
```

### Web Browser (Alternative):
```bash
# Run Flutter web version
flutter run -d web --dart-define=API_BASE_URL=http://10.162.14.120/mabote_api
```

## 🔍 TESTING CONNECTION

### Test API Connection from Another Device:

1. **Open browser on another device**
2. **Navigate to:** `http://10.162.14.120/mabote_api/test_connection.php`
3. **Should see:** "API is working!"

### Test from Mobile Browser:
1. **Open browser on phone**
2. **Go to:** `http://10.162.14.120/mabote_admin/`
3. **Should see:** MaBote Admin login page

## 🚨 TROUBLESHOOTING

### Common Issues:

#### 1. "Connection Refused" Error:
- **Check XAMPP is running**
- **Verify IP address is correct**
- **Check Windows Firewall**

#### 2. "Network Unreachable" Error:
- **Ensure devices are on same WiFi**
- **Check router settings**
- **Try different IP address**

#### 3. "API Not Found" Error:
- **Verify API endpoints exist**
- **Check XAMPP Apache is running**
- **Test with browser first**

### Quick Fix Commands:
```bash
# Check if XAMPP is accessible
curl http://10.162.14.120/mabote_api/test_connection.php

# Test from another device
ping 10.162.14.120

# Check Flutter devices
flutter devices
```

## 📋 STEP-BY-STEP CHECKLIST

### ✅ Pre-Connection Setup:
- [ ] XAMPP is running (Apache + MySQL)
- [ ] Your PC IP is `10.162.14.120`
- [ ] Both devices on same WiFi network
- [ ] Windows Firewall allows XAMPP connections

### ✅ Flutter App Setup:
- [ ] Update API base URL to `http://10.162.14.120/mabote_api`
- [ ] Run Flutter app with correct IP
- [ ] Test login functionality

### ✅ Device Connection:
- [ ] Connect target device to same WiFi
- [ ] Install Flutter app on device
- [ ] Test API connection
- [ ] Verify login works

### ✅ Final Testing:
- [ ] Login with existing account
- [ ] Test all app features
- [ ] Check notifications work
- [ ] Verify rewards system

## 🎯 EXPECTED RESULT

After following these steps:
- **Multiple devices** can connect to your MaBote system
- **Same user accounts** work across all devices
- **Real-time data sync** between devices
- **Notifications** work on all connected devices
- **Rewards system** accessible from any device

## 🔗 QUICK START COMMAND

```bash
# Run this command to connect another device
flutter run --dart-define=API_BASE_URL=http://10.162.14.120/mabote_api
```

**Your MaBote system will now be accessible from any device on your network!** 🎉






