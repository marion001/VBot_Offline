#Code By: Vũ Tuyển
#GitHub VBot: https://github.com/marion001/VBot_Offline.git
#Facebook Group: https://www.facebook.com/groups/1148385343358824
#Facebook: https://www.facebook.com/TWFyaW9uMDAx

import json
from pvrecorder import PvRecorder  

def show_micro_devices():
    try:
        devices = PvRecorder.get_available_devices()
        
        if devices:
            devices_list = [{"ID": index, "Tên": device} for index, device in enumerate(devices)]
            response = {
                "success": True,
                "message": "Danh sách thiết bị Micro được lấy thành công.",
                "devices": devices_list
            }
        else:
            response = {
                "success": False,
                "message": "Không tìm thấy thiết bị Micro nào.",
                "devices": []
            }
        
    except Exception as e:
        response = {
            "success": False,
            "message": f"Đã xảy ra lỗi: {str(e)}",
            "devices": []
        }
    
    # Trả về chuỗi JSON
    return json.dumps(response, ensure_ascii=False, indent=4)

# Gọi hàm và in kết quả JSON
json_output = show_micro_devices()
print(json_output)
