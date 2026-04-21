#!/bin/bash
set -euo pipefail

# Backward-compatible wrapper: keep old file name, delegate to the unified startup flow.
exec /entrypoint.sh "$@"
