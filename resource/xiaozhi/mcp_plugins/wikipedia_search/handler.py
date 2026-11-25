WIKIPEDIA_API_URL = "https://vi.wikipedia.org/w/api.php"
_WIKI_HTTP_SESSION = None


def _clean_text(value):
    return " ".join(str(value or "").split())


def handle(arguments, context):
    global _WIKI_HTTP_SESSION
    lib = context["Lib"]
    query = _clean_text(arguments.get("query"))
    if not query:
        raise ValueError("Thiếu từ khóa tìm kiếm")

    try:
        req_mod = lib.requests
        req_kwargs = dict(
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
        # Tái sử dụng kết nối TLS Keep-Alive nếu lib.requests hỗ trợ Session thật
        if _WIKI_HTTP_SESSION is None and hasattr(req_mod, "Session") and callable(getattr(req_mod, "Session", None)):
            try:
                _WIKI_HTTP_SESSION = req_mod.Session()
            except Exception:
                _WIKI_HTTP_SESSION = None
        getter = _WIKI_HTTP_SESSION.get if (_WIKI_HTTP_SESSION is not None and getattr(req_mod, "__name__", "") == "requests") else req_mod.get
        response = getter(WIKIPEDIA_API_URL, **req_kwargs)
        try:
            response.raise_for_status()
            payload = response.json()
        finally:
            close_fn = getattr(response, "close", None)
            if callable(close_fn):
                close_fn()
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
    query_obj = payload.get("query") if isinstance(payload, dict) else None
    raw_pages = query_obj.get("pages") if isinstance(query_obj, dict) else []
    if isinstance(raw_pages, dict):
        pages = list(raw_pages.values())
    elif isinstance(raw_pages, list):
        pages = raw_pages
    else:
        pages = []

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
