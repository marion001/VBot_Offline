import os
import pvporcupine
import sys
import json
import random

def get_first_ppn_file(directory):
    # Lấy danh sách tất cả các tệp .ppn trong thư mục
    ppn_files = [filename for filename in os.listdir(directory) if filename.endswith(".ppn")]
    
    # Kiểm tra xem có tệp .ppn nào không
    if not ppn_files:
        return None
    
    # Chọn ngẫu nhiên một tệp .ppn từ danh sách
    return random.choice(ppn_files)
    
# Kiểm tra xem có đủ tham số được truyền không
if len(sys.argv) > 3:
    # Lấy giá trị của các tham số từ dòng lệnh
    key = sys.argv[1]
    lang = sys.argv[2]
    model_file = sys.argv[3]
else:
    # Nếu không có đủ tham số, yêu cầu người dùng nhập
    key = input("Vui lòng nhập key Picovoice: ")
    lang = input("Vui lòng nhập ngôn ngữ (ví dụ: 'vi' cho tiếng Việt, 'eng' cho tiếng anh): ")
    model_file = input("Vui lòng nhập đường dẫn tệp mô hình file.pv: ")

# Đường dẫn chứa các tệp .ppn
model_path = f'{lang}'
model_file_path = f'{model_file}'

result = {
    'success': False,
    'message': ''
}

try:
    # Lấy tệp .ppn đầu tiên từ thư mục
    ppn_file = get_first_ppn_file(model_path)
    
    if ppn_file is None:
        result['message'] = 'Không tìm thấy tệp .ppn trong thư mục.'
        print(json.dumps(result, ensure_ascii=False))
        sys.exit(1)

    # Tạo đối tượng Picovoice
    porcupine = pvporcupine.create(
        access_key=key,
        sensitivities=[0.5],
        keyword_paths=[os.path.join(model_path, ppn_file)],
        model_path=model_file_path
    )
    # Sử dụng xử lý chuỗi
    last_part_str_lang = lang.rstrip('/').split('/')[-1]
    
    # Thay đổi giá trị của last_part_str_lang dựa trên ngôn ngữ
    if last_part_str_lang == 'vi':
        language_name = 'Tiếng Việt'
    elif last_part_str_lang == 'eng':
        language_name = 'Tiếng Anh'
    else:
        language_name = 'Không Xác Định'
    
    result['success'] = True
    result['lang'] = last_part_str_lang
    result['hotword_random_test'] = ppn_file
    result['language_name'] = language_name
    result['model_file_path'] = model_file_path
    result['message'] = f'Token Picovoice Hợp Lệ'
    print(json.dumps(result, ensure_ascii=False))

except pvporcupine.PorcupineInvalidArgumentError as e:
    result['message'] = f'Lỗi: {e}'
    print(json.dumps(result, ensure_ascii=False))

except Exception as e:
    result['message'] = f'Đã xảy ra lỗi: {e}'
    print(json.dumps(result, ensure_ascii=False))
