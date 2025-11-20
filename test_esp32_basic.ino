/*
 * ESP32 Component Testing Code
 * Test each component individually before building the complete system
 */

// Test 1: Basic ESP32 Test
void setup() {
  Serial.begin(115200);
  Serial.println("✅ ESP32 is working!");
  Serial.println("✅ Serial communication OK");
}

void loop() {
  Serial.println("ESP32 running...");
  delay(1000);
}







