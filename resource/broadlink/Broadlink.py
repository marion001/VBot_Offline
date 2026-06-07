'''
Code By: Vu Tuyen
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
'''

#!/usr/bin/env python3
import argparse
import json
from pathlib import Path

DEFAULT_JSON_FILE = str(Path(__file__).with_name("broadlink.json"))
DEFAULT_PORT = 80


try:
    import Broadlink_VBot
except ModuleNotFoundError as import_error:
    Broadlink_VBot = None
    BROADLINK_IMPORT_ERROR = import_error
else:
    BROADLINK_IMPORT_ERROR = None


def make_result(success=False, message="", data=None):
    result = {"success": success, "message": message}
    if data is not None:
        result["data"] = data
    return result


def build_parser():
    parser = argparse.ArgumentParser(description="Broadlink Tool (scan / learn / send)")
    sub = parser.add_subparsers(dest="action", required=True)

    p_scan = sub.add_parser("scan", help="Quet thiet bi Broadlink")
    p_scan.add_argument(
        "--json-file",
        default=getattr(Broadlink_VBot, "DEFAULT_JSON_FILE", DEFAULT_JSON_FILE),
        help="Duong dan file broadlink.json",
    )

    p_learn = sub.add_parser("learn", help="Hoc lenh IR/RF")
    p_learn.add_argument("--ip", required=True)
    p_learn.add_argument("--mac", required=True)
    p_learn.add_argument("--devtype", required=True)
    p_learn.add_argument("--port", type=int, default=getattr(Broadlink_VBot, "DEFAULT_PORT", DEFAULT_PORT))
    p_learn.add_argument("--wavetype", choices=("ir", "rf"), required=True)
    p_learn.add_argument("--timeout", type=float, default=None)

    p_send = sub.add_parser("send", help="Gui lenh IR/RF")
    p_send.add_argument("--ip", required=True)
    p_send.add_argument("--mac", required=True)
    p_send.add_argument("--devtype", required=True)
    p_send.add_argument("--port", type=int, default=getattr(Broadlink_VBot, "DEFAULT_PORT", DEFAULT_PORT))
    p_send.add_argument("--code", required=True)

    return parser


def require_broadlink_module():
    if Broadlink_VBot is None:
        raise RuntimeError(f"Khong the import Broadlink_VBot: {BROADLINK_IMPORT_ERROR}")
    return Broadlink_VBot


def handle_scan(args):
    broadlink_vbot = require_broadlink_module()
    return broadlink_vbot.scan_broadlink_devices(args.json_file)


def handle_learn(args):
    broadlink_vbot = require_broadlink_module()
    rm = broadlink_vbot.create_device(args.ip, args.mac, args.devtype, args.port)
    timeout = broadlink_vbot.normalize_timeout(args.timeout, 9 if args.wavetype == "ir" else 15)
    if args.wavetype == "ir":
        data = broadlink_vbot.learn_ir(rm, timeout=timeout)
    else:
        data = broadlink_vbot.learn_rf(rm, timeout=timeout)

    if not data:
        return make_result(False, "Het thoi gian cho hoc lenh")

    return make_result(
        True,
        f"Hoc lenh thanh cong ({args.wavetype.upper()})",
        data,
    )


def handle_send(args):
    broadlink_vbot = require_broadlink_module()
    rm = broadlink_vbot.create_device(args.ip, args.mac, args.devtype, args.port)
    if not broadlink_vbot.send_command(rm, args.code):
        return make_result(False, "Gui lenh IR/RF that bai")
    return make_result(True, "Da gui lenh IR/RF thanh cong")


def main(argv=None):
    parser = build_parser()
    args = parser.parse_args(argv)

    try:
        if args.action == "scan":
            result = handle_scan(args)
        elif args.action == "learn":
            result = handle_learn(args)
        elif args.action == "send":
            result = handle_send(args)
        else:
            result = make_result(False, "action khong hop le")
    except KeyboardInterrupt:
        result = make_result(False, "Da huy thao tac")
    except Exception as e:
        result = make_result(False, str(e))

    print(json.dumps(result, ensure_ascii=False))
    return 0 if result.get("success") else 1


if __name__ == "__main__":
    raise SystemExit(main())
