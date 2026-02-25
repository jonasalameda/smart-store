#!/usr/bin/env python3
"""
LED and Buzzer Control Script for Smart Store
Controls GPIO pins for visual and audio feedback:
- Blue LED: Success indication
- Red LED: Error indication
- Buzzer: Error indication
"""

import sys
from time import sleep
import RPi.GPIO as GPIO

BLUE_LED_PIN = 18
RED_LED_PIN = 23
BUZZER_PIN = 24

LED_DURATION = 1
BUZZER_DURATION = 1
BUZZER_FREQUENCY = 1000

def setup_gpio():
    GPIO.setmode(GPIO.BCM)
    GPIO.setwarnings(False)

    GPIO.setup(BLUE_LED_PIN, GPIO.OUT)
    GPIO.setup(RED_LED_PIN, GPIO.OUT)
    GPIO.setup(BUZZER_PIN, GPIO.OUT)

    GPIO.output(BLUE_LED_PIN, GPIO.LOW)
    GPIO.output(RED_LED_PIN, GPIO.LOW)
    GPIO.output(BUZZER_PIN, GPIO.LOW)

def cleanup_gpio():
    GPIO.cleanup()

def success_indication():
    try:
        setup_gpio()
        GPIO.output(BLUE_LED_PIN, GPIO.HIGH)
        sleep(LED_DURATION)
        GPIO.output(BLUE_LED_PIN, GPIO.LOW)
        cleanup_gpio()
        return True
    except Exception as e:
        print("Error in success_indication:", e)
        cleanup_gpio()
        return False

def error_indication():
    try:
        setup_gpio()
        
        GPIO.output(RED_LED_PIN, GPIO.HIGH)
        buzzer = GPIO.PWM(BUZZER_PIN, BUZZER_FREQUENCY)
        buzzer.start(50)
        
        sleep(BUZZER_DURATION)
        buzzer.stop()

        sleep(LED_DURATION - BUZZER_DURATION)

        GPIO.output(RED_LED_PIN, GPIO.LOW)
        
        cleanup_gpio()
        return True
    except Exception as e:
        print("Error in error_indication:", e)
        cleanup_gpio()
        return False

COMMAND_SUCCESS = "success"
COMMAND_ERROR = "error"
VALID_COMMANDS = (COMMAND_SUCCESS, COMMAND_ERROR)

def main():
    if len(sys.argv) < 2:
        print("Usage: python3 LED_Buzzer.py [success|error]")
        exit(1)

    command = sys.argv[1].lower()

    if command not in VALID_COMMANDS:
        print("Unknown command:", command, "- Use 'success' or 'error'")
        exit(1)

    if command == COMMAND_SUCCESS:
        ran_ok = success_indication()
        ok_message = "Success indication activated"
        fail_message = "Failed to activate success indication"
    else:
        ran_ok = error_indication()
        ok_message = "Error indication activated"
        fail_message = "Failed to activate error indication"

    if ran_ok:
        print(ok_message)
        exit(0)
    else:
        print(fail_message)
        exit(1)

if __name__ == "__main__":
    main()
