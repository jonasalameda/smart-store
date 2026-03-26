// dht11 temperature and humidity sensor arduino code
#include "DHT.h"
#define DHTPIN1 4      
#define DHTPIN2 2      
#define DHTTYPE DHT11

DHT dht1(DHTPIN1, DHTTYPE);
DHT dht2(DHTPIN2, DHTTYPE);

void setup() {
  Serial.begin(9600);
  dht1.begin();
  dht2.begin();
}

void loop() {
  // Reading temperature or humidity takes about 250 milliseconds!
  float humidity1 = dht1.readHumidity();
  float temperature1 = dht1.readTemperature(); 

  float humidity2 = dht2.readHumidity();
  float temperature2 = dht2.readTemperature(); 

  // // Check if any reads failed and exit early (to try again).
  // if (isnan(humidity1) || isnan(temperature1)) {
  //   Serial.println("Failed to read from DHT sensor!");
  //   return;
  // }

  char payload[150]; //declare sting variable to hold the JSON payload

  // sprintf(payload, "{\"temperature\":%.2f,\"humidity\":%.2f}", temperature, humidity);
  sprintf(payload, "{\"Frig1\":{\"temperature\":%.2f,\"humidity\":%.2f},\"Frig2\":{\"temperature\":%.2f,\"humidity\":%.2f}}", 
          temperature1, humidity1, temperature2, humidity2);
  Serial.println(payload);
// print('{"temperature":%.2f,"humidity":%.2f}' % (dht.getTemperature(), dht.getHumidity()))
delay(5000);
}


//ip 192.168.0.105

//Source: https://www.slyautomation.com/blog/dht11-temperature-and-humidity-sensor-arduino-code/ 