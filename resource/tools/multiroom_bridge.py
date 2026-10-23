#!/usr/bin/env python3
"""CLI entry point for source or compiled VBot Multiroom bridge modules."""
from pathlib import Path
import sys


def main():
    root = str(Path(__file__).resolve().parents[2])
    if root not in sys.path:
        sys.path.insert(0, root)
    try:
        from VBot_Multiroom_Bridge_Audio import main as bridge_main
    except ImportError as error:
        print(
            'Không nạp được module VBot_Multiroom_Bridge_Audio trong ' + root
            + '. Cần file .py hoặc .so tương thích Python/hệ điều hành của loa. Chi tiết: '
            + str(error), file=sys.stderr,
        )
        return 1
    # Keep argv unchanged: the module's own parser handles status/local/etc.
    return bridge_main()


if __name__ == '__main__':
    raise SystemExit(main())
