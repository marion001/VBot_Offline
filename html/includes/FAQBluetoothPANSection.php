<div class="card accordion mb-3" id="faq-bluetooth-pan">
  <div class="card-body">
    <h5 class="card-title accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_bluetooth_pan" aria-expanded="false" aria-controls="collapse_bluetooth_pan">Bluetooth PAN — Cài đặt và chia sẻ Internet từ điện thoại</h5>
    <div id="collapse_bluetooth_pan" class="accordion-collapse collapse">
      <p>Loa nhận Internet 4G/5G từ điện thoại qua Bluetooth PAN. Dùng chung tên Bluetooth và thiết bị đã ghép đôi với chức năng phát nhạc. Điện thoại cần bật chia sẻ mạng; chỉ kết nối Bluetooth âm thanh chưa đủ.</p>
      <div class="accordion mb-3" id="faq-pan-methods">
<div class="accordion-item">
  <h2 class="accordion-header" id="faq-pan-methods-auto-heading">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-pan-methods-auto" aria-expanded="false" aria-controls="faq-pan-methods-auto">Chạy file tự động</button>
  </h2>
  <div id="faq-pan-methods-auto" class="accordion-collapse collapse" aria-labelledby="faq-pan-methods-auto-heading" data-bs-parent="#faq-pan-methods">
    <div class="accordion-body"><p>Cho loa kết nối Internet bằng Wi-Fi/Ethernet trước. Mở <a href="Command.php#bluetooth-pan-setup">Command → Bluetooth PAN — Cài đặt / Kiểm tra</a>, nhấn cài đặt. Bộ cài chỉ bổ sung gói còn thiếu, chuẩn bị BNEP và script Wi-Fi, giữ thiết lập đã có khi chạy lại. Hoặc chạy qua SSH:</p>
      <pre class="bg-dark text-white p-3 rounded"><code>cd /home/pi/VBot_Offline
sudo python3 resource/bluetooth/pan_manager.py install</code></pre>
      <p>Nếu loa chưa dùng NetworkManager, bộ cài dừng và báo rõ. Cần kiểm tra/chuyển cơ chế quản lý mạng riêng; không cài NetworkManager chồng lên trình quản lý Wi-Fi khác.</p></div>
  </div>
</div>
<div class="accordion-item">
  <h2 class="accordion-header" id="faq-pan-methods-manual-heading">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-pan-methods-manual" aria-expanded="false" aria-controls="faq-pan-methods-manual">Chạy lệnh thủ công</button>
  </h2>
  <div id="faq-pan-methods-manual" class="accordion-collapse collapse" aria-labelledby="faq-pan-methods-manual-heading" data-bs-parent="#faq-pan-methods">
    <div class="accordion-body"><p>Kiểm tra NetworkManager đã có và đang hoạt động:</p>
      <pre class="bg-dark text-white p-3 rounded"><code>nmcli --version
systemctl is-active NetworkManager</code></pre>
      <p>Chỉ tiếp tục khi đã dùng NetworkManager. Cài các gói còn thiếu, không nâng cấp gói đã có:</p>
      <pre class="bg-dark text-white p-3 rounded"><code>sudo apt-get update
sudo apt-get install -y --no-upgrade --no-install-recommends bluez python3-dbus
sudo modprobe bnep
printf 'bnep\n' | sudo tee /etc/modules-load.d/vbot-pan.conf
sudo systemctl enable --now bluetooth</code></pre>
      <p>Nếu dịch vụ <code>wifi-connect</code> chạy <code>/home/pi/start-wifi-connect.sh</code>, cập nhật script mới để giữ AP cấu hình khi chưa có Wi-Fi, kể cả khi PAN đang có Internet:</p>
      <pre class="bg-dark text-white p-3 rounded"><code>systemctl cat wifi-connect
cd /home/pi/VBot_Offline
sh -n resource/wifi_manager/start-wifi-connect.sh
sudo cp -p /home/pi/start-wifi-connect.sh "/home/pi/start-wifi-connect.sh.backup.$(date +%Y%m%d_%H%M%S)"
sudo install -m 755 resource/wifi_manager/start-wifi-connect.sh /home/pi/start-wifi-connect.sh
sudo systemctl restart wifi-connect</code></pre>
      <p>Không thay script nếu dịch vụ đang dùng đường dẫn khác mà chưa kiểm tra. Mở <a href="Bluetooth_PAN.php">Mạng Bluetooth PAN</a> để bật/tắt sử dụng. Không cần restart NetworkManager hoặc chạy toàn bộ VBot. Cài thủ công không tạo dấu hoàn tất của bộ cài tự động; mục kiểm tra vẫn báo “chưa hoàn tất” cho đến khi chạy bộ cài.</p></div>
  </div>
</div>
</div>
      <h6>Cấu hình điện thoại và sử dụng</h6>
      <ol><li>Ghép đôi điện thoại với Bluetooth của loa.</li><li>iPhone: bật dữ liệu di động, Bluetooth, Điểm truy cập cá nhân → Cho phép người khác kết nối. Android: bật Chia sẻ kết nối qua Bluetooth.</li><li>Mở <a href="Bluetooth_PAN.php">Mạng Bluetooth PAN</a>, bật Kích hoạt Bluetooth PAN, nhấn Làm mới và Kết nối PAN. Hồ sơ mạng được tạo lần đầu. Có thể bật tự kết nối riêng cho điện thoại.</li></ol>
      <p>Cột <strong>Tự kết nối</strong> có ba chế độ, được lưu riêng cho từng điện thoại:</p>
      <ul><li><strong>Auto VBot</strong> (mặc định cho thiết bị chưa đặt chế độ): có Wi-Fi router thì không dùng PAN; mất Wi-Fi thì tự dùng PAN của điện thoại hiện đang kết nối Bluetooth. Khi Wi-Fi có địa chỉ trở lại, hệ thống tự ngắt PAN. AP do loa tự phát không được tính là Wi-Fi router.</li><li><strong>Bật</strong>: luôn tự kết nối PAN khi điện thoại đang kết nối Bluetooth, kể cả khi có Wi-Fi.</li><li><strong>Tắt</strong>: không tự kết nối PAN; vẫn có thể nhấn Kết nối PAN thủ công.</li></ul>
      <p>Tắt công tắc PAN chung sẽ ngừng mọi tự kết nối PAN, giữ Bluetooth âm thanh. Ngắt riêng một điện thoại sẽ đặt chế độ của điện thoại đó thành Tắt. Thiết lập giữ sau reboot tại <code>/var/lib/vbot-pan/devices.json</code>. Thiết bị chưa lưu tùy chọn dùng Auto VBot; lựa chọn Bật/Tắt đã lưu vẫn được giữ.</p>
      <h6>Kiểm tra mạng và truy cập WebUI</h6>
      <p>Nhấn <strong>Làm mới</strong> để cập nhật trạng thái PAN. Nhấn <strong>Kiểm tra Internet qua PAN</strong> để thử gateway, DNS và HTTPS qua Bluetooth, độc lập Wi-Fi. “Đã kết nối PAN” chỉ xác nhận kết nối mạng, chưa xác nhận Internet. Kết quả đo có thời gian và hết hạn sau 5 phút; không có ping chưa đủ kết luận mất mạng.</p>
      <p>Không có Wi-Fi router thì loa vẫn phát AP để cấu hình Wi-Fi, kể cả khi điện thoại đã cấp Internet qua PAN. Script vẫn thử lại Wi-Fi đã lưu theo chu kỳ, không reboot hoặc tắt Bluetooth. Khi cấu hình Wi-Fi thành công, Auto VBot nhường kết nối về Wi-Fi. Dịch vụ <code>vbot-pan-policy.timer</code> đối chiếu khoảng mỗi 10 giây, có thời gian chờ tăng dần khi điện thoại chưa cho chia sẻ mạng; không cần chạy VBot hoặc mở WebUI.</p>
      <p>Sau khi cập nhật mã nguồn, chạy lại cài đặt PAN trong Command hoặc lệnh dưới để cài dịch vụ mới và đồng bộ script đang dùng. Giữ nguyên lựa chọn bật/tắt PAN đã lưu:</p>
      <pre class="bg-dark text-white p-3 rounded"><code>cd /home/pi/VBot_Offline
sudo python3 resource/bluetooth/pan_manager.py install
systemctl status vbot-pan-policy.timer --no-pager</code></pre>
      <pre class="bg-dark text-white p-3 rounded"><code>ip -4 addr show bnep0
ip route get 1.1.1.1
ping -I bnep0 -c 5 1.1.1.1</code></pre>
      <p><code>dev bnep0</code> là dùng Bluetooth; <code>dev wlan0</code> là dùng Wi-Fi. Muốn mở WebUI qua PAN, thử <code>http://IP_BLUETOOTH_CUA_LOA</code>; khả năng truy cập phụ thuộc điện thoại. Không tắt Wi-Fi khi đang SSH qua Wi-Fi. Chạy thử PAN cùng âm thanh trước khi dùng thường xuyên.</p>
    </div>
  </div>
</div>
