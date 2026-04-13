#No Beep and ontinuous inventory
import serial
import time

ser = serial.Serial("/dev/ttyUSB0",115200,timeout=0.1)

# disable beep
ser.write(bytes.fromhex("0007FF0000000000"))

# start continuous inventory
ser.write(bytes.fromhex("0008220000000022"))

print("Inventory started")

buffer = bytearray()

while True:

    data = ser.read(ser.in_waiting or 1)

    if data:
        buffer.extend(data)

        while True:

            idx = buffer.find(b'\xFC\x90')

            if idx == -1:
                break

            if len(buffer) < idx + 8:
                break

            epc_len = buffer[idx+4]

            frame_len = 5 + epc_len + 3

            if len(buffer) < idx + frame_len:
                break

            frame = buffer[idx:idx+frame_len]

            epc = frame[5:5+epc_len].hex().upper()

            rssi = frame[5+epc_len] - 256

            # print("EPC:",epc,"RSSI:",rssi)
            print(epc)

            del buffer[:idx+frame_len]

# Do this on the Pi:
#  sudo modprobe usbserial vendor=0x0483 product=0x5750
#  ls /dev/ttyUSB*