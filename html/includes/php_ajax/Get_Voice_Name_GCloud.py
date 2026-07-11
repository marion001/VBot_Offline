'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

import json
import requests
from bs4 import BeautifulSoup
import sys

def detect_voice_type(voice_name):
    lower = voice_name.lower()
    if "chirp3-hd" in lower:
        return "Chirp3-HD"
    elif "chirp-hd" in lower:
        return "Chirp-HD"
    elif "journey" in lower:
        return "Journey"
    elif "studio" in lower:
        return "Studio"
    elif "neural2" in lower:
        return "Neural2"
    elif "wavenet" in lower:
        return "WaveNet"
    elif "standard" in lower:
        return "Standard"
    elif "news" in lower:
        return "News"
    elif "polyglot" in lower:
        return "Polyglot"
    return "Unknown"

def fetch_gcloud_voices_from_web(output_file="/home/pi/VBot_Offline/html/includes/other_data/list_voices_tts_gcloud.json"):
    try:
        resp = requests.get("https://docs.cloud.google.com/text-to-speech/docs/list-voices-and-types", timeout=20, headers={"User-Agent": "Mozilla/5.0"})
        resp.raise_for_status()
        soup = BeautifulSoup(resp.text, "html.parser")
        voices = []
        unique = set()
        for table in soup.find_all("table"):
            headers = [th.get_text(" ", strip=True) for th in table.find_all("th")]
            if not headers:
                continue
            for row in table.find_all("tr")[1:]:
                cols = [td.get_text(" ", strip=True) for td in row.find_all("td")]
                if len(cols) != len(headers):
                    continue
                item = dict(zip(headers, cols))
                language = item.get("Language", "")
                language_code = item.get("Language code", "")
                voice_name = item.get("Voice name", "")
                gender = item.get("SSML Gender", "")
                if not language_code or not voice_name:
                    continue
                key = (language_code, voice_name)
                if key in unique:
                    continue
                unique.add(key)
                voices.append({
                    "language": language,
                    "language_code": language_code,
                    "name": voice_name,
                    "type": detect_voice_type(voice_name),
                    "gender": gender
                })

        voices.sort(key=lambda x: (x["language_code"], x["type"], x["name"]))
        if not voices:
            return {"success": False, "message": "Không tìm thấy dữ liệu giọng đọc.", "count": 0}

        with open(output_file, "w", encoding="utf-8") as f:
            json.dump(voices, f, ensure_ascii=False, indent=4)

        return {"success": True, "message": "Đã cập nhật danh sách giọng đọc Google Cloud.", "count": len(voices)}

    except Exception as e:
        return {"success": False, "message": str(e), "count": 0}

if __name__ == "__main__":
    result = fetch_gcloud_voices_from_web()
    print(json.dumps(result, ensure_ascii=False))
    sys.exit(0)