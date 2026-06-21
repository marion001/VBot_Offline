"""
Custom async assistant implementation.

Keep the public signature unchanged so Assistant.Call_async can dispatch here.
"""

import Lib
import TTS_Processing
import aiohttp


async def dev_assistant(text_input, used_for=None):
    text_input = (text_input or "").strip()
    if not text_input:
        return None, None

    Lib.show_log(f"[DEV Assistant] Input: {text_input}", color=Lib.Color.RED)

    api_key_gemini = "AAAAAAAAAAAAAAAAA_BBBBBBBBB"
    url_api = (
        "https://generativelanguage.googleapis.com/v1beta/models/"
        f"gemini-1.5-flash-latest:generateContent?key={api_key_gemini}"
    )
    payload = {"contents": [{"parts": [{"text": text_input}]}]}
    timeout = aiohttp.ClientTimeout(total=float(Lib.time_out_google_gemini))

    try:
        async with aiohttp.ClientSession(timeout=timeout) as session:
            async with session.post(url_api, json=payload) as response:
                response_text = await response.text()
                if response.status != 200:
                    error = f"[DEV Assistant] API error {response.status}: {response_text}"
                    Lib.show_log(error, color=Lib.Color.RED)
                    return None, error
                response_data = await response.json(content_type=None)
    except (aiohttp.ClientError, Lib.asyncio.TimeoutError, ValueError) as exc:
        error = f"[DEV Assistant] API connection error: {exc}"
        Lib.show_log(error, color=Lib.Color.RED)
        return None, error

    candidates = response_data.get("candidates", [])
    parts = candidates[0].get("content", {}).get("parts", []) if candidates else []
    text_gemini = parts[0].get("text", "").strip() if parts else ""
    if not text_gemini:
        error = "[DEV Assistant] Gemini returned no valid content"
        Lib.show_log(error, color=Lib.Color.RED)
        return None, error

    Lib.show_log(f"[DEV Assistant] Gemini result: {text_gemini}", color=Lib.Color.GREEN)
    tts_gemini = await TTS_Processing.Select_TTS_async(text_gemini)
    return tts_gemini, text_gemini
