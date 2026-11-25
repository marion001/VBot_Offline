#!/bin/sh
set -eu

CONFIG_PATH="$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)/Config.json"
META=$(python3 - "$CONFIG_PATH" <<'PY'
import json
import socket
import sys
import uuid

uid = ""
for mid_path in ("/etc/machine-id", "/var/lib/dbus/machine-id"):
    try:
        with open(mid_path, encoding="utf-8") as fh:
            raw = fh.read().strip()
        if raw:
            uid = str(uuid.UUID(hex=raw))
            break
    except (OSError, ValueError):
        continue
if not uid:
    uid = str(uuid.uuid5(uuid.NAMESPACE_DNS, "vbot-dlna-" + socket.gethostname()))

name = ""
try:
    with open(sys.argv[1], encoding="utf-8-sig") as config_file:
        config = json.load(config_file)
    name = " ".join(str(config.get("contact_info", {}).get("full_name", "")).split())[:80]
except (OSError, ValueError, TypeError, AttributeError):
    name = ""

print(uid)
print(name)
PY
)
UUID=${META%%
*}
FULL_NAME=${META#*
}
FRIENDLY_NAME="VBot${FULL_NAME:+ $FULL_NAME}"
exec /usr/local/bin/gmediarender \
  --friendly-name="$FRIENDLY_NAME" \
  --uuid="$UUID" \
  --port=49494 \
  --mime-filter=audio \
  --gstout-audiosink=alsasink \
  --gstout-audiodevice=vbot_aec_media
