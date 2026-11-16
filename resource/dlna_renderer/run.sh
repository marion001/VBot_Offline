#!/bin/sh
set -eu

UUID=$(python3 -c 'import uuid; print(uuid.UUID(hex=open("/etc/machine-id").read().strip()))')
CONFIG_PATH="$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)/Config.json"
FULL_NAME=$(python3 - "$CONFIG_PATH" <<'PY'
import json
import sys

try:
    with open(sys.argv[1], encoding="utf-8") as config_file:
        config = json.load(config_file)
    name = " ".join(str(config.get("contact_info", {}).get("full_name", "")).split())
    print(name[:80])
except (OSError, ValueError, TypeError, AttributeError):
    print("")
PY
)
FRIENDLY_NAME="VBot${FULL_NAME:+ $FULL_NAME}"
exec /usr/local/bin/gmediarender \
  --friendly-name="$FRIENDLY_NAME" \
  --uuid="$UUID" \
  --port=49494 \
  --mime-filter=audio \
  --gstout-audiosink=alsasink \
  --gstout-audiodevice=vbot_aec_media
