import os
import subprocess

# Mã màu ANSI
RED = "\033[91m"
GREEN = "\033[92m"
YELLOW = "\033[93m"
RESET = "\033[0m"

# Thiết lập thư mục làm việc là /home/pi
HOME_DIR = "/home/pi"
os.chdir(HOME_DIR)

def run_command(command):
    """Hàm chạy lệnh shell và in lỗi nếu có."""
    print(f"{YELLOW}Đang chạy lệnh: {command}{RESET}")
    result = subprocess.run(command, shell=True, text=True, capture_output=True)
    if result.returncode != 0:
        print(f"{RED}Lỗi khi chạy lệnh: {command}\n{result.stderr}{RESET}")
    else:
        print(f"{GREEN}{result.stdout}{RESET}")

print(f"{YELLOW}Cập nhật hệ thống...{RESET}")
run_command("sudo apt update")

print(f"{YELLOW}Xóa Dữ Liệu Trước Đó...{RESET}")
run_command(f"sudo rm -rf {HOME_DIR}/snowboy")
run_command(f"sudo rm -rf {HOME_DIR}/snowboy.egg-info")
run_command(f"sudo rm {HOME_DIR}/VBot_Offline/resource/snowboy/snowboy.egg-info")
run_command(f"sudo rm -rf {HOME_DIR}/snowboy-master")
run_command(f"sudo rm {HOME_DIR}/snowboy.zip")
run_command(f"sudo rm {HOME_DIR}/scipy-1.13.1-cp39-cp39-linux_armv7l.whl")

print(f"{YELLOW}Cài đặt các gói cần thiết...{RESET}")
run_command("sudo apt install -y swig libatlas-base-dev liblapack-dev libblas-dev libopenblas-dev")

print(f"{YELLOW}Tải và cài đặt scipy{RESET}")
run_command(f"pip install scipy")
scipy_whl = f"{HOME_DIR}/scipy-1.13.1-cp39-cp39-linux_armv7l.whl"
#run_command(f"wget -P {HOME_DIR} https://www.piwheels.org/simple/scipy/scipy-1.13.1-cp39-cp39-linux_armv7l.whl")
#run_command(f"pip install {scipy_whl}")

print(f"{YELLOW}Tải Xuống git Snowboy vào {HOME_DIR}/{RESET}")
#run_command(f"git clone https://github.com/seasalt-ai/snowboy.git {HOME_DIR}/snowboy")
run_command(f"wget https://github.com/seasalt-ai/snowboy/archive/refs/heads/master.zip -O {HOME_DIR}/snowboy.zip")
run_command(f"unzip {HOME_DIR}/snowboy.zip -d {HOME_DIR}/")
run_command(f"sudo mv {HOME_DIR}/snowboy-master {HOME_DIR}/snowboy")

print(f"{YELLOW}Biên dịch và cài đặt Snowboy{RESET}")
run_command(f"cd {HOME_DIR}/snowboy/swig/Python3 && make")
run_command(f"cd {HOME_DIR}/snowboy && sudo python3 setup.py install")

print(f"{YELLOW}Sao chép tệp Snowboy vào thư mục Python{RESET}")
run_command(f"sudo cp {HOME_DIR}/VBot_Offline/resource/snowboy/snowboydetect.py /usr/local/lib/python3.9/dist-packages/snowboy-1.3.0-py3.9.egg/snowboy/")
run_command(f"sudo cp {HOME_DIR}/VBot_Offline/resource/snowboy/snowboydecoder.py /usr/local/lib/python3.9/dist-packages/snowboy-1.3.0-py3.9.egg/snowboy/")
run_command(f"sudo cp {HOME_DIR}/VBot_Offline/resource/snowboy/_snowboydetect.so /usr/local/lib/python3.9/dist-packages/snowboy-1.3.0-py3.9.egg/snowboy/")
run_command(f"sudo cp {HOME_DIR}/VBot_Offline/resource/snowboy/common.res /usr/local/lib/python3.9/dist-packages/snowboy-1.3.0-py3.9.egg/snowboy/")

print(f"{YELLOW}Dọn dẹp xóa file{RESET}")
run_command(f"sudo rm -rf {HOME_DIR}/snowboy.egg-info")
run_command(f"sudo rm -rf {HOME_DIR}/snowboy-master")
run_command(f"sudo rm {HOME_DIR}/snowboy.zip")
run_command(f"sudo rm {HOME_DIR}/VBot_Offline/resource/snowboy/snowboy.egg-info")
#Xóa file .whl sau khi cài đặt xong
if os.path.exists(scipy_whl):
    os.remove(scipy_whl)
    print(f"Đã xóa file: {scipy_whl}")
print(f"{GREEN}Cài đặt hoàn tất!{RESET}")
