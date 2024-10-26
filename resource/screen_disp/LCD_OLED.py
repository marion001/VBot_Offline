import time
import subprocess
import os
import json

# Lấy đường dẫn đến file hiện tại
current_file_path = os.path.abspath(__file__)

# Lùi lại 2 thư mục
new_path = os.path.abspath(os.path.join(current_file_path, "../../.."))

# Đường dẫn đến file Config.json
config_file_path = os.path.join(new_path, "Config.json")

# In ra đường dẫn để kiểm tra
#print("Đường dẫn file Config:", config_file_path)

# Đọc và phân tích nội dung file Config.json
try:
    with open(config_file_path, 'r') as file:
        config = json.load(file)
        #print("Nội dung file Config:", config)
except FileNotFoundError:
    print(f"File Config.json không tồn tại, tại {config_file_path}")
except KeyError as e:
    print(f"Lỗi KeyError: {e}")

#Màn Hình được kích hoạt hay không
display_screen_active = config['display_screen']['active']
#Kiểu kết nối màn hình
display_screen_connection_type = config['display_screen']['connection_type']
#Hiển thị văn bản đầu tiên
text_display_center = config['display_screen']['text_display_center']

oled_display_screen_type = config['display_screen']['lcd_i2c']['screen_type']

if display_screen_active and display_screen_connection_type == "lcd_i2c":
    import Adafruit_GPIO.SPI as SPI
    import Adafruit_SSD1306
    from PIL import Image, ImageDraw, ImageFont
    # Khởi tạo OLED
    RST = 0
    disp = getattr(Adafruit_SSD1306, oled_display_screen_type)(rst=RST)
    disp.begin()
    disp.clear()
    disp.display()
    # Thiết lập các thông số hiển thị
    width = disp.width
    height = disp.height
    image1 = Image.new('1', (width, height))
    draw = ImageDraw.Draw(image1)
    #padding = -2
    top = -2
    font = ImageFont.load_default()


def draw_multiline_text(text, x, y):
    """
    Vẽ văn bản nhiều dòng trên màn hình OLED.
    """
    words = text.split(" ")
    current_line = ""
    line_height = font.getsize("hg")[1]  # Chiều cao của một dòng

    for word in words:
        if draw.textsize(current_line + word, font=font)[0] <= width:
            current_line += word + " "
        else:
            draw.text((x, y), current_line, font=font, fill=255)
            y += line_height
            current_line = word + " "

    if current_line:
        draw.text((x, y), current_line, font=font, fill=255)

def get_system_info():
    """
    Lấy thông tin hệ thống và hiển thị trên màn hình OLED.
    """
    try:
        IP = subprocess.check_output("hostname -I | cut -d\' \' -f1", shell=True).decode('utf-8').strip()
    except subprocess.CalledProcessError:
        IP = "N/A"

    try:
        Board_Name = subprocess.check_output("cat /proc/device-tree/model", shell=True).decode('utf-8').strip()
    except subprocess.CalledProcessError:
        Board_Name = "N/A"

    try:
        Host_Name = subprocess.check_output("hostname", shell=True).decode('utf-8').strip()
    except subprocess.CalledProcessError:
        Host_Name = "N/A"

    try:
        CPU = subprocess.check_output("top -bn1 | grep load | awk '{printf \"CPU Load: %.2f\", $(NF-2)}'", shell=True).decode('utf-8').strip()
    except subprocess.CalledProcessError:
        CPU = "N/A"

    try:
        SSID = subprocess.check_output("iwgetid -r", shell=True).decode('utf-8').strip()
    except subprocess.CalledProcessError:
        SSID = "N/A"

    if display_screen_connection_type == "lcd_i2c":
        if oled_display_screen_type == "SSD1306_128_64":
            # Xóa màn hình trước khi vẽ
            draw.rectangle((0, 0, width, height), outline=0, fill=0)

            # Tính vị trí x để căn giữa cho dòng "VBot Assistant"
            text_width, text_height = draw.textsize(text_display_center, font=font)
            x_centered = (width - text_width) // 2

            # Vẽ các thông tin khác
            draw_multiline_text(text_display_center, x_centered, top)
            draw_multiline_text("Board: " + Board_Name, 0, top + 10)
            draw_multiline_text("Wifi: " + SSID, 0, top + 33)
            draw_multiline_text("IP: " + str(IP), 0, top + 44)
            draw_multiline_text(str(CPU), 0, top + 55)

            # Hiển thị thông tin
            disp.image(image1)
            disp.display()
        elif oled_display_screen_type == "lcd_spi":
            pass

VBot_Offline_Status_AutoRun = None

def auto_run_display():
    """
    Chạy vòng lặp kiểm tra trạng thái dịch vụ và hiển thị thông tin.
    """
    display_count = 0
    #Số lần hiển thị thông tin khi VBot chạy Auto
    vbot_display_count = 3  
    while True:
        if display_screen_active:
            try:
                # Kiểm tra trạng thái dịch vụ VBot_Offline.service
                service_status = subprocess.check_output(
                    "systemctl --user show -p ActiveState VBot_Offline.service", 
                    shell=True
                ).decode('utf-8').strip()
                # Trả về trạng thái
                service_status = service_status.split("=")[1]
            
                #print(f"Trạng thái service_status: {service_status}")
                # Nếu dịch vụ đang chạy
                if service_status == "active":
                    VBot_Offline_Status_AutoRun = True
                    #print("VBot_Offline.service Đang Chạy")
                    if display_count < vbot_display_count:
                        get_system_info()
                        display_count += 1
                        #print(f"VBot_Offline.service Đang Chạy, hiển thị thông tin lần: {display_count}")
                        time.sleep(5)
                        continue
                    
                    #Nếu đủ Số lần đếm hiển thị sẽ chờ 10 giây và kiểm tra lại xem VBot_Offline.service Đang Chạy hay không
                    else:
                        #print(f"Vbot đang chạy auto, chờ 10 giây để kiểm tra lại")
                        time.sleep(10)
                        continue
                else:
                    VBot_Offline_Status_AutoRun = False
                
                #Hiển thị thông tin Mỗi 10 giây khi VBot Không Chạy Auto
                if not VBot_Offline_Status_AutoRun:
                    #Reset biến đếm nếu dịch vụ không chạy
                    display_count = 0
                    #print("đang Hiển thị thông tin khi vbot không chạy")
                    get_system_info()
                    time.sleep(10)
            except Exception as e:
                # In ra lỗi nếu có
                # Xóa màn hình trước khi vẽ
                draw.rectangle((0, 0, width, height), outline=0, fill=0)
                draw_multiline_text("Error: " + str(e), 0, top + 5)
                disp.image(image1)
                disp.display()
                time.sleep(15)
#auto_run_display()