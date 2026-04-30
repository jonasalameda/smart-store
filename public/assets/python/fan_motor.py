#!/usr/bin/env python3
"""
DC motor fan control on Raspberry Pi (L293D-style driver).
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

ENABLE_PIN = 22
IN1_PIN = 27
IN2_PIN = 17


def setup():
    GPIO.setmode(GPIO.BCM)
    GPIO.setwarnings(False)
    GPIO.setup(ENABLE_PIN, GPIO.OUT, initial=GPIO.LOW)
    GPIO.setup(IN1_PIN, GPIO.OUT, initial=GPIO.LOW)
    GPIO.setup(IN2_PIN, GPIO.OUT, initial=GPIO.LOW)


def main():
    if len(sys.argv) < 2:
        print("Usage: python3 fan_motor.py [on|off]")
        return 1

    cmd = sys.argv[1].lower().strip()
    if cmd not in ("on", "off"):
        print("Use 'on' or 'off'")
        return 1

    setup()
    if cmd == "on":
        # Forward direction + enable motor driver
        GPIO.output(IN1_PIN, GPIO.HIGH)
        GPIO.output(IN2_PIN, GPIO.LOW)
        GPIO.output(ENABLE_PIN, GPIO.HIGH)
        print("Fan motor ON")
    else:
        # Disable motor
        GPIO.output(IN1_PIN, GPIO.LOW)
        GPIO.output(IN2_PIN, GPIO.LOW)
        GPIO.output(ENABLE_PIN, GPIO.LOW)
        print("Fan motor OFF")

    # Do not cleanup() here; cleanup resets pins and can stop the motor immediately.
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
