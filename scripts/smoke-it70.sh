#!/usr/bin/env bash
# Smoke test for Iteration 70 (Git publish admin API).
# Usage:
#   ./scripts/smoke-it70.sh
#   BASE_URL=http://192.168.10.26:8081 ADMIN_EMAIL=admin@paginium.local ADMIN_PASSWORD='…' ./scripts/smoke-it70.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BASE_URL="${BASE_URL:-http://localhost:8080}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@paginium.local}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-Admin123!ChangeMe}"
JAR="${SMOKE_COOKIE_JAR:-/tmp/pag-smoke-it70-cookies.txt}"

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

ok()   { echo -e "${GREEN}✓${NC} $1"; }
fail() { echo -e "${RED}✗${NC} $1" >&2; exit 1; }

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || fail "Missing command: $1"
}

need_cmd curl
need_cmd jq

echo "=== It.70 smoke test ==="
echo "BASE_URL=$BASE_URL"
echo "ADMIN_EMAIL=$ADMIN_EMAIL"
echo

rm -f "$JAR"

csrf="$(curl -sS -c "$JAR" "$BASE_URL/api/auth/csrf-token" | jq -r '.data.token // .token // empty')"
[[ -n "$csrf" ]] || fail "Could not fetch CSRF token"

login_code="$(curl -sS -o /tmp/pag-smoke-it70-login.json -w '%{http_code}' \
  -b "$JAR" -c "$JAR" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $csrf" \
  -X POST "$BASE_URL/api/auth/login" \
  -d "{\"email\":\"$ADMIN_EMAIL\",\"password\":\"$ADMIN_PASSWORD\"}")"
[[ "$login_code" == "200" ]] || fail "Login failed (HTTP $login_code): $(cat /tmp/pag-smoke-it70-login.json)"
ok "Login succeeded"

status_code="$(curl -sS -o /tmp/pag-smoke-it70-status.json -w '%{http_code}' \
  -b "$JAR" \
  -H "X-CSRF-TOKEN: $csrf" \
  "$BASE_URL/api/admin/git/status")"
[[ "$status_code" == "200" ]] || fail "Git status failed (HTTP $status_code): $(cat /tmp/pag-smoke-it70-status.json)"
ok "GET /api/admin/git/status"

strategy="$(jq -r '.data.strategy // .strategy // empty' /tmp/pag-smoke-it70-status.json)"
enabled="$(jq -r '.data.enabled // .enabled // empty' /tmp/pag-smoke-it70-status.json)"
echo "  strategy=$strategy enabled=$enabled"

preview_code="$(curl -sS -o /tmp/pag-smoke-it70-preview.json -w '%{http_code}' \
  -b "$JAR" \
  -H "X-CSRF-TOKEN: $csrf" \
  "$BASE_URL/api/admin/git/publish/preview")"
[[ "$preview_code" == "200" ]] || fail "Git preview failed (HTTP $preview_code): $(cat /tmp/pag-smoke-it70-preview.json)"
ok "GET /api/admin/git/publish/preview"

echo
echo "=== It.70 smoke test passed ==="
