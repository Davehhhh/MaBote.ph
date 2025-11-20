/*
 * IR Sensor Testing Code
 * Test TCRT5000 IR sensor on GPIO 19
 */

#define IR_PIN 19

void setup() {
  pinMode(IR_PIN, INPUT);
  Serial.begin(115200);
  Serial.println("✅ IR Sensor Test Started");
  Serial.println("Place object near sensor to test");
}

void loop() {
  int irValue = digitalRead(IR_PIN);
  
  if (irValue == LOW) {
    Serial.println("🔴 Object DETECTED!");
  } else {
    Serial.println("⚫ No object");
  }
  
  delay(500);
}







