# VBot Multiroom Audio

Tài liệu vận hành, test và đóng gói hệ thống **VBot Multiroom Audio** theo trạng thái hiện tại của dự án.

> Cập nhật: 16-08-2026  
> VBot root: `/home/pi/VBot_Offline`  
> Runtime: `/home/pi/VBot_Offline/resource/multiroom_audio`

---

# 1. Kiến trúc hiện tại

```text
VBot Main
│
├── VBot_mDNS.py
│   ├── _vbot-assistant._tcp.local.
│   └── _vbot-multiroom._tcp.local.
│
├── vbot-multiroom.service
│   └── TCP 9850 / UDP 9851 / UDP 9852
│
├── vbot-multiroom-controller.service
│   └── /tmp/vbot_multiroom_controller.sock
│
└── vbot-multiroom-bridge.service
    └── /tmp/vbot_audio_bridge.sock
```

Log startup thành công điển hình:

```text
[VBot Zeroconf mDNS] Đã Được Kích Hoạt | Host: vbot-88a29e061cfa.local | Device ID: 88a29e061cfa | IPv4: 192.168.14.175
[VBot Assistant mDNS] Đã Được Kích Hoạt | Service: _vbot-assistant._tcp.local. | Port: 1711
[VBot Multiroom mDNS] Đã Được Kích Hoạt | Control: 9850 | Audio: 9851 | Clock Sync: 9852
[VBot Multiroom] Daemon đã hoạt động | Control Port: 9850
[VBot Multiroom Controller] Đã hoạt động
[VBot Audio Bridge] Đã hoạt động
```

Thứ tự startup trong VBot:

```python
register_vbot_mdns()
ensure_vbot_multiroom_daemon()
ensure_vbot_multiroom_controller()
ensure_vbot_audio_bridge()
media_player = MediaPlayer()
```

---

# 2. Cấu trúc file

```text
/home/pi/VBot_Offline/
│
├── Config.json
├── VBot_mDNS.py
│
└── resource/multiroom_audio/
    │
    ├── vbot_multiroom.py
    ├── vbot_multiroom_controller.py
    ├── vbot_audio_bridge.py
    ├── vbot_multiroom_group.py
    │
    ├── VBot_Multiroom.py
    ├── VBot_Multiroom_Controller.py
    ├── VBot_Audio_Bridge.py
    ├── VBot_Multiroom_Group.py
    │
    ├── vbot-multiroom.service
    ├── vbot-multiroom-controller.service
    ├── vbot-multiroom-bridge.service
    │
    └── multiroom_groups.json
```

`VBot_mDNS.py` nằm ở root:

```text
/home/pi/VBot_Offline/VBot_mDNS.py
```

không nằm trong `resource/multiroom_audio`.

---

# 3. Production `.so`

Các file core có thể build bằng Cython:

```text
VBot_Multiroom.py
VBot_Multiroom_Controller.py
VBot_Audio_Bridge.py
VBot_Multiroom_Group.py
```

thành:

```text
VBot_Multiroom.cpython-39-arm-linux-gnueabihf.so
VBot_Multiroom_Controller.cpython-39-arm-linux-gnueabihf.so
VBot_Audio_Bridge.cpython-39-arm-linux-gnueabihf.so
VBot_Multiroom_Group.cpython-39-arm-linux-gnueabihf.so
```

Các file chữ thường giữ lại làm launcher:

```text
vbot_multiroom.py
vbot_multiroom_controller.py
vbot_audio_bridge.py
vbot_multiroom_group.py
```

Nguyên tắc import core:

```python
from VBot_Multiroom import ...
from VBot_Multiroom_Controller import ...
from VBot_Audio_Bridge import ...
from VBot_Multiroom_Group import ...
```

Không nên để core import ngược qua launcher:

```python
from vbot_multiroom import ...
```

---

# 4. Import `VBot_mDNS`

Các import cũ:

```python
from vbot_mdns import ...
```

đổi thành:

```python
from VBot_mDNS import ...
```

Ví dụ:

```python
from VBot_mDNS import (
    discover_vbots,
    host_for_device_id,
    normalize_device_id,
)
```

Do `VBot_mDNS.py` nằm ở root, các core trong `multiroom_audio` nên bảo đảm VBot root có trong `sys.path`:

```python
import os
import sys

VBOT_ROOT_PATH = os.path.abspath(
    os.path.join(
        os.path.dirname(__file__),
        "..",
        ".."
    )
)

if VBOT_ROOT_PATH not in sys.path:
    sys.path.insert(0, VBOT_ROOT_PATH)
```

sau đó mới:

```python
from VBot_mDNS import get_local_identity
```

---

# 5. Dependency

```bash
sudo apt update
sudo apt install -y ffmpeg alsa-utils
python3 -m pip install zeroconf==0.39.1
```

Kiểm tra:

```bash
ffmpeg -version
aplay --version
python3 -c "import zeroconf; print(zeroconf.__version__)"
```

---

# 6. Identity và mDNS

Mỗi VBot có `device_id` ổn định:

```text
vbot-<device_id>.local
```

Ví dụ:

```text
88a29e061cfa
→ vbot-88a29e061cfa.local

d83add38ef88
→ vbot-d83add38ef88.local
```

VBot chính gọi:

```python
from VBot_mDNS import register_vbot_mdns, unregister_vbot_mdns

register_vbot_mdns()
```

Khi shutdown:

```python
unregister_vbot_mdns()
```

Một Zeroconf instance publish:

```text
_vbot-assistant._tcp.local.
_vbot-multiroom._tcp.local.
```

Multiroom daemon không được publish trùng. Khi tạo publisher:

```python
self.mdns = VBotMDNSPublisher(
    display_name=name,
    control_port=9850,
    audio_port=9851,
    sync_port=9852,
    publish=False,
)
```

Mục đích: tránh `NonUniqueNameException`.

---

# 7. Không lưu IP DHCP

Group chỉ lưu `device_id`:

```json
{
  "version": 1,
  "groups": {
    "tang_1": {
      "name": "Tầng 1",
      "members": [
        "88a29e061cfa",
        "d83add38ef88"
      ]
    }
  }
}
```

Luồng resolve:

```text
device_id
  ↓
vbot-<device_id>.local
  ↓
mDNS
  ↓
IP hiện tại
```

---

# 8. Network / Socket

```text
TCP 9850  Control
UDP 9851  Audio
UDP 9852  Clock Sync
```

Controller socket:

```text
/tmp/vbot_multiroom_controller.sock
```

Audio Bridge socket:

```text
/tmp/vbot_audio_bridge.sock
```

Route state:

```text
/tmp/vbot_multiroom_route.json
```

---

# 9. Audio format

```text
PCM             S16_LE
Sample rate     48000 Hz
Channels        2
Sample width    16-bit
Packet duration 5 ms
Frames/packet   240
PCM/packet      960 bytes
```

PCM raw xấp xỉ:

```text
1.536 Mb/s
```

chưa tính UDP/header.

---

# 10. snd-aloop / Audio Bridge

Load Loopback:

```bash
sudo modprobe snd-aloop
```

Kiểm tra:

```bash
ls /sys/module/snd_aloop
```

Đường media:

```text
VLC
 ↓
plughw:Loopback,0,0
 ↓
snd-aloop
 ↓
plughw:Loopback,1,0
 ↓
Audio Bridge
 ├── LOCAL      → aplay default
 └── MULTIROOM  → Controller → AudioSender
```

TTS/system sound có thể tiếp tục phát local qua ALSA default.

---

# 11. Service Multiroom Daemon

Source:

```text
/home/pi/VBot_Offline/resource/multiroom_audio/vbot-multiroom.service
```

Target:

```text
/etc/systemd/system/vbot-multiroom.service
```

```ini
[Unit]
Description=VBot Multiroom Audio Daemon
After=network-online.target sound.target
Wants=network-online.target sound.target

[Service]
Type=simple
User=pi
Group=pi
WorkingDirectory=/home/pi/VBot_Offline/resource/multiroom_audio
Environment=PYTHONUNBUFFERED=1
Environment=PYTHONPATH=/home/pi/VBot_Offline
Environment=VBOT_MULTIROOM_CONFIG=/home/pi/VBot_Offline/Config.json
ExecStart=/usr/bin/python3 /home/pi/VBot_Offline/resource/multiroom_audio/vbot_multiroom.py daemon
Restart=always
RestartSec=2
TimeoutStopSec=5
KillMode=control-group

[Install]
WantedBy=multi-user.target
```

Cài/update:

```bash
sudo cp \
/home/pi/VBot_Offline/resource/multiroom_audio/vbot-multiroom.service \
/etc/systemd/system/vbot-multiroom.service

sudo systemctl daemon-reload
sudo systemctl restart vbot-multiroom.service
```

Kiểm tra:

```bash
systemctl status vbot-multiroom.service --no-pager -l
ss -lntp | grep 9850
ss -lnup | grep 9852
```

Expected:

```text
ClockSync UDP listening 0.0.0.0:9852
Control TCP listening 0.0.0.0:9850
```

---

# 12. Service Multiroom Controller

Source:

```text
/home/pi/VBot_Offline/resource/multiroom_audio/vbot-multiroom-controller.service
```

Target:

```text
/etc/systemd/system/vbot-multiroom-controller.service
```

```ini
[Unit]
Description=VBot Multiroom Controller Service
After=network-online.target sound.target vbot-multiroom.service
Wants=network-online.target sound.target
Requires=vbot-multiroom.service

[Service]
Type=simple
User=pi
Group=pi
WorkingDirectory=/home/pi/VBot_Offline/resource/multiroom_audio
Environment=PYTHONUNBUFFERED=1
Environment=PYTHONPATH=/home/pi/VBot_Offline
Environment=VBOT_MULTIROOM_CONFIG=/home/pi/VBot_Offline/Config.json
ExecStart=/usr/bin/python3 /home/pi/VBot_Offline/resource/multiroom_audio/vbot_multiroom_controller.py serve
Restart=always
RestartSec=2
TimeoutStopSec=5
KillMode=control-group

[Install]
WantedBy=multi-user.target
```

Cài/update:

```bash
sudo cp \
/home/pi/VBot_Offline/resource/multiroom_audio/vbot-multiroom-controller.service \
/etc/systemd/system/vbot-multiroom-controller.service

sudo systemctl daemon-reload
sudo systemctl restart vbot-multiroom-controller.service
```

Kiểm tra:

```bash
systemctl status vbot-multiroom-controller.service --no-pager -l
ls -l /tmp/vbot_multiroom_controller.sock
```

---

# 13. Service Audio Bridge

Source:

```text
/home/pi/VBot_Offline/resource/multiroom_audio/vbot-multiroom-bridge.service
```

Target:

```text
/etc/systemd/system/vbot-multiroom-bridge.service
```

```ini
[Unit]
Description=VBot Audio Bridge Service
After=sound.target
Wants=sound.target

[Service]
Type=simple
User=pi
Group=pi
WorkingDirectory=/home/pi/VBot_Offline/resource/multiroom_audio
Environment=PYTHONUNBUFFERED=1
Environment=VBOT_MULTIROOM_CONFIG=/home/pi/VBot_Offline/Config.json
ExecStart=/usr/bin/python3 /home/pi/VBot_Offline/resource/multiroom_audio/vbot_audio_bridge.py serve
Restart=always
RestartSec=2
TimeoutStopSec=5
KillMode=control-group

[Install]
WantedBy=multi-user.target
```

Cài/update:

```bash
sudo cp \
/home/pi/VBot_Offline/resource/multiroom_audio/vbot-multiroom-bridge.service \
    /etc/systemd/system/vbot-multiroom-bridge.service

sudo systemctl daemon-reload
sudo systemctl restart vbot-multiroom-bridge.service
```

Kiểm tra:

```bash
systemctl status vbot-multiroom-bridge.service --no-pager -l
ls -l /tmp/vbot_audio_bridge.sock
```

---

# 14. Quản lý service từ VBot

Không bắt buộc `systemctl enable` vì VBot tự kiểm tra/start/restart.

Kiểm tra cả 3:

```bash
systemctl is-active vbot-multiroom.service
systemctl is-active vbot-multiroom-controller.service
systemctl is-active vbot-multiroom-bridge.service
```

Expected:

```text
active
active
active
```

Xem log:

```bash
journalctl -u vbot-multiroom.service -n 50 --no-pager
journalctl -u vbot-multiroom-controller.service -n 50 --no-pager
journalctl -u vbot-multiroom-bridge.service -n 50 --no-pager
```

---

# 15. Chạy thủ công để debug

Dừng service trước để tránh trùng port/socket:

```bash
sudo systemctl stop vbot-multiroom.service
sudo systemctl stop vbot-multiroom-controller.service
sudo systemctl stop vbot-multiroom-bridge.service
```

Daemon:

```bash
cd /home/pi/VBot_Offline/resource/multiroom_audio

PYTHONPATH=/home/pi/VBot_Offline \
VBOT_MULTIROOM_CONFIG=/home/pi/VBot_Offline/Config.json \
python3 vbot_multiroom.py daemon
```

Hoặc:

```bash
python3 vbot_multiroom.py daemon \
    --name "VBot Phòng Ngủ" \
    --alsa-device default
```

Controller:

```bash
python3 vbot_multiroom_controller.py serve
```

Bridge:

```bash
sudo modprobe snd-aloop
python3 vbot_audio_bridge.py serve
```

---

# 16. Tên VBot từ Config.json

Daemon có thể đọc tên từ:

```json
{
  "contact_info": {
    "full_name": "Vũ Tuyển DEV 😃"
  }
}
```

Do đó production service không cần hard-code `--name`.

`--name` chủ yếu dùng khi test thủ công.

---

# 17. Group Manager

Devices:

```bash
python3 vbot_multiroom_group.py devices
```

Tạo group:

```bash
python3 vbot_multiroom_group.py create \
    --id tang_1 \
    --name "Tầng 1" \
    --member 88a29e061cfa \
    --member d83add38ef88
```

List:

```bash
python3 vbot_multiroom_group.py list
```

Show:

```bash
python3 vbot_multiroom_group.py show tang_1
```

Rename:

```bash
python3 vbot_multiroom_group.py rename tang_1 \
    --name "Phòng Khách + Phòng Ngủ"
```

Add member:

```bash
python3 vbot_multiroom_group.py add tang_1 \
    --member d83add38ef88
```

Remove member:

```bash
python3 vbot_multiroom_group.py remove tang_1 \
    --member d83add38ef88
```

Set member:

```bash
python3 vbot_multiroom_group.py set-members tang_1 \
    --member 88a29e061cfa \
    --member d83add38ef88
```

Delete:

```bash
python3 vbot_multiroom_group.py delete tang_1
```

---

# 18. Audio Bridge status

```bash
python3 vbot_audio_bridge.py status
```

Local điển hình:

```text
mode                     = local
capture_device           = plughw:Loopback,1,0
vlc_output_device        = plughw:Loopback,0,0
local_playback_device    = default
local_passthrough_running= true
controller_state         = idle
```

Multiroom điển hình:

```text
mode                     = multiroom
local_passthrough_running= false
group_id                 = tang_1
controller_state         = playing
controller_source_type   = alsa_capture
```

---

# 19. LOCAL → MULTIROOM

Bắt đầu:

```bash
python3 vbot_audio_bridge.py multiroom-start tang_1
```

Kiểm tra:

```bash
python3 vbot_audio_bridge.py status
python3 vbot_multiroom_controller.py status
```

Controller nên thấy:

```text
state       = playing
group_id    = tang_1
source_type = alsa_capture
file        = alsa://plughw:Loopback,1,0
```

Quay về local:

```bash
python3 vbot_audio_bridge.py multiroom-stop
```

Expected:

```text
mode = local
local_passthrough_running = true
```

---

# 20. Controller status

```bash
python3 vbot_multiroom_controller.py status
```

Ví dụ:

```json
{
  "ok": true,
  "state": "playing",
  "group_id": "tang_1",
  "file": "alsa://plughw:Loopback,1,0",
  "source_type": "alsa_capture",
  "stream_id": 1819876343,
  "group_volume": 40,
  "speakers": [
    {
      "id": "88a29e061cfa",
      "name": "Vũ Tuyển DEV 😃",
      "host": "vbot-88a29e061cfa.local",
      "volume": 40,
      "muted": false
    },
    {
      "id": "d83add38ef88",
      "name": "VBot Phòng Ngủ",
      "host": "vbot-d83add38ef88.local",
      "volume": 40,
      "muted": false
    }
  ],
  "coordinator_host": "vbot-88a29e061cfa.local",
  "remote_control": false
}
```

State chính:

```text
idle
starting
playing
paused
finished
stopped
error
```

---

# 21. Pause / Resume

```bash
python3 vbot_multiroom_controller.py pause
python3 vbot_multiroom_controller.py resume
```

Pause giữ stream/timeline, không tạo stream mới.

---

# 22. Volume

Toàn group:

```bash
python3 vbot_multiroom_controller.py volume 50
```

Riêng Phòng Ngủ:

```bash
python3 vbot_multiroom_controller.py volume 50 \
    --speaker-id d83add38ef88
```

Nhiều loa:

```bash
python3 vbot_multiroom_controller.py volume 40 \
    --speaker-id 88a29e061cfa \
    --speaker-id d83add38ef88
```

---

# 23. Mute / Unmute toàn group

Sau cập nhật hiện tại, bỏ `--speaker-id` nghĩa là áp dụng toàn bộ loa active.

Mute:

```bash
python3 vbot_multiroom_controller.py mute
```

Unmute:

```bash
python3 vbot_multiroom_controller.py unmute
```

---

# 24. Mute / Unmute riêng loa

Mute Phòng Ngủ:

```bash
python3 vbot_multiroom_controller.py mute \
    --speaker-id d83add38ef88
```

Unmute:

```bash
python3 vbot_multiroom_controller.py unmute \
    --speaker-id d83add38ef88
```

Nếu parser có alias `--speaker` thì cũng có thể dùng:

```bash
python3 vbot_multiroom_controller.py mute \
    --speaker d83add38ef88
```

Mute không thay đổi giá trị volume gốc.

---

# 25. Add / Remove loa live

Command hiện tại là số ít:

```text
add-speaker
remove-speaker
```

Remove:

```bash
python3 vbot_multiroom_controller.py remove-speaker \
    --speaker-id d83add38ef88
```

Add lại:

```bash
python3 vbot_multiroom_controller.py add-speaker \
    --speaker-id d83add38ef88
```

Nhiều loa vẫn được bằng cách lặp `--speaker-id`:

```bash
python3 vbot_multiroom_controller.py add-speaker \
    --speaker-id d83add38ef88 \
    --speaker-id abcdef123456
```

Live Join không restart stream hiện tại.

---

# 26. Stop

Dừng Controller session:

```bash
python3 vbot_multiroom_controller.py stop
```

Nếu media đang đi qua Audio Bridge và muốn quay về local, dùng:

```bash
python3 vbot_audio_bridge.py multiroom-stop
```

---

# 27. VLC test thủ công

Phát file vào Loopback:

```bash
cvlc \
  --aout=alsa \
  --alsa-audio-device=plughw:Loopback,0,0 \
  "/home/pi/Music/test.mp3"
```

Bridge local:

```text
VLC → Loopback → Bridge → ALSA default
```

Sau đó:

```bash
python3 vbot_audio_bridge.py multiroom-start tang_1
```

thì VLC vẫn phát liên tục nhưng output chuyển thành:

```text
VLC → Loopback → Bridge → Controller → Multiroom
```

---

# 28. Receiver Phòng Ngủ

Nếu Phòng Ngủ chỉ làm receiver, tối thiểu cần:

```text
mDNS
vbot_multiroom daemon
```

Chạy foreground:

```bash
cd /home/pi/VBot_Offline/resource/multiroom_audio

PYTHONPATH=/home/pi/VBot_Offline \
python3 vbot_multiroom.py daemon \
    --name "VBot Phòng Ngủ" \
    --alsa-device default
```

Nếu muốn Phòng Ngủ cũng có khả năng làm source/coordinator:

```bash
python3 vbot_multiroom_controller.py serve
sudo modprobe snd-aloop
python3 vbot_audio_bridge.py serve
```

---

# 29. Debug mDNS

Kiểm tra module:

```bash
PYTHONPATH=/home/pi/VBot_Offline \
python3 -c "import VBot_mDNS; print(VBot_mDNS.__file__)"
```

Expected:

```text
/home/pi/VBot_Offline/VBot_mDNS.py
```

Signature publisher:

```bash
PYTHONPATH=/home/pi/VBot_Offline \
python3 -c "import VBot_mDNS, inspect; print(inspect.signature(VBot_mDNS.VBotMDNSPublisher.__init__))"
```

Expected:

```text
(self, display_name, control_port=9850, audio_port=9851, sync_port=9852, publish=True)
```

Environment service:

```bash
systemctl show vbot-multiroom.service -p Environment
```

phải có:

```text
PYTHONPATH=/home/pi/VBot_Offline
```

---

# 30. Debug port / socket

```bash
ss -lntp | grep 9850
ss -lnup | grep 9851
ss -lnup | grep 9852
```

```bash
ls -l /tmp/vbot_multiroom_controller.sock
ls -l /tmp/vbot_audio_bridge.sock
```

---

# 31. Debug ALSA

```bash
aplay -l
aplay -L
arecord -l
arecord -L
```

Process giữ playback:

```bash
sudo fuser -v /dev/snd/pcm*C*D*p 2>/dev/null
```

Process giữ capture:

```bash
sudo fuser -v /dev/snd/pcm*C*D*c 2>/dev/null
```

Test Loopback trực tiếp:

```bash
arecord \
  -D plughw:Loopback,1,0 \
  -t raw \
  -f S16_LE \
  -r 48000 \
  -c 2 \
| aplay \
  -D default \
  -t raw \
  -f S16_LE \
  -r 48000 \
  -c 2
```

---

# 32. Clock Sync

```bash
python3 vbot_multiroom.py clock-test \
    vbot-d83add38ef88.local \
    --samples 20
```

Theo dõi:

```text
RTT
clock offset
delta
discard
reject
```

Offset monotonic tuyệt đối giữa hai Pi có thể lớn do thời gian boot khác nhau; delta/độ ổn định quan trọng hơn.

---

# 33. Jitter / Audio stats

Các metric debug:

```text
recv
missing
late_drop
overflow_drop
buffer
seq
alsa_slow
alsa_max
```

Packet cũ hơn playback sequence phải bị drop để tránh stale buffer.

---

# 34. Route state / Mesh Control

```text
/tmp/vbot_multiroom_route.json
```

Ví dụ:

```json
{
  "version": 1,
  "active": true,
  "stream_id": 123456789,
  "coordinator_host": "vbot-88a29e061cfa.local",
  "last_stream_id": 123456789,
  "last_coordinator_host": "vbot-88a29e061cfa.local"
}
```

Một stream chỉ có một coordinator/source.

---

# 35. Kiểm tra import trước khi build `.so`

```bash
cd /home/pi/VBot_Offline/resource/multiroom_audio
```

Tìm import mDNS cũ:

```bash
grep -R "from vbot_mdns\|import vbot_mdns" -n .
```

Tìm core đang import launcher:

```bash
grep -R \
"from vbot_multiroom\|from vbot_multiroom_controller\|from vbot_multiroom_group\|from vbot_audio_bridge" \
-n .
```

Core nên dùng:

```python
from VBot_mDNS import ...
from VBot_Multiroom import ...
from VBot_Multiroom_Controller import ...
from VBot_Multiroom_Group import ...
from VBot_Audio_Bridge import ...
```

---

# 36. Build / Strip `.so`

Pipeline:

```text
.py source
   ↓
Cython
   ↓
.c
   ↓
gcc
   ↓
.so
   ↓
backup_so
   ↓
strip --strip-unneeded
```

Test import trước khi xóa source core:

```bash
python3 -c "import VBot_Multiroom; print('OK')"
python3 -c "import VBot_Multiroom_Controller; print('OK')"
python3 -c "import VBot_Audio_Bridge; print('OK')"
python3 -c "import VBot_Multiroom_Group; print('OK')"
```

Test launcher:

```bash
python3 vbot_multiroom.py --help
python3 vbot_multiroom_controller.py --help
python3 vbot_audio_bridge.py status
python3 vbot_multiroom_group.py --help
```

`.so` giúp che source Python tốt hơn nhưng không phải mã hóa mật mã tuyệt đối.

---

# 37. Help command

Engine:

```bash
python3 vbot_multiroom.py --help
python3 vbot_multiroom.py daemon --help
python3 vbot_multiroom.py discover --help
python3 vbot_multiroom.py ping --help
python3 vbot_multiroom.py clock-test --help
```

Controller:

```bash
python3 vbot_multiroom_controller.py --help
python3 vbot_multiroom_controller.py serve --help
python3 vbot_multiroom_controller.py capture --help
python3 vbot_multiroom_controller.py pause --help
python3 vbot_multiroom_controller.py resume --help
python3 vbot_multiroom_controller.py volume --help
python3 vbot_multiroom_controller.py mute --help
python3 vbot_multiroom_controller.py unmute --help
python3 vbot_multiroom_controller.py add-speaker --help
python3 vbot_multiroom_controller.py remove-speaker --help
python3 vbot_multiroom_controller.py stop --help
python3 vbot_multiroom_controller.py status --help
```

Group:

```bash
python3 vbot_multiroom_group.py --help
python3 vbot_multiroom_group.py devices --help
python3 vbot_multiroom_group.py create --help
python3 vbot_multiroom_group.py list --help
python3 vbot_multiroom_group.py show --help
python3 vbot_multiroom_group.py rename --help
python3 vbot_multiroom_group.py add --help
python3 vbot_multiroom_group.py remove --help
python3 vbot_multiroom_group.py set-members --help
python3 vbot_multiroom_group.py delete --help
```

---

# 38. Quy trình test end-to-end

```bash
cd /home/pi/VBot_Offline/resource/multiroom_audio
```

1. Kiểm tra service:

```bash
systemctl is-active vbot-multiroom.service
systemctl is-active vbot-multiroom-controller.service
systemctl is-active vbot-multiroom-bridge.service
```

2. Kiểm tra group:

```bash
python3 vbot_multiroom_group.py show tang_1
```

3. Kiểm tra Bridge local:

```bash
python3 vbot_audio_bridge.py status
```

4. Cho VLC/media phát vào Loopback.

5. Chuyển Multiroom:

```bash
python3 vbot_audio_bridge.py multiroom-start tang_1
```

6. Status:

```bash
python3 vbot_audio_bridge.py status
python3 vbot_multiroom_controller.py status
```

7. Pause/Resume:

```bash
python3 vbot_multiroom_controller.py pause
python3 vbot_multiroom_controller.py resume
```

8. Volume:

```bash
python3 vbot_multiroom_controller.py volume 40
python3 vbot_multiroom_controller.py volume 50 --speaker-id d83add38ef88
```

9. Mute/Unmute:

```bash
python3 vbot_multiroom_controller.py mute
python3 vbot_multiroom_controller.py unmute
python3 vbot_multiroom_controller.py mute --speaker-id d83add38ef88
python3 vbot_multiroom_controller.py unmute --speaker-id d83add38ef88
```

10. Remove/Add live:

```bash
python3 vbot_multiroom_controller.py remove-speaker --speaker-id d83add38ef88
python3 vbot_multiroom_controller.py add-speaker --speaker-id d83add38ef88
```

11. Quay về local:

```bash
python3 vbot_audio_bridge.py multiroom-stop
```

12. Kiểm tra:

```bash
python3 vbot_audio_bridge.py status
```

Expected:

```text
mode = local
local_passthrough_running = true
```

---

# 39. Những phần đã hoàn thành

```text
✓ mDNS tích hợp trong VBot chính
✓ Assistant + Multiroom dùng cùng cơ chế Zeroconf
✓ chống publish mDNS trùng
✓ device_id + hostname .local ổn định
✓ TCP Control 9850
✓ UDP Audio 9851
✓ UDP Clock Sync 9852
✓ Multiroom daemon service
✓ Multiroom controller service
✓ Audio Bridge service
✓ snd-aloop
✓ VLC → Loopback → Bridge
✓ LOCAL ↔ MULTIROOM không restart VLC
✓ pause/resume
✓ group volume
✓ per-speaker volume
✓ group mute/unmute
✓ per-speaker mute/unmute
✓ live add/remove speaker
✓ launcher/core chuẩn bị cho Cython .so
```

---

# 40. Các mục nên hoàn thiện tiếp

```text
1. API/PHP/UI cho Group, Speaker, Volume, Mute và playback.
2. Coordinator arbitration khi nhiều VBot cùng muốn làm source.
3. Audio Output Manager cho Shairport/Bluetooth/TTS/Media.
4. Persistent per-speaker volume nếu cần.
5. Authentication/token cho Control Layer LAN.
6. Opus/FLAC nếu cần giảm băng thông khi số loa tăng.
7. Seek/position/duration.
8. Next/Previous/Playlist.
9. UI hiển thị RTT/jitter/packet loss.
10. Recovery/fallback khi receiver mất mạng.
```

---

# 41. Quick Reference

```bash
cd /home/pi/VBot_Offline/resource/multiroom_audio
```

Service:

```bash
sudo systemctl restart vbot-multiroom.service
sudo systemctl restart vbot-multiroom-controller.service
sudo systemctl restart vbot-multiroom-bridge.service
```

Status:

```bash
python3 vbot_multiroom_controller.py status
python3 vbot_audio_bridge.py status
```

Group:

```bash
python3 vbot_multiroom_group.py devices
python3 vbot_multiroom_group.py list
python3 vbot_multiroom_group.py show tang_1
```

Multiroom start:

```bash
python3 vbot_audio_bridge.py multiroom-start tang_1
```

Multiroom stop/local:

```bash
python3 vbot_audio_bridge.py multiroom-stop
```

Pause/Resume:

```bash
python3 vbot_multiroom_controller.py pause
python3 vbot_multiroom_controller.py resume
```

Volume group:

```bash
python3 vbot_multiroom_controller.py volume 50
```

Volume Phòng Ngủ:

```bash
python3 vbot_multiroom_controller.py volume 50 --speaker-id d83add38ef88
```

Mute group:

```bash
python3 vbot_multiroom_controller.py mute
python3 vbot_multiroom_controller.py unmute
```

Mute Phòng Ngủ:

```bash
python3 vbot_multiroom_controller.py mute --speaker-id d83add38ef88
python3 vbot_multiroom_controller.py unmute --speaker-id d83add38ef88
```

Remove/Add Phòng Ngủ:

```bash
python3 vbot_multiroom_controller.py remove-speaker --speaker-id d83add38ef88
python3 vbot_multiroom_controller.py add-speaker --speaker-id d83add38ef88
```

Port:

```bash
ss -lntp | grep 9850
ss -lnup | grep 9851
ss -lnup | grep 9852
```

Socket:

```bash
ls -l /tmp/vbot_multiroom_controller.sock
ls -l /tmp/vbot_audio_bridge.sock
```

---

# 42. Sơ đồ cuối cùng

```text
Media Player / VLC
        ↓
plughw:Loopback,0,0
        ↓
     snd-aloop
        ↓
plughw:Loopback,1,0
        ↓
   Audio Bridge
   ┌────┴───────────────┐
   ↓                    ↓
 LOCAL              MULTIROOM
   ↓                    ↓
aplay default       Controller
                        ↓
                    AudioSender
                 ┌──────┴──────┐
                 ↓             ↓
              VBot DEV   VBot Phòng Ngủ
```

Nguyên tắc chính:

```text
device_id là định danh lâu dài.
mDNS resolve IP động.
Group không lưu IP.
VBot chính sở hữu mDNS.
Daemon không publish mDNS trùng.
Launcher .py chỉ là entry point.
Logic production có thể nằm trong .so.
Audio Bridge chuyển LOCAL ↔ MULTIROOM mà không restart VLC.
```
