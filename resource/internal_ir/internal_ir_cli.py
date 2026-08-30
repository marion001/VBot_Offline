#!/usr/bin/env python3
'''
Code By: Vũ Tuyển
GitHub VBot: https://github.com/marion001/VBot_Offline.git
Facebook Group: https://www.facebook.com/groups/1148385343358824
Facebook: https://www.facebook.com/TWFyaW9uMDAx
Mail: VBot.Assistant@gmail.com
'''

import sys
from pathlib import Path

VBOT_ROOT = Path(__file__).resolve().parents[2]
if str(VBOT_ROOT) not in sys.path:
    sys.path.insert(0, str(VBOT_ROOT))

import Internal_IR

if __name__ == "__main__":
    sys.exit(Internal_IR.main())
