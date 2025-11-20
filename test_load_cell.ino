/*
 * Load Cell Testing Code
 * Test HX711 load cell on GPIO 21, 22
 */

#include "HX711.h"

#define HX711_DT_PIN 21
#define HX711_SCK_PIN 22

HX711 scale;

void setup() {
  Serial.begin(115200);
  scale.begin(HX711_DT_PIN, HX711_SCK_PIN);
  scale.set_scale(1000.0); // Adjust this value based on your load cell
  scale.tare(); // Reset to zero
  
  Serial.println("✅ Load Cell Test Started");
  Serial.println("Place objects on load cell to test");
}

void loop() {
  float weight = scale.get_units(5); // Average of 5 readings
  Serial.println("⚖️ Weight: " + String(weight) + " grams");
  delay(1000);
}







