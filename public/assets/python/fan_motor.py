#!/usr/bin/env python3
"""
DC motor fan control on Raspberry Pi.
Usage: python3 fan_motor.py on
       python3 fan_motor.py off
"""
import os
import sys

os.environ.setdefault("LGPIO_WORKING_DIR", "/tmp")

try:
    import RPi.GPIO as GPIO
except ImportError:
    print("Install RPi.GPIO on the Pi: sudo apt install python3-rpi.gpio")
    sys.exit(1)

MOTOR_PIN = 17


def setup():
    GPIO.setmode(GPIO.BCM)
    GPIO.setwarnings(False)
    GPIO.setup(MOTOR_PIN, GPIO.OUT, initial=GPIO.LOW)


def main():
    if len(sys.argv) < 2:
        print("Usage: python3 fan_motor.py [on|off]")
        return 1

    cmd = sys.argv[1].lower().strip()
    if cmd not in ("on", "off"):
        print("Use 'on' or 'off'")
        return 1

    setup()
    try:
        GPIO.output(MOTOR_PIN, GPIO.HIGH if cmd == "on" else GPIO.LOW)
        print(f"Fan motor {'ON' if cmd == 'on' else 'OFF'}")
        return 0
    finally:
        if cmd == "off":
            GPIO.cleanup()


if __name__ == "__main__":
    raise SystemExit(main())
