#Code By: Vũ Tuyển
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx
#Email: VBot.Assistant@gmail.com

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

#Kiểm tra xem có đủ tham số được truyền không
if len(sys.argv) > 3:
    key = sys.argv[1]
    lang = sys.argv[2]
    model_file = sys.argv[3]
else:
    key = input("Vui lòng nhập key Picovoice: ")
    lang = input("Vui lòng nhập ngôn ngữ (ví dụ: 'vi' cho tiếng Việt, 'eng' cho tiếng anh): ")
    model_file = input("Vui lòng nhập đường dẫn tệp mô hình file.pv: ")

#Đường dẫn chứa các tệp .ppn
model_path = f'{lang}'
model_file_path = f'{model_file}'
language_code = os.path.basename(model_path.rstrip('/'))

result = {
    'success': False,
    'message': ''
}

try:
    sensitivity = 0.5
    if language_code == "customize":
        vbot_path = os.path.abspath(
            os.path.join(os.path.dirname(__file__), "..", "..", "..")
        )
        if vbot_path not in sys.path:
            sys.path.insert(0, vbot_path)
        import Dev_Picovoice
        keyword_paths = [
            os.path.abspath(path if os.path.isabs(path) else os.path.join(vbot_path, path))
            for path in Dev_Picovoice.keyword_paths
            if isinstance(path, str) and path.endswith(".ppn")
        ]
        custom_model_path = Dev_Picovoice.model_file_path
        model_file_path = os.path.abspath(
            custom_model_path
            if os.path.isabs(custom_model_path)
            else os.path.join(vbot_path, custom_model_path)
        )
        if keyword_paths:
            selected_index = random.randrange(len(keyword_paths))
            keyword_path = keyword_paths[selected_index]
            if selected_index < len(Dev_Picovoice.sensitivities):
                sensitivity = float(Dev_Picovoice.sensitivities[selected_index])
        else:
            keyword_path = None
        ppn_file = os.path.basename(keyword_path) if keyword_path else None
    else:
        ppn_file = get_first_ppn_file(model_path)
        keyword_path = os.path.join(model_path, ppn_file) if ppn_file else None

    if ppn_file is None or keyword_path is None:
        result['message'] = 'Không tìm thấy tệp .ppn trong thư mục.'
        print(json.dumps(result, ensure_ascii=False))
        sys.exit(1)
    porcupine = pvporcupine.create(
        access_key=key,
        sensitivities=[sensitivity],
        keyword_paths=[keyword_path],
        model_path=model_file_path
    )
    last_part_str_lang = language_code
    if last_part_str_lang == 'vi':
        language_name = 'Tiếng Việt'
    elif last_part_str_lang == 'eng':
        language_name = 'Tiếng Anh'
    elif last_part_str_lang == 'customize':
        language_name = 'Customize (Dev_Picovoice.py)'
    else:
        language_name = 'Không Xác Định'
    result['success'] = True
    result['lang'] = last_part_str_lang
    result['hotword_random_test'] = ppn_file
    result['language_name'] = language_name
    result['model_file_path'] = model_file_path
    result['message'] = f'Token Picovoice Hợp Lệ'
    porcupine.delete()
    print(json.dumps(result, ensure_ascii=False))

except pvporcupine.PorcupineInvalidArgumentError as e:
    result['message'] = f'Lỗi: {e}'
    print(json.dumps(result, ensure_ascii=False))

except Exception as e:
    result['message'] = f'Đã xảy ra lỗi: {e}'
    print(json.dumps(result, ensure_ascii=False))
