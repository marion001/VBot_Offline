"""Cầu nối CLI cho Calendar Events WebUI.

File này được giữ ở dạng Python để PHP có thể gọi module Calendar_Events dù
phần lõi được phân phối dưới dạng Calendar_Events.py hoặc extension .so.
"""

import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[3]
root_text = str(ROOT)
if root_text not in sys.path:
    sys.path.insert(0, root_text)

from Calendar_Events import main


if __name__ == "__main__":
    raise SystemExit(main())
