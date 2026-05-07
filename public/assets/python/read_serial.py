# This code is just to read from the ESP32 arduino output

# import serial

# ser = serial.Serial('/dev/ttyUSB0', 9600, timeout=2)
# line = ser.readline().decode('utf-8').strip()
# print(line)
# ser.close()

# https://forum.arduino.cc/t/using-python-to-read-and-process-serial-data-from-arduino/1059079

# do: ls /dev/tty* to find the correct port for the ESP32, it may be different than /dev/ttyUSB0

# import serial
# import json

# try:
#     ser = serial.Serial('/dev/ttyUSB0', 9600, timeout=2)
#     line = ser.readline().decode('utf-8').strip()
#     # Assume line is like "Frig1:25,60 Frig2:22,55" or something, parse it
#     # For now, if it reads, print as is, else default
#     if line:
#         print(line)
#     else:
#         print('{"Frig1":{"temperature":25,"humidity":60},"Frig2":{"temperature":22,"humidity":55}}')
#     ser.close()
# except Exception as e:
#     print('{"Frig1":{"temperature":25,"humidity":60},"Frig2":{"temperature":22,"humidity":55}}')

import serial
import sys
import time
import json
import paho.mqtt.client as mqtt

# MQTT settings
MQTT_BROKER = 'localhost'
MQTT_PORT = 1883

def publish_to_mqtt(topic, message):
    client = mqtt.Client()
    client.connect(MQTT_BROKER, MQTT_PORT, 60)
    client.publish(topic, message, retain=True)
    client.disconnect()

try:
    ser = serial.Serial('/dev/ttyUSB0', 9600, timeout=3)
    time.sleep(0.5)  
    
    line = ser.readline().decode('utf-8').strip()
    
    # If we get empty line, try one more time
    if not line:
        time.sleep(0.2)
        line = ser.readline().decode('utf-8').strip()
    
    ser.close()
    
    if line:
        line = line.replace('nan', 'null').replace('NaN', 'null').replace('Infinity', 'null')
        print(line)
        # Parse and publish to MQTT
        try:
            data = json.loads(line)
            for topic, values in data.items():
                publish_to_mqtt(topic, json.dumps(values))
        except json.JSONDecodeError:
            pass
    else:
        default_data = {"Frig1":{"temperature":25,"humidity":60},"Frig2":{"temperature":22,"humidity":55}}
        print(json.dumps(default_data))
        # Publish defaults
        for topic, values in default_data.items():
            publish_to_mqtt(topic, json.dumps(values))
        sys.stderr.write("Warning: No data read from serial, using defaults\n")
        
except serial.SerialException as e:
    default_data = {"Frig1":{"temperature":25,"humidity":60},"Frig2":{"temperature":22,"humidity":55}}
    print(json.dumps(default_data))
    # Publish defaults
    for topic, values in default_data.items():
        publish_to_mqtt(topic, json.dumps(values))
    sys.stderr.write(f"Serial error: {str(e)}\n")
    
except Exception as e:
    default_data = {"Frig1":{"temperature":25,"humidity":60},"Frig2":{"temperature":22,"humidity":55}}
    print(json.dumps(default_data))
    # Publish defaults
    for topic, values in default_data.items():
        publish_to_mqtt(topic, json.dumps(values))
    sys.stderr.write(f"Error: {str(e)}\n")