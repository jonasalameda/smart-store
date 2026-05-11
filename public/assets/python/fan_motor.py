#!/usr/bin/env python3
"""
DC motor fan control on Raspberry Pi (L293D-style driver).
Usage: python3 fan_motor.py on
       python3 fan_motor.py off
"""
import os
import sys

# Keep the LGPIO working directory available for systems that use it.
os.environ.setdefault("LGPIO_WORKING_DIR", "/tmp")

GPIO_BACKEND = None
GPIO = None
OutputDevice = None

try:
    import RPi.GPIO as GPIO
    GPIO_BACKEND = "rpi_gpio"
except (ImportError, RuntimeError):
    try:
        from gpiozero import OutputDevice
        GPIO_BACKEND = "gpiozero"
    except ImportError:
        print(
            "Install GPIO support on the Pi: sudo apt install python3-rpi.gpio python3-gpiozero",
        )
        sys.exit(1)

ENABLE_PIN = 22
IN1_PIN = 27
IN2_PIN = 17

outputs = {}


def setup():
    if GPIO_BACKEND == "rpi_gpio":
        GPIO.setmode(GPIO.BCM)
        GPIO.setwarnings(False)
        GPIO.setup(ENABLE_PIN, GPIO.OUT, initial=GPIO.LOW)
        GPIO.setup(IN1_PIN, GPIO.OUT, initial=GPIO.LOW)
        GPIO.setup(IN2_PIN, GPIO.OUT, initial=GPIO.LOW)
    else:
        outputs["enable"] = OutputDevice(ENABLE_PIN, active_high=True, initial_value=False)
        outputs["in1"] = OutputDevice(IN1_PIN, active_high=True, initial_value=False)
        outputs["in2"] = OutputDevice(IN2_PIN, active_high=True, initial_value=False)


def cleanup():
    if GPIO_BACKEND == "rpi_gpio":
        GPIO.cleanup()
    else:
        for device in outputs.values():
            try:
                device.close()
            except Exception:
                pass


def fan_on():
    if GPIO_BACKEND == "rpi_gpio":
        GPIO.output(IN1_PIN, GPIO.HIGH)
        GPIO.output(IN2_PIN, GPIO.LOW)
        GPIO.output(ENABLE_PIN, GPIO.HIGH)
    else:
        outputs["in1"].on()
        outputs["in2"].off()
        outputs["enable"].on()


def fan_off():
    if GPIO_BACKEND == "rpi_gpio":
        GPIO.output(IN1_PIN, GPIO.LOW)
        GPIO.output(IN2_PIN, GPIO.LOW)
        GPIO.output(ENABLE_PIN, GPIO.LOW)
    else:
        outputs["in1"].off()
        outputs["in2"].off()
        outputs["enable"].off()


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
        if cmd == "on":
            fan_on()
            print("Fan motor ON")
        else:
            fan_off()
            print("Fan motor OFF")
    finally:
        # Keep the pins configured while the motor is running;
        # if the script exits immediately, cleanup() should not
        # reset the motor state unnecessarily.
        pass

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
