// dht11 temperature and humidity sensor arduino code
#include "DHT.h"
#define DHTPIN 4      // Pin where the data pin of DHT11 is connected
#define DHTTYPE DHT11

DHT dht(DHTPIN, DHTTYPE);

void setup() {
  Serial.begin(9600);
  dht.begin();
}

void loop() {
  // Reading temperature or humidity takes about 250 milliseconds!
  float humidity = dht.readHumidity();
  float temperature = dht.readTemperature(); // For Fahrenheit use dht.readTemperature(true);

  // Check if any reads failed and exit early (to try again).
  if (isnan(humidity) || isnan(temperature)) {
    Serial.println("Failed to read from DHT sensor!");
    return;
  }

// Serial.print("Humidity: ");
// Serial.print(humidity);
// Serial.print(" %\t");
// Serial.print("Temperature: ");
// Serial.print(temperature);
// Serial.println(" *C");
  char payload[50]; //declare sting variable to hold the JSON payload
  sprintf(payload, "{\"temperature\":%.2f,\"humidity\":%.2f}", temperature, humidity);
  Serial.println(payload);
// print('{"temperature":%.2f,"humidity":%.2f}' % (dht.getTemperature(), dht.getHumidity()))
delay(5000);
}


//ip 192.168.0.105

//Source: https://www.slyautomation.com/blog/dht11-temperature-and-humidity-sensor-arduino-code/ 