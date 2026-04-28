# Do this on the Pi:
#  sudo modprobe usbserial vendor=0x0483 product=0x5750
#  ls /dev/ttyUSB*

import serial
import time

ser = serial.Serial("/dev/ttyUSB0", 115200, timeout=0.5)

# disable beep
ser.write(bytes.fromhex("0007FF0000000000"))

# start inventory
ser.write(bytes.fromhex("0008220000000022"))

buffer = bytearray()
start_time = time.time()

while time.time() - start_time < 5:  # wait max 5 seconds
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

            print(epc)
            ser.close()
            exit(0)

# no tag found
ser.close()
print("")