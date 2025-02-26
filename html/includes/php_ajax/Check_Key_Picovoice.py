#Code By: Vũ Tuyển
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx

import os
import pvporcupine
import sys
import json
import random

#Chọn Ngẫu Nhiện File .ppn từ thư mục
def get_first_ppn_file(directory):
    ppn_files = [filename for filename in os.listdir(directory) if filename.endswith(".ppn")]
    if not ppn_files:
        return None
    return random.choice(ppn_files)
    
# Kiểm tra xem có đủ tham số được truyền không
if len(sys.argv) > 3:
    key = sys.argv[1]
    lang = sys.argv[2]
    model_file = sys.argv[3]
else:
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
    porcupine = pvporcupine.create(
        access_key=key,
        sensitivities=[0.5],
        keyword_paths=[os.path.join(model_path, ppn_file)],
        model_path=model_file_path
    )
    last_part_str_lang = lang.rstrip('/').split('/')[-1]
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
