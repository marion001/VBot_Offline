import json
import requests
from bs4 import BeautifulSoup
import sys

def fetch_vi_vn_voices_from_web(output_file="/home/pi/VBot_Offline/html/includes/other_data/list_voices_tts_gcloud.json"):
    try:
        resp = requests.get("https://docs.cloud.google.com/text-to-speech/docs/list-voices-and-types", timeout=10)
        resp.raise_for_status()
        soup = BeautifulSoup(resp.text, "html.parser")
        voice_names = set()
        for table in soup.find_all("table"):
            headers = [th.get_text(strip=True) for th in table.find_all("th")]
            if not headers:
                continue
            for row in table.find_all("tr")[1:]:
                cols = [td.get_text(strip=True) for td in row.find_all("td")]
                if len(cols) != len(headers):
                    continue
                item = dict(zip(headers, cols))
                if "vietnam" in item.get("Language", "").lower():
                    voice = item.get("Voice name")
                    if voice:
                        voice_names.add(voice)
        if not voice_names:
            return {
                "success": False,
                "message": "Không tìm thấy giọng nói tiếng Việt",
                "count": 0
            }
        data = {"voice_list_vi_vn": sorted(voice_names)}
        with open(output_file, "w", encoding="utf-8") as f:
            json.dump(data, f, ensure_ascii=False, indent=4)
        return {
            "success": True,
            "message": "Lấy danh sách giọng đọc Google thành công",
            "count": len(voice_names),
            #"output_file": output_file
        }
    except Exception as e:
        return {
            "success": False,
            "message": str(e),
            "count": 0
        }

if __name__ == "__main__":
    result = fetch_vi_vn_voices_from_web()
    print(json.dumps(result, ensure_ascii=False))
    sys.exit(0)
