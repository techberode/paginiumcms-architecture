#!/usr/bin/env bash
# Smoke test for Iteration 25 (setup wizard API + install state).
# Usage:
#   ./scripts/smoke-it25.sh
#   BASE_URL=http://127.0.0.1:8080 ./scripts/smoke-it25.sh
# Fresh-install complete flow (empty users + installed=false):
#   FRESH_INSTALL=1 SETUP_EMAIL=admin@example.test SETUP_PASSWORD='StrongP@ssw0rd123!' ./scripts/smoke-it25.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BASE_URL="${BASE_URL:-http://localhost:8080}"
SETUP_EMAIL="${SETUP_EMAIL:-setup_smoke_$(date +%s)@example.com}"
SETUP_PASSWORD="${SETUP_PASSWORD:-StrongP@ssw0rd123!}"
SETUP_NAME="${SETUP_NAME:-Smoke Setup Admin}"
SITE_NAME="${SITE_NAME:-Smoke Test Site}"
LANGUAGE="${LANGUAGE:-en}"

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

echo "=== It.25 smoke test ==="
echo "BASE_URL=$BASE_URL"
echo

status_code="$(curl -sS -o /tmp/pag-smoke-it25-status.json -w '%{http_code}' \
  "$BASE_URL/api/setup/status")"
[[ "$status_code" == "200" ]] || fail "Setup status failed (HTTP $status_code): $(cat /tmp/pag-smoke-it25-status.json)"
ok "GET /api/setup/status"

preflight_code="$(curl -sS -o /tmp/pag-smoke-it25-preflight.json -w '%{http_code}' \
  "$BASE_URL/api/setup/preflight")"
[[ "$preflight_code" == "200" ]] || fail "Setup preflight failed (HTTP $preflight_code): $(cat /tmp/pag-smoke-it25-preflight.json)"
ok "GET /api/setup/preflight"

preflight_ready="$(jq -r '.data.ready // .ready // empty' /tmp/pag-smoke-it25-preflight.json)"
preflight_checks="$(jq -r '.data.checks | length // 0' /tmp/pag-smoke-it25-preflight.json)"
echo "  preflight ready=$preflight_ready checks=$preflight_checks"
[[ "$preflight_checks" =~ ^[0-9]+$ ]] && (( preflight_checks > 0 )) || fail "Setup preflight returned no checks"

needs_setup="$(jq -r '.data.needsSetup // .needsSetup // empty' /tmp/pag-smoke-it25-status.json)"
installed="$(jq -r '.data.installed // .installed // empty' /tmp/pag-smoke-it25-status.json)"
echo "  needsSetup=$needs_setup installed=$installed"

if [[ "${FRESH_INSTALL:-0}" == "1" ]]; then
  if [[ "$needs_setup" != "true" ]]; then
    fail "FRESH_INSTALL=1 but instance reports needsSetup=false (purge users and set general.installed=false first)"
  fi

  complete_code="$(curl -sS -o /tmp/pag-smoke-it25-complete.json -w '%{http_code}' \
    -H "Content-Type: application/json" \
    -X POST "$BASE_URL/api/setup/complete" \
    -d "{\"email\":\"$SETUP_EMAIL\",\"password\":\"$SETUP_PASSWORD\",\"passwordConfirm\":\"$SETUP_PASSWORD\",\"name\":\"$SETUP_NAME\",\"siteName\":\"$SITE_NAME\",\"language\":\"$LANGUAGE\"}")"
  [[ "$complete_code" == "200" ]] || fail "Setup complete failed (HTTP $complete_code): $(cat /tmp/pag-smoke-it25-complete.json)"
  ok "POST /api/setup/complete (fresh install)"

  complete_installed="$(jq -r '.installed // .data.installed // empty' /tmp/pag-smoke-it25-complete.json)"
  [[ "$complete_installed" == "true" ]] || fail "Setup complete did not mark installed=true"
  ok "Setup marked installed=true"
else
  if [[ "$needs_setup" == "true" ]]; then
    echo "  hint: open $BASE_URL/setup in the browser to finish onboarding"
  else
    ok "Instance already installed (expected on existing deployments)"
  fi
fi

echo
echo "=== It.25 smoke test passed ==="
