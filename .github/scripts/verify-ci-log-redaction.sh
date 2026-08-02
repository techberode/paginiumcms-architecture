#!/usr/bin/env bash
# Fail-closed: sanitized CI log must not contain recoverable secret patterns.
set -euo pipefail

LOG_FILE="${1:?Usage: verify-ci-log-redaction.sh <safe-log-path>}"

if [[ ! -f "$LOG_FILE" ]]; then
  echo "CI log redaction verification: missing log file: $LOG_FILE" >&2
  exit 1
fi

fail() {
  echo "CI log redaction verification FAILED: $1" >&2
  exit 1
}

grep -Eiq 'otpauth://' "$LOG_FILE" && fail 'otpauth URI present'
grep -Eiq 'TOTP kód:[[:space:]]*[0-9]{4,8}' "$LOG_FILE" && fail 'TOTP code present'
grep -Eiq 'data:image/[^;]+;base64,[A-Za-z0-9+/=]{80,}' "$LOG_FILE" && fail 'QR base64 present'
grep -Eiq 'Authorization:[[:space:]]*(Bearer|Basic)[[:space:]]+[A-Za-z0-9._-]{8,}' "$LOG_FILE" && fail 'Authorization credential present'

if grep -Eio 'secret=[^[:space:]]+' "$LOG_FILE" | grep -Ev '^secret=\[REDACTED\]$' | grep -q .; then
  fail 'plaintext secret= value present'
fi

if grep -Eo '"secret"[[:space:]]*:[[:space:]]*"[^"]+"' "$LOG_FILE" | grep -Ev '\[REDACTED\]' | grep -q .; then
  fail 'JSON secret field not redacted'
fi

if grep -Eo '"qr_code"[[:space:]]*:[[:space:]]*"[^"]+"' "$LOG_FILE" | grep -Ev '\[REDACTED\]' | grep -q .; then
  fail 'JSON qr_code field not redacted'
fi

if grep -Eo '"provisioning_uri"[[:space:]]*:[[:space:]]*"[^"]+"' "$LOG_FILE" | grep -Ev '\[REDACTED\]' | grep -q .; then
  fail 'JSON provisioning_uri field not redacted'
fi

echo "CI log redaction verification: OK"
