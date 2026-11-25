# README_COMMAND.MD

# VBot Multiroom Audio - Command Reference

Tài liệu tổng hợp toàn bộ command thường dùng cho hệ thống VBot Multiroom Audio.

---

## 1. Thư mục làm việc

Các command Multiroom chính:

```bash
cd /home/pi/VBot_Offline/resource/multiroom_audio
```

mDNS nằm tại:

```bash
cd /home/pi/VBot_Offline
```

---

# 2. Device ID hiện tại dùng trong ví dụ

Loa chủ / VBot DEV:

```text
Device ID: 88a29e061cfa
Hostname : vbot-88a29e061cfa.local
```

Loa Phòng Ngủ:

```text
Device ID: d83add38ef88
Hostname : vbot-d83add38ef88.local
```

Group ví dụ:

```text
Group ID: tang_1
Name    : Tầng 1
```

---

# 3. Audio Bridge - LOCAL / MULTIROOM

## 3.1 Xem trạng thái Audio Bridge

```bash
python3 vbot_audio_bridge.py status
```

Trạng thái LOCAL mong đợi:

```json
{
  "mode": "local",
  "local_passthrough_running": true,
  "group_id": null,
  "controller_state": "idle"
}
```

Trạng thái MULTIROOM mong đợi:

```json
{
  "mode": "multiroom",
  "local_passthrough_running": false,
  "group_id": "tang_1",
  "controller_state": "playing",
  "controller_source_type": "alsa_capture"
}
```

---

## 3.2 Chuyển từ loa chủ sang Multiroom

```bash
python3 vbot_audio_bridge.py multiroom-start tang_1
```

Luồng audio:

```text
VLC
 ↓
plughw:Loopback,0,0
 ↓
snd-aloop
 ↓
plughw:Loopback,1,0
 ↓
Audio Bridge / Controller
 ↓
tang_1
 ├─ Loa chủ
 └─ Các loa phụ
```

---

## 3.3 Phát Multiroom chỉ một loa cụ thể

Chỉ Phòng Ngủ:

```bash
python3 vbot_audio_bridge.py multiroom-start tang_1 \
    --speaker-id d83add38ef88
```

Chỉ loa chủ:

```bash
python3 vbot_audio_bridge.py multiroom-start tang_1 \
    --speaker-id 88a29e061cfa
```

---

## 3.4 Chọn nhiều loa cụ thể

```bash
python3 vbot_audio_bridge.py multiroom-start tang_1 \
    --speaker-id 88a29e061cfa \
    --speaker-id d83add38ef88
```

---

## 3.5 Cho phép thử kết nối loa chưa discover được

```bash
python3 vbot_audio_bridge.py multiroom-start tang_1 \
    --allow-undiscovered
```

---

## 3.6 Dừng Multiroom và quay lại loa chủ

Đây là command chuẩn để quay về LOCAL:

```bash
python3 vbot_audio_bridge.py multiroom-stop
```

Luồng:

```text
MULTIROOM
 ↓
stop Controller capture
 ↓
nhả plughw:Loopback,1,0
 ↓
LOCAL passthrough
 ↓
loa chủ
```

---

## 3.7 Ép Audio Bridge sang LOCAL

```bash
python3 vbot_audio_bridge.py local
```

Lưu ý:

Nếu Controller Multiroom vẫn đang giữ:

```text
plughw:Loopback,1,0
```

thì lệnh này có thể báo:

```text
Device or resource busy
```

Trong trường hợp đang Multiroom, nên dùng:

```bash
python3 vbot_audio_bridge.py multiroom-stop
```

---

## 3.8 Chạy Audio Bridge foreground

```bash
python3 vbot_audio_bridge.py serve
```

Thông thường không cần chạy thủ công vì systemd đã quản lý.

---

# 4. Multiroom Controller

## 4.1 Xem trạng thái phiên

```bash
python3 vbot_multiroom_controller.py status
```

---

## 4.2 Pause

```bash
python3 vbot_multiroom_controller.py pause
```

---

## 4.3 Resume

```bash
python3 vbot_multiroom_controller.py resume
```

---

## 4.4 Stop Controller

```bash
python3 vbot_multiroom_controller.py stop
```

Lưu ý:

Command này chỉ dừng phiên Controller.

Nếu muốn:

```text
MULTIROOM → LOCAL
```

hãy dùng:

```bash
python3 vbot_audio_bridge.py multiroom-stop
```

---

# 5. Controller - phát trực tiếp file / URL

## 5.1 Phát file local vào group

```bash
python3 vbot_multiroom_controller.py play tang_1 \
    --file "/home/pi/Music/test.mp3"
```

Hoặc:

```bash
python3 vbot_multiroom_controller.py play tang_1 \
    --source "/home/pi/Music/test.mp3"
```

---

## 5.2 Phát URL

```bash
python3 vbot_multiroom_controller.py play tang_1 \
    --file "https://example.com/music.mp3"
```

---

## 5.3 Chỉ phát một speaker

```bash
python3 vbot_multiroom_controller.py play tang_1 \
    --file "/home/pi/Music/test.mp3" \
    --speaker-id d83add38ef88
```

---

## 5.4 Phát một subset speaker

```bash
python3 vbot_multiroom_controller.py play tang_1 \
    --file "/home/pi/Music/test.mp3" \
    --speaker-id 88a29e061cfa \
    --speaker-id d83add38ef88
```

---

## 5.5 Cho phép undiscovered

```bash
python3 vbot_multiroom_controller.py play tang_1 \
    --file "/home/pi/Music/test.mp3" \
    --allow-undiscovered
```

---

# 6. Controller - ALSA capture

Thông thường Audio Bridge sẽ gọi command này.

## 6.1 Capture mặc định

```bash
python3 vbot_multiroom_controller.py capture tang_1
```

Device mặc định:

```text
plughw:Loopback,1,0
```

---

## 6.2 Chỉ định ALSA capture device

```bash
python3 vbot_multiroom_controller.py capture tang_1 \
    --device plughw:Loopback,1,0
```

---

## 6.3 Capture chỉ một loa

```bash
python3 vbot_multiroom_controller.py capture tang_1 \
    --device plughw:Loopback,1,0 \
    --speaker-id d83add38ef88
```

---

## 6.4 Capture nhiều loa

```bash
python3 vbot_multiroom_controller.py capture tang_1 \
    --device plughw:Loopback,1,0 \
    --speaker-id 88a29e061cfa \
    --speaker-id d83add38ef88
```

---

# 7. Thêm / xóa speaker trong phiên đang phát

Các command này chỉ thay đổi phiên hiện tại, không sửa group JSON.

## 7.1 Thêm một loa

```bash
python3 vbot_multiroom_controller.py add-speaker \
    --speaker-id d83add38ef88
```

---

## 7.2 Thêm nhiều loa

```bash
python3 vbot_multiroom_controller.py add-speaker \
    --speaker-id d83add38ef88 \
    --speaker-id abcdef123456
```

---

## 7.3 Cho phép undiscovered khi add

```bash
python3 vbot_multiroom_controller.py add-speaker \
    --speaker-id d83add38ef88 \
    --allow-undiscovered
```

---

## 7.4 Xóa một loa khỏi phiên hiện tại

```bash
python3 vbot_multiroom_controller.py remove-speaker \
    --speaker-id d83add38ef88
```

---

## 7.5 Xóa nhiều loa

```bash
python3 vbot_multiroom_controller.py remove-speaker \
    --speaker-id d83add38ef88 \
    --speaker-id abcdef123456
```

---

# 8. Volume

## 8.1 Volume toàn bộ phiên/group

```bash
python3 vbot_multiroom_controller.py volume 50
```

Giá trị:

```text
0 .. 100
```

---

## 8.2 Volume loa chủ

```bash
python3 vbot_multiroom_controller.py volume 60 \
    --speaker-id 88a29e061cfa
```

---

## 8.3 Volume Phòng Ngủ

```bash
python3 vbot_multiroom_controller.py volume 40 \
    --speaker-id d83add38ef88
```

---

## 8.4 Volume nhiều speaker

```bash
python3 vbot_multiroom_controller.py volume 50 \
    --speaker-id 88a29e061cfa \
    --speaker-id d83add38ef88
```

---

# 9. Mute / Unmute

## 9.1 Mute Phòng Ngủ

```bash
python3 vbot_multiroom_controller.py mute \
    --speaker-id d83add38ef88
```

---

## 9.2 Unmute Phòng Ngủ

```bash
python3 vbot_multiroom_controller.py unmute \
    --speaker-id d83add38ef88
```

---

## 9.3 Mute nhiều speaker

```bash
python3 vbot_multiroom_controller.py mute \
    --speaker-id 88a29e061cfa \
    --speaker-id d83add38ef88
```

---

## 9.4 Unmute nhiều speaker

```bash
python3 vbot_multiroom_controller.py unmute \
    --speaker-id 88a29e061cfa \
    --speaker-id d83add38ef88
```

---

# 10. Scan / Discovery Device

## 10.1 Scan VBot bằng Group Manager

```bash
python3 vbot_multiroom_group.py devices
```

---

## 10.2 Scan với timeout 3 giây

```bash
python3 vbot_multiroom_group.py devices --timeout 3
```

---

## 10.3 Scan trực tiếp bằng mDNS

```bash
cd /home/pi/VBot_Offline

python3 VBot_mDNS.py discover
```

---

## 10.4 Scan mDNS timeout 3 giây

```bash
python3 VBot_mDNS.py discover --timeout 3
```

---

# 11. Identity / Resolve Device

## 11.1 Xem identity của VBot hiện tại

```bash
cd /home/pi/VBot_Offline

python3 VBot_mDNS.py identity
```

---

## 11.2 Resolve Device ID

Loa chủ:

```bash
python3 VBot_mDNS.py resolve \
    --id 88a29e061cfa
```

Phòng Ngủ:

```bash
python3 VBot_mDNS.py resolve \
    --id d83add38ef88
```

---

## 11.3 Resolve hostname .local

```bash
python3 VBot_mDNS.py resolve \
    --host vbot-88a29e061cfa.local
```

Hoặc:

```bash
python3 VBot_mDNS.py resolve \
    --host vbot-d83add38ef88.local
```

---

# 12. Debug advertise mDNS riêng

```bash
python3 VBot_mDNS.py advertise \
    --name "VBot Test"
```

Chỉ định port:

```bash
python3 VBot_mDNS.py advertise \
    --name "VBot Test" \
    --control-port 9850 \
    --audio-port 9851 \
    --sync-port 9852
```

---

# 13. Group Manager

Các command:

```text
devices
create
list
show
rename
add
remove
set-members
delete
play
```

---

## 13.1 Tạo group `tang_1`

```bash
cd /home/pi/VBot_Offline/resource/multiroom_audio

python3 vbot_multiroom_group.py create \
    --id tang_1 \
    --name "Tầng 1" \
    --member 88a29e061cfa \
    --member d83add38ef88
```

---

## 13.2 Tạo / ghi đè group đã tồn tại

```bash
python3 vbot_multiroom_group.py create \
    --id tang_1 \
    --name "Tầng 1" \
    --member 88a29e061cfa \
    --member d83add38ef88 \
    --replace
```

---

## 13.3 Liệt kê toàn bộ group

```bash
python3 vbot_multiroom_group.py list
```

---

## 13.4 Xem chi tiết group

```bash
python3 vbot_multiroom_group.py show tang_1
```

Timeout 3 giây:

```bash
python3 vbot_multiroom_group.py show tang_1 --timeout 3
```

---

## 13.5 Đổi tên group

```bash
python3 vbot_multiroom_group.py rename tang_1 \
    --name "Phòng Khách + Phòng Ngủ"
```

---

## 13.6 Thêm member vào group

```bash
python3 vbot_multiroom_group.py add tang_1 \
    --member d83add38ef88
```

---

## 13.7 Thêm nhiều member

```bash
python3 vbot_multiroom_group.py add tang_1 \
    --member d83add38ef88 \
    --member abcdef123456
```

---

## 13.8 Xóa member khỏi group

```bash
python3 vbot_multiroom_group.py remove tang_1 \
    --member d83add38ef88
```

---

## 13.9 Xóa nhiều member

```bash
python3 vbot_multiroom_group.py remove tang_1 \
    --member d83add38ef88 \
    --member abcdef123456
```

---

## 13.10 Đặt lại toàn bộ member

```bash
python3 vbot_multiroom_group.py set-members tang_1 \
    --member 88a29e061cfa \
    --member d83add38ef88
```

---

## 13.11 Xóa toàn bộ member khỏi group

```bash
python3 vbot_multiroom_group.py set-members tang_1
```

---

## 13.12 Xóa group

```bash
python3 vbot_multiroom_group.py delete tang_1
```

---

# 14. Group Manager - phát file

## 14.1 Phát toàn bộ group

```bash
python3 vbot_multiroom_group.py play tang_1 \
    --file "/home/pi/Music/test.mp3"
```

---

## 14.2 Chỉ phát một member

```bash
python3 vbot_multiroom_group.py play tang_1 \
    --file "/home/pi/Music/test.mp3" \
    --speaker-id d83add38ef88
```

---

## 14.3 Chọn nhiều member

```bash
python3 vbot_multiroom_group.py play tang_1 \
    --file "/home/pi/Music/test.mp3" \
    --speaker-id 88a29e061cfa \
    --speaker-id d83add38ef88
```

---

## 14.4 Cho phép undiscovered

```bash
python3 vbot_multiroom_group.py play tang_1 \
    --file "/home/pi/Music/test.mp3" \
    --allow-undiscovered
```

---

# 15. Phân biệt Group Config và Live Session

## Sửa group cố định

```bash
python3 vbot_multiroom_group.py add ...
python3 vbot_multiroom_group.py remove ...
python3 vbot_multiroom_group.py set-members ...
```

Các command này sửa cấu hình group lâu dài.

---

## Sửa speaker của phiên đang phát

```bash
python3 vbot_multiroom_controller.py add-speaker ...
python3 vbot_multiroom_controller.py remove-speaker ...
```

Các command này chỉ thay đổi stream hiện tại.

---

# 16. Multiroom daemon

## 16.1 Chạy daemon foreground

Ví dụ:

```bash
python3 vbot_multiroom.py daemon \
    --id 88a29e061cfa \
    --name "Vũ Tuyển DEV 😃"
```

---

## 16.2 Chỉ định ALSA output

```bash
python3 vbot_multiroom.py daemon \
    --id 88a29e061cfa \
    --name "Vũ Tuyển DEV 😃" \
    --alsa-device default
```

---

## 16.3 Calibration output delay

```bash
python3 vbot_multiroom.py daemon \
    --id 88a29e061cfa \
    --name "Vũ Tuyển DEV 😃" \
    --alsa-device default \
    --output-delay-ms 10
```

---

# 17. Multiroom daemon - test Control Layer

```bash
python3 vbot_multiroom.py ping vbot-d83add38ef88.local
```

Hoặc:

```bash
python3 vbot_multiroom.py ping vbot-88a29e061cfa.local
```

---

# 18. Multiroom daemon - phát file trực tiếp kiểu thấp tầng

Ví dụ:

```bash
python3 vbot_multiroom.py play \
    --file "/home/pi/Music/test.mp3" \
    --speaker "Phòng Khách=192.168.14.175" \
    --speaker "Phòng Ngủ=192.168.14.113"
```

Chỉ định coordinator IP:

```bash
python3 vbot_multiroom.py play \
    --file "/home/pi/Music/test.mp3" \
    --speaker "Phòng Khách=192.168.14.175" \
    --speaker "Phòng Ngủ=192.168.14.113" \
    --coordinator-ip 192.168.14.175
```

Lưu ý:

Đây là command debug / tầng thấp.

Trong VBot nên ưu tiên:

```bash
python3 vbot_audio_bridge.py multiroom-start tang_1
```

---

# 19. systemd Service

Các service:

```text
vbot-multiroom.service
vbot-multiroom-controller.service
vbot-multiroom-bridge.service
```

---

## 19.1 Kiểm tra trạng thái cả 3

```bash
systemctl status \
    vbot-multiroom.service \
    vbot-multiroom-controller.service \
    vbot-multiroom-bridge.service \
    --no-pager
```

---

## 19.2 Kiểm tra active nhanh

```bash
systemctl is-active vbot-multiroom.service
systemctl is-active vbot-multiroom-controller.service
systemctl is-active vbot-multiroom-bridge.service
```

Mong đợi:

```text
active
active
active
```

---

## 19.3 Start đúng thứ tự

```bash
sudo systemctl start vbot-multiroom.service
sudo systemctl start vbot-multiroom-controller.service
sudo systemctl start vbot-multiroom-bridge.service
```

---

## 19.4 Stop đúng thứ tự

```bash
sudo systemctl stop vbot-multiroom-bridge.service
sudo systemctl stop vbot-multiroom-controller.service
sudo systemctl stop vbot-multiroom.service
```

---

## 19.5 Restart đúng thứ tự

```bash
sudo systemctl restart vbot-multiroom.service
sleep 1

sudo systemctl restart vbot-multiroom-controller.service
sleep 1

sudo systemctl restart vbot-multiroom-bridge.service
```

---

## 19.6 Sau khi sửa file `.service`

```bash
sudo systemctl daemon-reload
```

Sau đó:

```bash
sudo systemctl restart vbot-multiroom.service
sudo systemctl restart vbot-multiroom-controller.service
sudo systemctl restart vbot-multiroom-bridge.service
```

---

## 19.7 Enable khi boot

```bash
sudo systemctl enable vbot-multiroom.service
sudo systemctl enable vbot-multiroom-controller.service
sudo systemctl enable vbot-multiroom-bridge.service
```

---

## 19.8 Disable

```bash
sudo systemctl disable vbot-multiroom-bridge.service
sudo systemctl disable vbot-multiroom-controller.service
sudo systemctl disable vbot-multiroom.service
```

---

# 20. Log systemd

## 20.1 Multiroom daemon

```bash
journalctl \
    -u vbot-multiroom.service \
    -n 100 \
    --no-pager
```

---

## 20.2 Controller

```bash
journalctl \
    -u vbot-multiroom-controller.service \
    -n 100 \
    --no-pager
```

---

## 20.3 Audio Bridge

```bash
journalctl \
    -u vbot-multiroom-bridge.service \
    -n 100 \
    --no-pager
```

---

## 20.4 Xem realtime cả 3

```bash
journalctl \
    -u vbot-multiroom.service \
    -u vbot-multiroom-controller.service \
    -u vbot-multiroom-bridge.service \
    -f
```

---

## 20.5 Log 2 phút gần nhất

```bash
journalctl \
    -u vbot-multiroom.service \
    -u vbot-multiroom-controller.service \
    -u vbot-multiroom-bridge.service \
    --since "-2 min" \
    --no-pager
```

---

# 21. snd-aloop

## 21.1 Kiểm tra module

```bash
lsmod | grep snd_aloop
```

---

## 21.2 Load snd-aloop

```bash
sudo modprobe snd-aloop
```

---

## 21.3 Kiểm tra ALSA playback device

```bash
aplay -l
```

---

## 21.4 Kiểm tra ALSA playback aliases

```bash
aplay -L
```

---

## 21.5 Kiểm tra capture device

```bash
arecord -l
```

---

## 21.6 Kiểm tra capture aliases

```bash
arecord -L
```

---

# 22. Xem process đang giữ ALSA

## 22.1 Playback

```bash
sudo fuser -v /dev/snd/pcm*C*D*p 2>/dev/null
```

---

## 22.2 Capture

```bash
sudo fuser -v /dev/snd/pcm*C*D*c 2>/dev/null
```

Dùng khi gặp:

```text
Device or resource busy
```

---

# 23. Test Loopback

## 23.1 Test Loopback trực tiếp

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

## 23.2 Ghi 5 giây từ Loopback

Trong lúc VLC đang phát vào Loopback:

```bash
arecord \
    -D plughw:Loopback,1,0 \
    -f S16_LE \
    -r 48000 \
    -c 2 \
    -t wav \
    -d 5 \
    /tmp/loopback_test.wav
```

Nghe lại:

```bash
aplay -D default /tmp/loopback_test.wav
```

---

# 24. VLC - phát vào snd-aloop

```bash
cvlc \
    --play-and-exit \
    --aout=alsa \
    --alsa-audio-device=plughw:Loopback,0,0 \
    "/home/pi/Music/test.mp3"
```

Hoặc không tự exit:

```bash
cvlc \
    --aout=alsa \
    --alsa-audio-device=plughw:Loopback,0,0 \
    "/home/pi/Music/test.mp3"
```

---

# 25. Port Multiroom

## 25.1 Control TCP 9850

```bash
ss -lntp | grep 9850
```

---

## 25.2 Audio UDP 9851

```bash
ss -lnup | grep 9851
```

---

## 25.3 Clock Sync UDP 9852

```bash
ss -lnup | grep 9852
```

---

# 26. Unix Socket

## Controller socket

```bash
ls -l /tmp/vbot_multiroom_controller.sock
```

---

## Audio Bridge socket

```bash
ls -l /tmp/vbot_audio_bridge.sock
```

---

# 27. Route state

```bash
cat /tmp/vbot_multiroom_route.json
```

Ví dụ:

```json
{
  "version": 1,
  "active": true,
  "stream_id": 1819876343,
  "coordinator_host": "vbot-88a29e061cfa.local"
}
```

---

# 28. Process

```bash
ps aux | grep -E \
'vbot_multiroom|VBot_Multiroom|vbot_audio_bridge|VBot_Audio_Bridge' \
| grep -v grep
```

---

# 29. Test import module

## Multiroom

```bash
cd /home/pi/VBot_Offline/resource/multiroom_audio

python3 -c "import VBot_Multiroom; print(VBot_Multiroom.__file__)"
```

---

## Controller

```bash
python3 -c "import VBot_Multiroom_Controller; print(VBot_Multiroom_Controller.__file__)"
```

---

## Audio Bridge

```bash
python3 -c "import VBot_Audio_Bridge; print(VBot_Audio_Bridge.__file__)"
```

---

## Group

```bash
python3 -c "import VBot_Multiroom_Group; print(VBot_Multiroom_Group.__file__)"
```

---

## mDNS

```bash
cd /home/pi/VBot_Offline

python3 -c "import VBot_mDNS; print(VBot_mDNS.__file__)"
```

---

# 30. Workflow thông dụng

## 30.1 VBot boot mặc định

```text
VBot startup
 ↓
vbot-multiroom.service
 ↓
vbot-multiroom-controller.service
 ↓
vbot-multiroom-bridge.service
 ↓
LOCAL
 ↓
loa chủ
```

Kiểm tra:

```bash
python3 vbot_audio_bridge.py status
```

---

## 30.2 LOCAL → MULTIROOM

```bash
python3 vbot_audio_bridge.py multiroom-start tang_1
```

---

## 30.3 MULTIROOM → LOCAL

```bash
python3 vbot_audio_bridge.py multiroom-stop
```

---

## 30.4 Pause Multiroom

```bash
python3 vbot_multiroom_controller.py pause
```

---

## 30.5 Resume Multiroom

```bash
python3 vbot_multiroom_controller.py resume
```

---

## 30.6 Thêm loa realtime

```bash
python3 vbot_multiroom_controller.py add-speaker \
    --speaker-id d83add38ef88
```

---

## 30.7 Xóa loa realtime

```bash
python3 vbot_multiroom_controller.py remove-speaker \
    --speaker-id d83add38ef88
```

---

## 30.8 Volume toàn nhóm

```bash
python3 vbot_multiroom_controller.py volume 50
```

---

## 30.9 Volume từng loa

```bash
python3 vbot_multiroom_controller.py volume 40 \
    --speaker-id d83add38ef88
```

---

# 31. Workflow tạo group từ đầu

## Bước 1 - Scan device

```bash
python3 vbot_multiroom_group.py devices
```

## Bước 2 - Tạo group

```bash
python3 vbot_multiroom_group.py create \
    --id tang_1 \
    --name "Tầng 1" \
    --member 88a29e061cfa \
    --member d83add38ef88
```

## Bước 3 - Kiểm tra

```bash
python3 vbot_multiroom_group.py show tang_1
```

## Bước 4 - Phát group

```bash
python3 vbot_audio_bridge.py multiroom-start tang_1
```

## Bước 5 - Quay lại loa chủ

```bash
python3 vbot_audio_bridge.py multiroom-stop
```

---

# 32. Các command quan trọng nhất để tích hợp API/UI

## Scan loa

```bash
python3 vbot_multiroom_group.py devices
```

## List group

```bash
python3 vbot_multiroom_group.py list
```

## Show group

```bash
python3 vbot_multiroom_group.py show tang_1
```

## Create group

```bash
python3 vbot_multiroom_group.py create \
    --id tang_1 \
    --name "Tầng 1" \
    --member 88a29e061cfa \
    --member d83add38ef88
```

## Update toàn bộ member

```bash
python3 vbot_multiroom_group.py set-members tang_1 \
    --member 88a29e061cfa \
    --member d83add38ef88
```

## Delete group

```bash
python3 vbot_multiroom_group.py delete tang_1
```

## LOCAL → MULTIROOM

```bash
python3 vbot_audio_bridge.py multiroom-start tang_1
```

## MULTIROOM → LOCAL

```bash
python3 vbot_audio_bridge.py multiroom-stop
```

## Controller status

```bash
python3 vbot_multiroom_controller.py status
```

## Bridge status

```bash
python3 vbot_audio_bridge.py status
```

## Volume

```bash
python3 vbot_multiroom_controller.py volume 50
```

## Volume từng loa

```bash
python3 vbot_multiroom_controller.py volume 50 \
    --speaker-id d83add38ef88
```

## Mute

```bash
python3 vbot_multiroom_controller.py mute \
    --speaker-id d83add38ef88
```

## Unmute

```bash
python3 vbot_multiroom_controller.py unmute \
    --speaker-id d83add38ef88
```

## Add speaker realtime

```bash
python3 vbot_multiroom_controller.py add-speaker \
    --speaker-id d83add38ef88
```

## Remove speaker realtime

```bash
python3 vbot_multiroom_controller.py remove-speaker \
    --speaker-id d83add38ef88
```

---

# 33. Quy tắc nên dùng trong VBot

```text
vbot_multiroom_group.py
→ discovery + quản lý group cố định

vbot_audio_bridge.py
→ chuyển LOCAL ↔ MULTIROOM

vbot_multiroom_controller.py
→ điều khiển phiên đang phát

VBot_mDNS.py
→ identity / discovery / resolve

vbot_multiroom.py
→ daemon + low-level debug
```

Khi người dùng chọn một group:

```bash
python3 vbot_audio_bridge.py multiroom-start <group_id>
```

Khi người dùng chọn quay về loa chính:

```bash
python3 vbot_audio_bridge.py multiroom-stop
```

Không nên dùng:

```bash
python3 vbot_audio_bridge.py local
```

để thoát Multiroom khi Controller vẫn đang giữ ALSA.

---

# 34. Command help

Audio Bridge:

```bash
python3 vbot_audio_bridge.py --help
```

Controller:

```bash
python3 vbot_multiroom_controller.py --help
```

Group Manager:

```bash
python3 vbot_multiroom_group.py --help
```

Multiroom daemon:

```bash
python3 vbot_multiroom.py --help
```

mDNS:

```bash
cd /home/pi/VBot_Offline
python3 VBot_mDNS.py --help
```

---

# 35. Cheat Sheet ngắn

```bash
cd /home/pi/VBot_Offline/resource/multiroom_audio

# Scan
python3 vbot_multiroom_group.py devices

# Group
python3 vbot_multiroom_group.py list
python3 vbot_multiroom_group.py show tang_1

# LOCAL -> Multiroom
python3 vbot_audio_bridge.py multiroom-start tang_1

# Multiroom -> LOCAL
python3 vbot_audio_bridge.py multiroom-stop

# Bridge status
python3 vbot_audio_bridge.py status

# Controller status
python3 vbot_multiroom_controller.py status

# Pause / Resume
python3 vbot_multiroom_controller.py pause
python3 vbot_multiroom_controller.py resume

# Volume
python3 vbot_multiroom_controller.py volume 50

# Add / Remove speaker realtime
python3 vbot_multiroom_controller.py add-speaker \
    --speaker-id d83add38ef88

python3 vbot_multiroom_controller.py remove-speaker \
    --speaker-id d83add38ef88

# Service
systemctl is-active vbot-multiroom.service
systemctl is-active vbot-multiroom-controller.service
systemctl is-active vbot-multiroom-bridge.service

# Log realtime
journalctl \
    -u vbot-multiroom.service \
    -u vbot-multiroom-controller.service \
    -u vbot-multiroom-bridge.service \
    -f
```

---

**VBot Multiroom Audio Command Reference**
