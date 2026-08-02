#!/usr/bin/env python3
"""
Sanitize CI test log output before publishing to GitHub Actions console or artifacts.
Reads stdin or a file path argument; writes sanitized text to stdout.
"""
from __future__ import annotations

import json
import re
import sys
from typing import Any

SENSITIVE_KEYS = frozenset(
    {
        "password",
        "password_confirmation",
        "passwordhash",
        "secret",
        "two_factor_secret",
        "twofactorsecret",
        "qr_code",
        "qrcode",
        "provisioning_uri",
        "provisioninguri",
        "otp",
        "totp",
        "token",
        "access_token",
        "accesstoken",
        "refresh_token",
        "refreshtoken",
        "reset_token",
        "resettoken",
        "authorization",
        "cookie",
        "session",
        "api_key",
        "apikey",
    }
)

PLACEHOLDER = "[REDACTED]"

OTPAUTH_RE = re.compile(r"otpauth://[^\s\"']+", re.IGNORECASE)
BEARER_RE = re.compile(
    r"(Authorization:\s*(?:Bearer|Basic)\s+)(\S+)", re.IGNORECASE
)
JSON_STRING_KEY_RE = re.compile(
    r'"(secret|qr_code|provisioning_uri|two_factor_secret|password|token|access_token|refresh_token|api_key)"\s*:\s*"([^"\\]|\\.)*"',
    re.IGNORECASE,
)
PLAIN_SECRET_RE = re.compile(
    r"(secret=|TOTP kód:\s*|OTP kód:\s*|totp kód:\s*)(\S+)",
    re.IGNORECASE,
)
DATA_PREFIX_BASE64_RE = re.compile(
    r"data:image/[a-z+]+;base64,[A-Za-z0-9+/=]{80,}",
    re.IGNORECASE,
)
INLINE_DATA_JSON_RE = re.compile(
    r'(,\s*data=)(\\?\{.*)$',
    re.IGNORECASE,
)


def _normalize_key(key: str) -> str:
    return re.sub(r"[^a-z0-9]", "", key.lower())


def redact_structure(value: Any) -> Any:
    if isinstance(value, dict):
        out: dict[str, Any] = {}
        for key, item in value.items():
            if _normalize_key(str(key)) in SENSITIVE_KEYS:
                out[key] = PLACEHOLDER
            else:
                out[key] = redact_structure(item)
        return out
    if isinstance(value, list):
        return [redact_structure(item) for item in value]
    return value


def try_redact_json_blob(text: str) -> str | None:
    start = text.find("{")
    if start == -1:
        return None
    candidate = text[start:]
    try:
        parsed = json.loads(candidate)
    except json.JSONDecodeError:
        return None
    redacted = redact_structure(parsed)
    prefix = text[:start]
    return prefix + json.dumps(redacted, ensure_ascii=False)


def sanitize_line(line: str) -> str:
    original = line.rstrip("\n")
    line = original

    redacted_json = try_redact_json_blob(line)
    if redacted_json is not None:
        line = redacted_json

    line = OTPAUTH_RE.sub(PLACEHOLDER, line)
    line = JSON_STRING_KEY_RE.sub(
        lambda m: f'"{m.group(1)}": "{PLACEHOLDER}"', line
    )
    line = BEARER_RE.sub(rf"\1{PLACEHOLDER}", line)
    line = PLAIN_SECRET_RE.sub(rf"\1{PLACEHOLDER}", line)
    line = DATA_PREFIX_BASE64_RE.sub("data:image/*;base64,[REDACTED]", line)

    if ', data=' in line.lower() and '"secret"' in line.lower():
        line = INLINE_DATA_JSON_RE.sub(rf"\1{PLACEHOLDER}", line)

    return line + "\n"


def sanitize_text(text: str) -> str:
    return "".join(sanitize_line(part) for part in text.splitlines(keepends=True))


def main() -> int:
    if len(sys.argv) > 1:
        with open(sys.argv[1], encoding="utf-8", errors="replace") as handle:
            raw = handle.read()
    else:
        raw = sys.stdin.read()

    sys.stdout.write(sanitize_text(raw))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
