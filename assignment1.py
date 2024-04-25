from sense_emu import SenseHat
import time
import requests

class TreeSensor:
    def __init__(self):
        self.sense = SenseHat()
        self.last_report_time = time.time()
        self.last_acceleration = self.sense.get_accelerometer_raw()
        self.current_state = "normal"
        self.collision_message = "COL"
        self.setup_mode = False
        self.mode = "temperature"
        self.temperature_threshold = 15
        self.humidity_threshold = 15
        self.last_windy_reported = False

    def detect_movement(self):
        print("Detection mode")
        acceleration = self.sense.get_accelerometer_raw()
        accX = abs(acceleration['x'] - self.last_acceleration['x'])
        accY = abs(acceleration['y'] - self.last_acceleration['y'])
        accZ = abs(acceleration['z'] - self.last_acceleration['z'])
        maxAcc = max(accX, accY, accZ)
        print(f"Max Acceleration: {maxAcc}")

        if maxAcc < 0.1:
            self.current_state = "normal"
        elif 0.1 <= maxAcc <= 0.3:
            self.current_state = "windy"
            if not self.last_windy_reported:
                self.last_windy_reported = True
        elif maxAcc > 0.3:
            self.current_state = "collision"
            print("Collision detected")

        self.last_acceleration = acceleration

    def report_to_server(self):
        web_server = 'http://iotserver.com/webserver.php'
        temperature = self.sense.get_temperature()
        humidity = self.sense.get_humidity()
        deviceTimestamp = time.strftime('%Y-%m-%d %H:%M:%S')

        payload = {
            'deviceTimestamp': deviceTimestamp,
            'temperature': temperature,
            'humidity': humidity,
            'state': self.current_state,
            'temperature_threshold': self.temperature_threshold,
            'humidity_threshold': self.humidity_threshold
        }

        try:
            print("Sending request to server...")
            r = requests.get(web_server, params=payload)
            print("Request sent.")
            if r.status_code == 200:
                print("Success!")
        except requests.exceptions.RequestException as e:
            print(f"Offline Error: {e}")

    def setup_mode_handler(self):
        print("Setup mode active")
        self.sense.show_message("Setup",scroll_speed=0.05)
        last_setup_activity = time.time()
        while self.setup_mode:
            if time.time() - last_setup_activity > 10:
                print("Exiting Setup Mode (due to inactivity)...")
                self.setup_mode = False
                break

            events = self.sense.stick.get_events()
            for event in events:
                last_setup_activity = time.time()  
                if event.action == "pressed":
                    if event.direction == "middle":
                        self.setup_mode = False
                        print("Exiting setup mode...")
                        break
                    if event.direction == "left":
                        self.mode = "temperature"
                        self.sense.show_message("M:T",scroll_speed=0.05)
                    if event.direction == "right":
                        self.mode = "humidity"
                        self.sense.show_message("M:H",scroll_speed=0.05)
                    if event.direction == "up":
                        if self.mode == "temperature":
                            self.temperature_threshold += 1
                            self.sense.show_message(f"T + : {self.temperature_threshold:.1f}",scroll_speed=0.05)
                        elif self.mode == "humidity":
                            self.humidity_threshold += 1
                            self.sense.show_message(f"H + : {self.humidity_threshold:.1f}",scroll_speed=0.05)
                    if event.direction == "down":
                        if self.mode == "temperature":
                            self.temperature_threshold -= 1
                            self.sense.show_message(f"T - : {self.temperature_threshold:.1f}",scroll_speed=0.05)
                        elif self.mode == "humidity":
                            self.humidity_threshold -= 1
                            self.sense.show_message(f"H - : { self.humidity_threshold:.1f}",scroll_speed=0.05)

    def run(self):
        while True:
            current_time = time.time()
            if not self.setup_mode:
                if self.current_state != "collision":
                    self.detect_movement()
                if self.current_state == "collision":
                    self.sense.show_message(self.collision_message, scroll_speed=0.05)

            if current_time - self.last_report_time >= 60:
                self.report_to_server()
                self.last_report_time = current_time

            # Handle button presses for setup or collision reset
            events = self.sense.stick.get_events()
            for event in events:
                if event.action == "pressed" and event.direction == "middle":
                    if self.current_state == "collision":
                        self.sense.clear()
                        self.current_state = "normal"
                        print("Collision mode is reset")
                        continue
                    if not self.setup_mode:
                        self.setup_mode = True
                        self.setup_mode_handler()

if __name__ == "__main__":
    sensor = TreeSensor()
    sensor.run()


