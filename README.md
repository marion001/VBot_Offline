VBot Assistant, VBot là loa thông minh tiếng Việt, hỗ trợ điều khiển nhà thông minh, trả lời câu hỏi, nhắc nhở, phát nhạc và nhiều tiện ích khác. Với thiết kế tinh tế và khả năng hiểu ngôn ngữ tự nhiên, VBot mang đến sự tiện nghi và trải nghiệm hiện đại, gần gũi cho mọi gia đình Việt.
-----
- Các Bạn Có Nhu Cầu Mua Mạch VBot Assistant AIO, hoặc Nguyên 1 Chiếc Loa Liên Hệ Mình Nhé: [Facebook - Vũ Tuyển](https://www.facebook.com/TWFyaW9uMDAx)
- Mạch được thiết kế dùng với Loa Xiaodu Donkey Kong
- Group Hệ Hỗ Trợ: [Facebook - Group](https://www.facebook.com/groups/1148385343358824)
- @Email: VBot.Assistant@gmail.com
- Demo Hoàn Thiện: [Demo Video Youtube](https://youtu.be/D84jqz-Trss?si=fv9vIWn-RtkAjByl)
------------------------------------------------
- HỖ TRỢ TỐT NHẤT TRÊN Raspberry Pi Zero 2W (IMG có sẵn được Build trên Raspberry Pi Zero 2W)
- Mạch Mic ReSpeaker 2-Mics Pi HAT v1 (sử dụng ic WM8960), ReSpeaker Mic Array v2.0, Mic USB, V..v...
- Hoặc Mạch Mic Vietbot AIO Ver 2.0
- Hoặc Có Thể Sử Dụng Các Module Mic i2s Và phát âm thanh Audio I2s
- Hỗ Trợ Các Loại Đèn LED Có Mã Như: WS2812B, APPA102 (Sử dụng duy nhất chân GPIO10 trên Raspberry Pi để điều khiển led)
- --------------------------------
- Kết Nối Client Server Sử Dụng MCU Như ESP32,ESP32 D1 MINI, ESP32S3, V..v...
- Hỗ Trợ Đánh Thức Bằng Từ Khóa Sử Dụng Picovoice Và Snowboy
- Đầy Đủ Giao Tiếp API, MQTT
- Kết Nối Điều Khiển Home Assistant (HASS)
- Tùy Chỉnh Câu Lệnh Điều Khiển Thiết Bị Home Assistant (Custom Home Assistant)
- Trình Giải Trí, Nghe Nhạc, kể Chuyển, Đọc Báo, PodCast, Tin Tức Trong Ngày, Thời Sự
- Phát Nhạc Từ Danh Sách Phát (PlayList)
- Quản Lý Danh Sách Phát trên Webui
- Lên Lịch Trình, Thông Báo, Lời Nhắc, v...v....
- Sao Lưu, Backup dữ liệu lên Google Cloud Driver
- Thiết Lập Mật Khẩu Đăng Nhập Webui
- Hỗ Trợ Truy Cập WebUI VBot bên ngoài mạng Internet
- Liên kết và tương tác, ra lệnh với các thiết bị chạy VBot trong cùng lớp mạng (Loa 1 ra lệnh Control, điều khiển, phát nhạc, đọc báo, tin tức, tới Loa 2, Client Loa 1 ra lệnh tới Loa 2, V..v....)
- Tích hợp với trợ lý ảo Assist của Home Assistant (Làm Tác Nhân)
      : https://github.com/marion001/VBot-Assist-Conversation
- Liên Kết Loa VBot Vào Home Assistant
      : https://github.com/marion001/VBot_Offline_Custom_Component

- Thiết Lập Diy VBot Client Kết Nối Tới Loa VBot
      : https://github.com/marion001/VBot_Client_Offline

- Hỗ Trợ Xử Lý Đa Lệnh Trong 1 Câu Lệnh:
  
          Ví Dụ Câu Lệnh:
        - Bật đèn ngủ và tắt đèn bếp
        - Bật đèn khòng khách và đèn phòng ngủ
        - Tắt đèn bếp sau đó phát danh sách nhạc
  

- Thông Tin Đăng Nhập SSH:

        $:> user: pi
        $:> pass: vbot123
  
- Chạy VBot thủ công:

        $:> git clone https://github.com/marion001/VBot_Offline.git
        $:> cd VBot_Offline
        $:> python3 Start.py

- Hỗ trợ người dùng tự tùy biến, code theo ý muốn ở các File:

        - Code Trợ Lý Ảo: Dev_Assistant.py
        - Code Custom Skill: Dev_Customization.py
        - Code Hiệu Ứng Đèn Led: Dev_Led.py
        - Code Chuyển Văn Bản Thành Giọng Nói: Dev_TTS.py


Link Download IMG FLASH (IMG Được Build Trên Raspberry Pi Zero 2W): https://drive.google.com/drive/folders/1rB3P8rev2byxgRsXS7mAdkKRj7j0M4xZ

Phần Mềm Tìm Kiếm Thiết Bị Chạy VBot Trong Mạng Lan: https://drive.google.com/drive/folders/1wLxamxFjP96LT-rN2iobJ8M3j5nGrxs_?usp=drive_link

      Model: Raspberry Pi Zero 2 W Rev 1.0

      $:>uname -a
      Linux VBot-Assistant 6.1.21-v7+ #1642 SMP Mon Apr  3 17:20:52 BST 2023 armv7l GNU/Linux

      $:>python3 --version
      Python 3.9.2

      $:>apache2 -v
      Server version: Apache/2.4.56 (Raspbian)
      Server built:   2023-04-02T03:06:01

      $:>php -v
      PHP 7.4.33 (cli) (built: Feb 22 2023 20:07:47) ( NTS )
      Copyright (c) The PHP Group
      Zend Engine v3.4.0, Copyright (c) Zend Technologies
          with Zend OPcache v7.4.33, Copyright (c), by Zend Technologies

      $:>lsb_release -a
      No LSB modules are available.
      Distributor ID: Raspbian
      Description:    Raspbian GNU/Linux 11 (bullseye)
      Release:        11
      Codename:       bullseye

      $:>cat /etc/os-release
      PRETTY_NAME="Raspbian GNU/Linux 11 (bullseye)"
      NAME="Raspbian GNU/Linux"
      VERSION_ID="11"
      VERSION="11 (bullseye)"
      VERSION_CODENAME=bullseye
      ID=raspbian
      ID_LIKE=debian
      HOME_URL="http://www.raspbian.org/"
      SUPPORT_URL="http://www.raspbian.org/RaspbianForums"
      BUG_REPORT_URL="http://www.raspbian.org/RaspbianBugs"

      $:>vcgencmd version
      Mar 17 2023 10:53:00
      Copyright (c) 2012 Broadcom
      version 82f3750a65fadae9a38077e3c2e217ad158c8d54 (clean) (release) (start_cd)

<hr/>

![Alt text](https://github.com/user-attachments/assets/05b0eafa-6b73-42b9-ae65-e3e114faec01) 

![Alt text](https://github.com/user-attachments/assets/cd10cef1-de0e-42fc-ac41-42d8548b1da4)

![VBot3](https://github.com/user-attachments/assets/8d0c145f-20f8-4aaf-a0e4-1a40f5dc6097)

![Image](https://github.com/user-attachments/assets/efbe15fc-77e5-4ace-b458-43a70b531abe)

![Image](https://github.com/user-attachments/assets/6545cc75-f0c3-421c-808d-74ef06c0ac28)

Sơ đồ mạch Loa Thông Minh Tiếng Việt VBot Assistant DAC I2S Sử Dụng Mic INMP441 và MAX89357

Nếu sử dụng Mic INMP441 và MAX89357 thì cần sử dụng IMG có chữ 'i2s' flash vào thẻ nhớ

[VBot_i2s_bb.pdf](https://github.com/user-attachments/files/22601079/VBot_i2s_bb.pdf)
<img width="2583" height="1830" alt="Image" src="https://github.com/user-attachments/assets/96162be8-0f07-401f-86af-786372b9aba3" />
