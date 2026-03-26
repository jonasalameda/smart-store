# This code is just to read from the ESP32 arduino output

import serial

ser = serial.Serial('/dev/ttyUSB0', 9600, timeout=2)
line = ser.readline().decode('utf-8').strip()
print(line)
ser.close()

# https://forum.arduino.cc/t/using-python-to-read-and-process-serial-data-from-arduino/1059079

# do: ls /dev/tty* to find the correct port for the ESP32, it may be different than /dev/ttyUSB0