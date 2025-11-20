/*
 * Servo Motor Testing Code
 * Test MG996R servo motor on GPIO 18
 */

#include <ESP32Servo.h>

Servo myServo;
#define SERVO_PIN 18

void setup() {
  myServo.attach(SERVO_PIN);
  Serial.begin(115200);
  Serial.println("✅ Servo Test Started");
}

void loop() {
  // Close position (0 degrees)
  myServo.write(0);
  Serial.println("🔒 Servo: CLOSED (0°)");
  delay(2000);
  
  // Half open (90 degrees)
  myServo.write(90);
  Serial.println("🔓 Servo: HALF OPEN (90°)");
  delay(2000);
  
  // Full open (180 degrees)
  myServo.write(180);
  Serial.println("🔓 Servo: FULL OPEN (180°)");
  delay(2000);
}







