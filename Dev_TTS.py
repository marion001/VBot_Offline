"""
Custom async TTS implementation.

Network I/O is native async. File operations remain synchronous helpers and
are executed through asyncio.to_thread.
"""

import Lib
import aiohttp


DEV_TTS_TIMEOUT = float(Lib.config.get("xiaozhi", {}).get("tts_time_out", 20))


def _write_audio_file(output_file_path, content):
    tmp_file = f"{output_file_path}.{Lib.uuid.uuid4().hex[:8]}.tmp"
    try:
        with open(tmp_file, "wb") as audio_file:
            audio_file.write(content)
        if Lib.os.path.getsize(tmp_file) < 128:
            Lib.os.remove(tmp_file)
            return None
        Lib.os.replace(tmp_file, output_file_path)
        Lib.os.chmod(output_file_path, 0o777)
        return output_file_path
    except Exception:
        if Lib.os.path.exists(tmp_file):
            Lib.os.remove(tmp_file)
        raise


async def dev_tts(text_input):
    text_input = (text_input or "").strip()
    if not text_input:
        return None

    output_file_path = Lib.os.path.join(
        Lib.directory_tts,
        Lib.tts_string(text_input) + ".mp3",
    )
    payload = {
        "speaker_id": 4,
        "speed": 0.9,
        "input": text_input,
    }
    headers = {
        "apikey": "11111111111111111111111111",
        "Content-Type": "application/x-www-form-urlencoded",
    }
    timeout = aiohttp.ClientTimeout(total=DEV_TTS_TIMEOUT)

    try:
        async with aiohttp.ClientSession(timeout=timeout) as session:
            async with session.post(
                "https://api.zalo.ai/v1/tts/synthesize",
                headers=headers,
                data=payload,
            ) as response:
                response.raise_for_status()
                response_data = await response.json(content_type=None)

            if response_data.get("error_code") != 0:
                Lib.show_log(f"[DEV TTS] API error: {response_data}", color=Lib.Color.RED)
                return None

            audio_url = response_data.get("data", {}).get("url")
            if not audio_url:
                Lib.show_log("[DEV TTS] Missing audio URL", color=Lib.Color.RED)
                return None

            async with session.get(audio_url) as audio_response:
                audio_response.raise_for_status()
                audio_content = await audio_response.read()
    except (aiohttp.ClientError, Lib.asyncio.TimeoutError, ValueError) as exc:
        Lib.show_log(f"[DEV TTS] API error: {exc}", color=Lib.Color.RED)
        return None

    try:
        saved_file = await Lib.asyncio.to_thread(
            _write_audio_file,
            output_file_path,
            audio_content,
        )
    except OSError as exc:
        Lib.show_log(f"[DEV TTS] File error: {exc}", color=Lib.Color.RED)
        return audio_url

    if not saved_file:
        Lib.show_log("[DEV TTS] Downloaded audio is invalid", color=Lib.Color.RED)
        return audio_url

    Lib.show_log(f"[DEV TTS] Saved: {saved_file}", color=Lib.Color.GREEN)
    return saved_file
