'''
Code By: Vu Tuyen
GitHub VBot: https://github.com/marion001/VBot_Offline.git
'''

import aiohttp

import Lib


async def custom_music_async(input_text: str):
    Lib.show_log(
        f"DEV Custom Music search: {input_text}",
        color=Lib.Color.YELLOW,
    )
    params = {
        "keyword": input_text,
        "correct": "false",
        "timestamp": int(Lib.time.time() * 1000),
    }
    try:
        timeout = aiohttp.ClientTimeout(total=6, sock_connect=5, sock_read=5)
        async with aiohttp.ClientSession(timeout=timeout) as session:
            async with session.get(
                "https://graph.nhaccuatui.com/api/v3/search/all",
                params=params,
            ) as response:
                response.raise_for_status()
                data = await response.json(content_type=None)

        songs = data.get("data", {}).get("songs", [])
        if not songs:
            Lib.show_log(
                "DEV Custom Music: Khong tim thay bai hat",
                color=Lib.Color.RED,
            )
            return None, None, None, None

        song = songs[0]
        streams = song.get("streamURL", [])
        selected = next(
            (item for item in streams if item.get("type") == "320"),
            None,
        )
        selected = selected or next(
            (item for item in streams if item.get("type") == "128"),
            None,
        )
        audio_url = selected.get("stream") if selected else None
        title = f"{song.get('name', '')} - {song.get('artistName', '')}"
        return audio_url, title, song.get("image", ""), "NhacCuaTui"
    except (aiohttp.ClientError, Lib.asyncio.TimeoutError, ValueError, TypeError) as exc:
        Lib.show_log(f"DEV Custom Music error: {exc}", color=Lib.Color.RED)
        return None, None, None, None
