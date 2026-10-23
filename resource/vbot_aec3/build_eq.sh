#!/bin/sh
set -eu
eq_adapter_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
exec sh "$eq_adapter_dir/../equalizer/build_eq.sh" "$@"
