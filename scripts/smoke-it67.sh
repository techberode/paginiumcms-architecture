#!/usr/bin/env bash
# Smoke test for Iteration 67 (shortcodes + theme import API).
# Usage:
#   ./scripts/smoke-it67.sh
#   BASE_URL=http://192.168.10.26:8081 ADMIN_EMAIL=admin@paginium.local ADMIN_PASSWORD='…' ./scripts/smoke-it67.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BASE_URL="${BASE_URL:-http://localhost:8080}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@paginium.local}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-Admin123!ChangeMe}"
JAR="${SMOKE_COOKIE_JAR:-/tmp/pag-smoke-it67-cookies.txt}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ok()   { echo -e "${GREEN}✓${NC} $1"; }
warn() { echo -e "${YELLOW}!${NC} $1"; }
fail() { echo -e "${RED}✗${NC} $1" >&2; exit 1; }

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || fail "Missing command: $1"
}

need_cmd curl
need_cmd php
need_cmd jq

echo "=== It.67 smoke test ==="
echo "BASE_URL=$BASE_URL"
echo "ADMIN_EMAIL=$ADMIN_EMAIL"
echo

rm -f "$JAR"

# --- health
HTTP_CODE=$(curl -s -o /dev/null -w '%{http_code}' "$BASE_URL/api/health" || true)
if [[ "$HTTP_CODE" != "200" ]]; then
  fail "Backend not reachable at $BASE_URL/api/health (HTTP $HTTP_CODE)"
fi
ok "Backend health OK"

# --- login
LOGIN_JSON=$(curl -s -c "$JAR" -b "$JAR" -X POST "$BASE_URL/api/auth/login" \
  -H 'Content-Type: application/json' \
  -d "$(jq -nc --arg e "$ADMIN_EMAIL" --arg p "$ADMIN_PASSWORD" '{email:$e,password:$p}')")

LOGIN_OK=$(echo "$LOGIN_JSON" | jq -r '.success // false')
if [[ "$LOGIN_OK" != "true" ]]; then
  echo "$LOGIN_JSON" | jq . 2>/dev/null || echo "$LOGIN_JSON"
  fail "Login failed — set ADMIN_EMAIL / ADMIN_PASSWORD (PHP 8.5 rejects admin@localhost; use admin@paginium.local or your real account email)"
fi

if [[ "$(echo "$LOGIN_JSON" | jq -r '.requires_two_factor // .data.requires_two_factor // false')" == "true" ]]; then
  fail "Account requires 2FA — complete TOTP login in browser first, or set TWO_FACTOR_REQUIRED=false in .env for local dev"
fi
ok "Logged in as $ADMIN_EMAIL"

# --- CSRF (GET — not POST)
CSRF_JSON=$(curl -s -c "$JAR" -b "$JAR" "$BASE_URL/api/auth/csrf-token")
CSRF=$(echo "$CSRF_JSON" | jq -r '.token // .data.token // empty')
if [[ -z "$CSRF" ]]; then
  echo "$CSRF_JSON" | jq . 2>/dev/null || echo "$CSRF_JSON"
  fail "Could not read CSRF token from GET /api/auth/csrf-token"
fi
ok "CSRF token acquired"

auth_headers=(-b "$JAR" -H "X-CSRF-TOKEN: $CSRF")

# --- shortcode preview safe
SAFE_FILE="$ROOT/backend/tests/Fixtures/hostile/shortcodes/safe-alert.json"
PREVIEW_OK=$(curl -s "${auth_headers[@]}" -X POST "$BASE_URL/api/admin/shortcodes/preview" \
  -H 'Content-Type: application/json' \
  -d @"$SAFE_FILE")
if [[ "$(echo "$PREVIEW_OK" | jq -r '.success // false')" != "true" ]]; then
  echo "$PREVIEW_OK" | jq .
  fail "Safe shortcode preview should succeed"
fi
ok "Shortcode preview accepted safe fixture"

# --- shortcode preview hostile
HOSTILE_FILE="$ROOT/backend/tests/Fixtures/hostile/shortcodes/script-tag.json"
PREVIEW_BAD_CODE=$(curl -s -o /tmp/pag-preview-bad.json -w '%{http_code}' "${auth_headers[@]}" \
  -X POST "$BASE_URL/api/admin/shortcodes/preview" \
  -H 'Content-Type: application/json' \
  -d @"$HOSTILE_FILE")
if [[ "$PREVIEW_BAD_CODE" != "422" ]]; then
  cat /tmp/pag-preview-bad.json
  fail "Hostile shortcode preview should return HTTP 422 (got $PREVIEW_BAD_CODE)"
fi
ok "Shortcode preview rejected hostile fixture (422)"

# --- shortcode save
SAVE_JSON=$(curl -s "${auth_headers[@]}" -X PUT "$BASE_URL/api/admin/shortcodes/alert-box" \
  -H 'Content-Type: application/json' \
  -d @"$SAFE_FILE")
if [[ "$(echo "$SAVE_JSON" | jq -r '.success // false')" != "true" ]]; then
  echo "$SAVE_JSON" | jq .
  fail "Shortcode save failed"
fi
ok "Shortcode saved (alert-box)"

# --- theme zip via PHP (no zip CLI required)
THEME_ZIP="/tmp/pag-smoke-test-theme.zip"
php <<'PHP' "$THEME_ZIP"
<?php
declare(strict_types=1);
$zipPath = $argv[1] ?? '';
if ($zipPath === '') {
    fwrite(STDERR, "Missing zip path\n");
    exit(1);
}
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Cannot create zip\n");
    exit(1);
}
$manifest = json_encode([
    'id' => 'smoke-test-theme',
    'name' => 'Smoke Test Theme',
    'version' => '1.0.0',
    'slots' => ['header', 'main', 'footer'],
    'supports' => ['appearance-tokens'],
], JSON_UNESCAPED_UNICODE);
$zip->addFromString('smoke-test-theme/theme.json', $manifest);
$zip->addFromString('smoke-test-theme/templates/default.html', '<main class="pg-main">{{content}}</main>');
$zip->close();
echo "created:$zipPath\n";
PHP

IMPORT_JSON=$(curl -s "${auth_headers[@]}" -X POST "$BASE_URL/api/admin/themes/import" \
  -F "file=@$THEME_ZIP")
if [[ "$(echo "$IMPORT_JSON" | jq -r '.success // false')" != "true" ]]; then
  echo "$IMPORT_JSON" | jq .
  fail "Theme import failed (maybe already installed — delete smoke-test-theme first)"
fi
ok "Theme ZIP imported (smoke-test-theme)"

echo
echo -e "${GREEN}=== It.67 smoke test passed ===${NC}"
echo "Check flat files:"
echo "  data/shortcodes/definitions/alert-box.json"
echo "  data/shortcodes/registry.json"
echo "  backend/resources/views/themes/smoke-test-theme/"
