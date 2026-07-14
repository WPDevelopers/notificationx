#!/usr/bin/env bash
# PostToolUse hook — lint any PHP file Claude just edited with `php -l`.
# On a syntax error, exit 2 so the error is fed back to Claude to fix.
set -euo pipefail

input="$(cat)"
file="$(printf '%s' "$input" | jq -r '.tool_input.file_path // empty')"

[ -z "$file" ] && exit 0
case "$file" in
  *.php) ;;
  *) exit 0 ;;
esac
[ -f "$file" ] || exit 0

if ! out="$(php -l "$file" 2>&1)"; then
  echo "PHP syntax error introduced in $file:" >&2
  echo "$out" >&2
  exit 2
fi
exit 0
