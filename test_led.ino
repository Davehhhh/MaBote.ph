/*
 * LED Testing Code
 * Test LED connection on GPIO 2
 */

#define LED_PIN 2

void setup() {
  pinMode(LED_PIN, OUTPUT);
  Serial.begin(115200);
  Serial.println("✅ LED Test Started");
}

void loop() {
  // Turn LED ON
  digitalWrite(LED_PIN, HIGH);
  Serial.println("🔴 LED ON");
  delay(1000);
  
  // Turn LED OFF
  digitalWrite(LED_PIN, LOW);
  Serial.println("⚫ LED OFF");
  delay(1000);
}







