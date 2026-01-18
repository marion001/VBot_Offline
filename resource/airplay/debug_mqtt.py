import paho.mqtt.client as mqtt
from datetime import datetime
import os

BROKER = "localhost"
TOPIC = "shairport/vbot/#"
COVER_DIR = "/tmp/shairport_covers"

os.makedirs(COVER_DIR, exist_ok=True)

def detect_image_type(data: bytes):
    if data.startswith(b"\xFF\xD8\xFF"):
        return "jpg"
    if data.startswith(b"\x89PNG\r\n\x1a\n"):
        return "png"
    if data.startswith(b"GIF87a") or data.startswith(b"GIF89a"):
        return "gif"
    if data.startswith(b"RIFF") and b"WEBP" in data[8:16]:
        return "webp"
    return None

def on_message(client, userdata, msg):
    ts = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    # xử lý cover binary
    if msg.topic.endswith("/cover"):
        img_type = detect_image_type(msg.payload)
        size_kb = len(msg.payload) // 1024

        if img_type:
            filename = f"{COVER_DIR}/cover.{img_type}"
            with open(filename, "wb") as f:
                f.write(msg.payload)

            print(f"[{ts}] {msg.topic} -> 🖼 saved {filename} ({size_kb} KB)")
        else:
            print(f"[{ts}] {msg.topic} -> ❓ unknown binary ({size_kb} KB)")
        return

    # xử lý payload text
    try:
        payload = msg.payload.decode("utf-8")
    except UnicodeDecodeError:
        payload = f"<binary {len(msg.payload)} bytes>"

    print(f"[{ts}] {msg.topic} -> {payload}")

client = mqtt.Client()
client.on_message = on_message
client.connect(BROKER, 1883, 60)
client.subscribe(TOPIC)

print("🔍 Đã bắt đầu gỡ lỗi MQTT...")
client.loop_forever()
