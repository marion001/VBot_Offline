'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
'''

#!/usr/bin/env python3
import Broadlink_VBot

#MAIN
parser = Broadlink_VBot.argparse.ArgumentParser(description="Broadlink Tool (scan / learn / send)")
sub = parser.add_subparsers(dest="action", required=True)

#Quét thiết bị
sub.add_parser("scan")

#Học Lệnh
p_learn = sub.add_parser("learn")
p_learn.add_argument("--ip", required=True)
p_learn.add_argument("--mac", required=True)
p_learn.add_argument("--devtype", required=True)

#Gửi Lệnh
p_send = sub.add_parser("send")
p_send.add_argument("--ip", required=True)
p_send.add_argument("--mac", required=True)
p_send.add_argument("--devtype", required=True)
p_send.add_argument("--code", required=True)

args = parser.parse_args()
result = {"success": False, "message": ""}

try:
    if args.action == "scan":
        result = Broadlink_VBot.scan_broadlink_devices('/home/pi/VBot_Offline/resource/broadlink/broadlink.json')
    elif args.action == "learn":
        rm = Broadlink_VBot.create_device(args.ip, args.mac, args.devtype)
        proto, data = Broadlink_VBot.learn_auto(rm)
        if data:
            result["success"] = True
            result["message"] = f"Học lệnh thành công ({proto})"
            result["data"] = Broadlink_VBot.base64.b64encode(data).decode()
        else:
            result["message"] = "Hết thời gian chờ học lệnh"
    elif args.action == "send":
        rm = Broadlink_VBot.create_device(args.ip, args.mac, args.devtype)
        Broadlink_VBot.send_command(rm, args.code)
        result["success"] = True
        result["message"] = "Đã gửi lệnh IR/RF thành công"
except Exception as e:
    result["message"] = str(e)
print(Broadlink_VBot.json.dumps(result, ensure_ascii=False))
