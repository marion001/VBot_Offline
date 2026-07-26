def _format_uptime(seconds):
    seconds = max(0, int(seconds))
    days, seconds = divmod(seconds, 86400)
    hours, seconds = divmod(seconds, 3600)
    minutes, _ = divmod(seconds, 60)

    parts = []
    if days:
        parts.append(f"{days} ngày")
    if hours:
        parts.append(f"{hours} giờ")
    parts.append(f"{minutes} phút")
    return " ".join(parts)


def handle(arguments, context):
    lib = context["Lib"]
    detail = str(arguments.get("detail", "all")).strip().lower()
    if detail not in {"all", "network", "audio", "uptime"}:
        detail = "all"

    now = lib.datetime.now().strftime("%H:%M:%S ngày %d/%m/%Y")
    uptime_seconds = lib.time.time() - lib.psutil.boot_time()
    data = {
        "time": now,
        "ip_address": str(lib.get_my_ip or "không xác định"),
        "wifi_name": str(lib.get_ssid_name or "không xác định"),
        "volume": int(lib.Volume),
        "uptime_seconds": int(uptime_seconds),
        "uptime_text": _format_uptime(uptime_seconds),
    }

    if detail == "network":
        text = (
            f"VBot đang kết nối mạng {data['wifi_name']}, "
            f"địa chỉ IP là {data['ip_address']}."
        )
        structured_content = {
            "wifi_name": data["wifi_name"],
            "ip_address": data["ip_address"],
        }
    elif detail == "audio":
        text = f"Âm lượng hiện tại của VBot là {data['volume']} phần trăm."
        structured_content = {"volume": data["volume"]}
    elif detail == "uptime":
        text = f"Hệ thống VBot đã hoạt động được {data['uptime_text']}."
        structured_content = {
            "uptime_seconds": data["uptime_seconds"],
            "uptime_text": data["uptime_text"],
        }
    else:
        text = (
            f"Bây giờ là {data['time']}. "
            f"VBot đang kết nối mạng {data['wifi_name']}, "
            f"địa chỉ IP {data['ip_address']}, "
            f"âm lượng {data['volume']} phần trăm và hệ thống đã hoạt động "
            f"được {data['uptime_text']}."
        )
        structured_content = data

    context["log"](
        f"[MCP Plugin self.vbot.system_status] Trả về trạng thái: {detail}",
        color=lib.Color.CYAN,
    )
    return {
        "content": [
            {
                "type": "text",
                "text": text,
            }
        ],
        "structuredContent": structured_content,
    }
