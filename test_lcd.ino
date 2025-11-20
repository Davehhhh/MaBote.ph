/*
 * LCD Display Testing Code
 * Test 16x2 I2C LCD display on GPIO 4, 5
 */

#include <LiquidCrystal_I2C.h>

LiquidCrystal_I2C lcd(0x27, 16, 2); // I2C address, 16 columns, 2 rows

void setup() {
  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("MaBote.ph");
  lcd.setCursor(0, 1);
  lcd.print("LCD Test OK!");
  
  Serial.begin(115200);
  Serial.println("✅ LCD Display Test Started");
}

void loop() {
  // Display will show static text
  delay(1000);
}







