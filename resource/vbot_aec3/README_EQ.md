# EQ loa

EQ đã chuyển dữ liệu, nguồn build và thư viện native sang `resource/equalizer/`.

Xem [hướng dẫn EQ và quản lý mẫu](../equalizer/README.md).

```bash
cd /home/pi/VBot_Offline
sh resource/equalizer/build_eq.sh
```

`Config.json` chỉ chứa `media_player.equalizer.active`. Mẫu và thông số nằm trong `resource/equalizer/settings.json`; thư viện là `resource/equalizer/libvbot_eq.so`. WebUI chỉnh được khi VBot dừng, miễn dịch vụ bridge âm thanh chung vẫn chạy.
