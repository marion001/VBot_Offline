WIKIPEDIA_API_URL = "https://vi.wikipedia.org/w/api.php"


def _clean_text(value):
    return " ".join(str(value or "").split())


def handle(arguments, context):
    lib = context["Lib"]
    query = _clean_text(arguments.get("query"))
    if not query:
        raise ValueError("Thiếu từ khóa tìm kiếm")

    try:
        response = lib.requests.get(
            WIKIPEDIA_API_URL,
            params={
                "action": "query",
                "generator": "search",
                "gsrsearch": query,
                "gsrlimit": 2,
                "prop": "extracts|info",
                "exintro": 1,
                "explaintext": 1,
                "exsentences": 3,
                "inprop": "url",
                "redirects": 1,
                "format": "json",
                "formatversion": 2,
            },
            headers={
                "User-Agent": "VBot-Assistant-MCP/1.0",
                "Accept": "application/json",
            },
            timeout=(3, 6),
        )
        response.raise_for_status()
        payload = response.json()
    except Exception as error:
        message = f"Không thể kết nối Wikipedia để tìm kiếm: {error}"
        context["log"](
            f"[MCP Plugin self.vbot.wikipedia_search] {message}",
            color=lib.Color.RED,
        )
        return {
            "content": [{"type": "text", "text": message}],
            "structuredContent": {
                "query": query,
                "results": [],
                "error": str(error),
            },
            "isError": True,
        }
    pages = payload.get("query", {}).get("pages", [])

    results = []
    for page in pages:
        title = _clean_text(page.get("title"))
        summary = _clean_text(page.get("extract"))
        url = str(page.get("fullurl") or "").strip()
        if not title:
            continue
        results.append({
            "title": title,
            "summary": summary or "Không có phần tóm tắt",
            "url": url,
        })

    if not results:
        return {
            "content": [{
                "type": "text",
                "text": f"Không tìm thấy thông tin Wikipedia phù hợp với: {query}",
            }],
            "structuredContent": {
                "query": query,
                "results": [],
            },
            "isError": True,
        }

    text_parts = [f"Kết quả tìm kiếm Wikipedia cho {query}:"]
    for index, item in enumerate(results, 1):
        text_parts.append(
            f"{index}. {item['title']}: {item['summary']}"
        )

    context["log"](
        f"[MCP Plugin self.vbot.wikipedia_search] Tìm thấy {len(results)} kết quả cho: {query}",
        color=lib.Color.CYAN,
    )
    return {
        "content": [{
            "type": "text",
            "text": " ".join(text_parts),
        }],
        "structuredContent": {
            "query": query,
            "source": "Wikipedia tiếng Việt",
            "results": results,
        },
    }
