/*
 * QR Scanner Testing Code
 * Test GM65 QR scanner on GPIO 16, 17
 */

#include <SoftwareSerial.h>

SoftwareSerial qrSerial(16, 17); // RX, TX

void setup() {
  Serial.begin(115200);
  qrSerial.begin(9600);
  Serial.println("✅ QR Scanner Test Started");
  Serial.println("Scan QR codes to test");
}

void loop() {
  if (qrSerial.available()) {
    String qrData = qrSerial.readString();
    qrData.trim();
    Serial.println("📱 QR Code: " + qrData);
  }
  delay(100);
}







