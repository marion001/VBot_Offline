def handle(arguments, context):
    text = str(arguments.get("text", "")).strip()
    if not text:
        raise ValueError("Thiếu nội dung text")

    context["log"](
        f"[MCP Plugin self.vbot.echo] Nội dung: {text}",
        color=context["Lib"].Color.CYAN,
    )
    return {
        "content": [
            {
                "type": "text",
                "text": f"Bạn vừa nói: {text}",
            }
        ],
        "structuredContent": {
            "text": text,
        },
    }
