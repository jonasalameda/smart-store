// ============================================================================
//  Fridge Monitor — ESP32 Dev Module
//  Reads two DHT11 sensors and publishes to MQTT topics: Frig1 & Frig2
//
//  Libraries required (Arduino Library Manager):
//    - "DHT sensor library"  by Adafruit
//    - "Adafruit Unified Sensor" by Adafruit (dependency)
//    - "PubSubClient"        by Nick O'Leary
// ============================================================================

#include <WiFi.h>
#include <PubSubClient.h>
#include "DHT.h"

// ── Sensor config ─────────────────────────────────────────────────────────
#define DHTPIN1   4       // GPIO4  → DHT11 sensor 1 (Frig1)
#define DHTPIN2   2       // GPIO2  → DHT11 sensor 2 (Frig2)
#define DHTTYPE   DHT11

// ── WiFi credentials ──────────────────────────────────────────────────────
constexpr char WIFI_SSID[]     = "padaria";
constexpr char WIFI_PASSWORD[] = "PlJh_@2002";

// ── MQTT broker ───────────────────────────────────────────────────────────
constexpr char MQTT_BROKER[]   = "10.0.0.75";
constexpr int  MQTT_PORT       =  1883;
constexpr char MQTT_CLIENT_ID[]= "esp32-fridge-monitor";
constexpr char TOPIC_FRIG1[]   = "Frig1";
constexpr char TOPIC_FRIG2[]   = "Frig2";

// ── Timing ────────────────────────────────────────────────────────────────
constexpr unsigned long PUBLISH_INTERVAL_MS = 1000;   // 5 s between readings
constexpr unsigned long MQTT_RETRY_DELAY_MS = 3000;   // wait before reconnect

// ── Objects ───────────────────────────────────────────────────────────────
DHT          dht1(DHTPIN1, DHTTYPE);
DHT          dht2(DHTPIN2, DHTTYPE);
WiFiClient   wifiClient;
PubSubClient mqttClient(wifiClient);

// ─────────────────────────────────────────────────────────────────────────
// WiFi
// ─────────────────────────────────────────────────────────────────────────
void connectWiFi() {
  if (WiFi.isConnected()) return;

  Serial.printf("\n[WiFi] Connecting to %s", WIFI_SSID);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  while (!WiFi.isConnected()) {
    delay(500);
    Serial.print(".");
  }

  Serial.printf("\n[WiFi] Connected — IP: %s\n", WiFi.localIP().toString().c_str());
}

// ─────────────────────────────────────────────────────────────────────────
// MQTT
// ─────────────────────────────────────────────────────────────────────────
void connectMQTT() {
  while (!mqttClient.connected()) {
    Serial.printf("[MQTT] Connecting to %s:%d ...", MQTT_BROKER, MQTT_PORT);

    if (mqttClient.connect(MQTT_CLIENT_ID)) {
      Serial.println(" connected.");
    } else {
      Serial.printf(" failed (state=%d). Retrying in %lu s...\n",
                    mqttClient.state(), MQTT_RETRY_DELAY_MS / 1000);
      delay(MQTT_RETRY_DELAY_MS);
    }
  }
}

// ─────────────────────────────────────────────────────────────────────────
// Sensor read + publish  (shared logic for both fridges)
// ─────────────────────────────────────────────────────────────────────────
void readAndPublish(DHT& sensor, const char* topic, const char* label) {
  float temperature = sensor.readTemperature();
  float humidity    = sensor.readHumidity();

  if (isnan(temperature) || isnan(humidity)) {
    Serial.printf("[%s] Sensor read failed — skipping.\n", label);
    return;
  }

  char payload[64];
  snprintf(payload, sizeof(payload),
           "{\"temperature\":%.2f,\"humidity\":%.2f}",
           temperature, humidity);

  if (mqttClient.publish(topic, payload)) {
    Serial.printf("[%s] Published → %s\n", label, payload);
  } else {
    Serial.printf("[%s] Publish failed (topic=%s)\n", label, topic);
  }
}

// ─────────────────────────────────────────────────────────────────────────
// Setup
// ─────────────────────────────────────────────────────────────────────────
void setup() {
  Serial.begin(115200);
  delay(100);                     // let serial settle

  dht1.begin();
  dht2.begin();

  connectWiFi();

  mqttClient.setServer(MQTT_BROKER, MQTT_PORT);
  mqttClient.setKeepAlive(60);    // heartbeat every 60 s
  connectMQTT();
}

// ─────────────────────────────────────────────────────────────────────────
// Loop
// ─────────────────────────────────────────────────────────────────────────
void loop() {
  // Maintain connections
  connectWiFi();
  if (!mqttClient.connected()) connectMQTT();
  mqttClient.loop();

  // Publish on interval (non-blocking)
  static unsigned long lastPublish = 0;
  if (millis() - lastPublish >= PUBLISH_INTERVAL_MS) {
    lastPublish = millis();
    readAndPublish(dht1, TOPIC_FRIG1, "Frig1");
    readAndPublish(dht2, TOPIC_FRIG2, "Frig2");
  }
}