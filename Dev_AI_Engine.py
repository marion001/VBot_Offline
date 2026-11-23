'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

"""
HƯỚNG DẪN SỬ DỤNG (Dev Custom AI - Dev_AI_Engine.py)
- File này được VBot tự động gọi khi bạn chọn:
  Chế Độ Xử Lý Dữ Liệu = "Áp dụng AI để xử lý dữ liệu"
  và Chọn AI Xử Lý Dữ Liệu = "Dev Custom AI - Dev_AI_Engine.py" (dev_ai_engine).
- Giữ nguyên tên hàm ``async def dev_ai_engine(text_input: str)`` và định dạng trả về:
  1) Gọi 1 hoặc nhiều Tool VBot:
     {
         "type": "function_call",
         "name": calls[0]["name"],
         "args": calls[0]["args"],
         "calls": calls,   # [{"name": "...", "args": {...}}, ...]
     }
  2) Trả lời văn bản trò chuyện bình thường:
     {"type": "text", "text": "Câu trả lời tiếng Việt ngắn gọn"}
  3) Trả về ``None`` khi lỗi (VBot sẽ tự động chuyển dự phòng nếu đang bật fallback).
- Mẫu bên dưới sử dụng chuẩn Groq AI / OpenAI Chat Completions + Function Calling.
  Bạn có thể dễ dàng đổi ``API_URL``, ``API_KEY``, ``MODEL_NAME`` sang Groq, OpenAI,
  OpenRouter, DeepSeek, Ollama LAN (http://192.168.1.x:11434/v1/chat/completions)...
"""

import json
import re
import aiohttp
import Lib
from AI_Skill_Registry import get_active_openai_tools
from AI_Engine import (
    _build_system_prompt,
    _enrich_user_input_with_tool_context,
    _get_recent_history,
    _log_ai_error,
    extract_ai_emotion,
)

# ==============================================================================
# CẤU HÌNH TÙY CHỈNH CỦA NGƯỜI DÙNG (MẪU GROQ AI / OPENAI COMPATIBLE)
# ==============================================================================
DEV_AI_API_URL = "https://api.groq.com/openai/v1/chat/completions"
DEV_AI_API_KEY = "gsk_YOUR_API_KEY_HERE"
DEV_AI_MODEL_NAME = "openai/gpt-oss-20b"
DEV_AI_TIMEOUT = 10.0
DEV_AI_MAX_TOKENS = 512
DEV_AI_TEMPERATURE = 0.2


async def dev_ai_engine(text_input: str):
    """
    Hàm chính do VBot gọi khi chọn 'Dev Custom AI - Dev_AI_Engine.py'.
    """
    text_input = (text_input or "").strip()
    if not text_input:
        return None

    api_key = (DEV_AI_API_KEY or "").strip()
    if not api_key or api_key == "gsk_YOUR_API_KEY_HERE":
        _log_ai_error("[Dev AI Engine] Chưa cấu hình DEV_AI_API_KEY trong file Dev_AI_Engine.py!")
        return None

    # Lấy system prompt kèm vị trí phòng loa (speaker_location) và ngữ cảnh thiết bị vừa thao tác
    sys_text = _build_system_prompt()

    messages = [{"role": "system", "content": sys_text}]
    for item in _get_recent_history():
        role = "assistant" if item.get("role") == "model" else "user"
        parts = item.get("parts") or []
        content = str(parts[0].get("text", "")).strip() if parts else ""
        if content:
            messages.append({"role": role, "content": content})
    messages.append({"role": "user", "content": _enrich_user_input_with_tool_context(text_input)})

    # Tự động lấy danh sách các Tool đang active = true trong Config.json (chuẩn OpenAI/Groq)
    tools_payload = get_active_openai_tools()

    payload = {
        "model": DEV_AI_MODEL_NAME,
        "messages": messages,
        "max_tokens": DEV_AI_MAX_TOKENS,
        "temperature": DEV_AI_TEMPERATURE,
    }
    if "gpt-oss" in DEV_AI_MODEL_NAME.lower():
        payload["reasoning_effort"] = "low"
    if tools_payload:
        payload["tools"] = tools_payload
        payload["tool_choice"] = "auto"

    headers = {
        "Content-Type": "application/json",
        "Authorization": f"Bearer {api_key}",
    }

    try:
        timeout = aiohttp.ClientTimeout(total=float(DEV_AI_TIMEOUT))
        shared_s = await Lib.get_shared_http_session() if hasattr(Lib, "get_shared_http_session") else None
        if shared_s:
            async with Lib.shared_http_request(shared_s, "POST", DEV_AI_API_URL, retries=0, headers=headers, json=payload, timeout=timeout) as response:
                if response.status != 200:
                    err_body = await response.text()
                    _log_ai_error(f"[Dev AI Engine] HTTP Error {response.status}: {err_body}")
                    return None
                data = await response.json(content_type=None)
        else:
            async with aiohttp.ClientSession(timeout=timeout) as session:
                async with session.post(DEV_AI_API_URL, json=payload, headers=headers) as response:
                    if response.status != 200:
                        err_body = await response.text()
                        _log_ai_error(f"[Dev AI Engine] HTTP Error {response.status}: {err_body}")
                        return None
                    data = await response.json(content_type=None)
    except Exception as e:
        Lib.log_exception("Dev_AI_Engine.dev_ai_engine", e)
        _log_ai_error(f"[Dev AI Engine] Lỗi kết nối API: {e}")
        return None

    choices = data.get("choices", [])
    if not choices:
        Lib.show_log("[Dev AI Engine] API không trả về choices nào", color=Lib.Color.YELLOW)
        return None

    message = choices[0].get("message") or {}

    # 1. Xử lý trường hợp AI gọi 1 hoặc nhiều Tool VBot
    tool_calls = message.get("tool_calls") or []
    if tool_calls:
        calls = []
        for tc in tool_calls:
            fn = (tc or {}).get("function") or {}
            func_name = fn.get("name")
            if not func_name:
                continue
            raw_args = fn.get("arguments")
            if isinstance(raw_args, dict):
                func_args = raw_args
            else:
                try:
                    func_args = json.loads(raw_args) if raw_args else {}
                    if not isinstance(func_args, dict):
                        func_args = {}
                except (TypeError, ValueError):
                    func_args = {}
            calls.append({"name": func_name, "args": func_args})
            Lib.show_log(f"[Dev AI Engine] AI gọi Tool: {func_name} | Args: {func_args}", color=Lib.Color.GREEN)
        if calls:
            return {
                "type": "function_call",
                "name": calls[0]["name"],
                "args": calls[0]["args"],
                "calls": calls,
            }

    # 2. Xử lý trường hợp AI trả lời text thông thường
    text_response = str(message.get("content") or "").strip()
    if "<think>" in text_response:
        text_response = re.sub(r"<think>.*?</think>", "", text_response, flags=re.DOTALL).strip()
    if text_response:
        clean_text, emotion = extract_ai_emotion(text_response)
        if clean_text:
            Lib.show_log(f"[Dev AI Engine] AI phản hồi [{emotion}]: '{clean_text}'", color=Lib.Color.CYAN)
            return {
                "type": "text",
                "text": clean_text,
                "emotion": emotion,
            }

    finish_reason = choices[0].get("finish_reason")
    Lib.show_log(f"[Dev AI Engine] Phản hồi rỗng (finish_reason={finish_reason})", color=Lib.Color.YELLOW)
    return None
