import os
import re
import subprocess

def replace_directory_path(new_dir_path):
    file_path = "/etc/apache2/apache2.conf"
    try:
        with open(file_path, 'r') as file:
            content = file.read()
        directory_block_pattern = re.compile(
            r"(<Directory\s+(?P<path>/var/www/.*?|/home/.*?)/?>)(.*?</Directory>)", re.DOTALL
        )
        def replace_block(match):
            old_dir_path = match.group(1)
            #print(f"- Đã tìm thấy khối đường dẫn cũ: {old_dir_path.strip()}")
            new_block = f"<Directory {new_dir_path}>\n" \
                        f"        Options Indexes FollowSymLinks\n" \
                        f"        AllowOverride None\n" \
                        f"        Require all granted\n" \
                        f"</Directory>"
            return new_block
        content = directory_block_pattern.sub(replace_block, content)
        with open(file_path, 'w') as file:
            file.write(content)
        print(f"- Đã thay thế apache2.conf thành công với đường dẫn mới: '{new_dir_path}'")
    except Exception as e:
        print(f"- Lỗi khi sửa file {file_path}: {e}")

def replace_document_root_in_sites(new_doc_root):
    file_path = "/etc/apache2/sites-available/000-default.conf"
    try:
        with open(file_path, 'r') as file:
            content = file.read()
        document_root_pattern = re.compile(r"(DocumentRoot\s+)(/home/pi/[^ ]+|/var/www/[^ ]+)")
        content_updated = document_root_pattern.sub(r"\1" + new_doc_root, content)
        with open(file_path, 'w') as file:
            file.write(content_updated)
        print(f"- Đã thay thế 000-default.conf với đường dẫn mới: '{new_doc_root}'")
    except Exception as e:
        print(f"- Lỗi khi sửa file {file_path}: {e}")

def reload_apache():
    try:
        print("- Đang reload lại Apache server...")
        subprocess.run(["sudo", "systemctl", "reload", "apache2"], check=True)
        print("- Apache đã được reload thành công")
        print("- Đường dẫn WebUI đã hoàn tất thay đổi, Hãy truy cập vào WebUI để kiểm tra")
    except subprocess.CalledProcessError as e:
        print(f"Lỗi khi reload Apache: {e}")
        
def main():
    print("Vui lòng nhập đường dẫn mới (ví dụ: /home/pi/VBot_Offline/html):")
    new_dir_path = input("Đường dẫn mới của bạn: ").strip()
    if not new_dir_path.startswith("/"):
        print("Đường dẫn không hợp lệ. Đường dẫn phải bắt đầu bằng dấu '/'")
        return
    replace_directory_path(new_dir_path)
    replace_document_root_in_sites(new_dir_path)
    reload_apache()

if __name__ == "__main__":
    # Chạy script
    if os.geteuid() != 0:
        print("- Vui lòng chạy script với quyền root bằng lệnh: (sudo)")
    else:
        main()
