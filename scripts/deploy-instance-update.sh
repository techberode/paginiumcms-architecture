#!/usr/bin/env bash
# PaginiumCMS — in-place instance update (production or demo).
#
# Run ON THE SERVER from a git checkout (or set APP_ROOT).
#
# Required env:
#   APP_ROOT          — e.g. /var/www/paginiumcms.com
#
# Optional env:
#   STACK_DIR         — e.g. /var/lib/docker/compose/paginiumcms (if set, restarts PHP)
#   BACKEND_PORT      — for health check (default 8089)
#   GIT_REF           — origin/main (default) or tag e.g. v2.1.0-beta.9
#   SKIP_COMPOSER=1   — skip composer install
#   SKIP_FRONTEND=1   — skip npm build (PHP-only hotfix)
#   SKIP_RESTART=1    — skip docker restart
#   SMOKE_DEMO_CORS=1   — POST login with Origin header (ISS-098); auto when DEMO_MODE=true
#
# Example — production, latest main:
#   APP_ROOT=/var/www/paginiumcms.com \
#   STACK_DIR=/var/lib/docker/compose/paginiumcms \
#   BACKEND_PORT=8089 \
#   ./scripts/deploy-instance-update.sh
#
# Example — release tag:
#   GIT_REF=v2.1.0-beta.9 APP_ROOT=… STACK_DIR=… ./scripts/deploy-instance-update.sh
set -euo pipefail

APP_ROOT="${APP_ROOT:?Set APP_ROOT to the git checkout root}"
STACK_DIR="${STACK_DIR:-}"
BACKEND_PORT="${BACKEND_PORT:-8089}"
GIT_REF="${GIT_REF:-origin/main}"
HEALTH_WAIT_SEC="${HEALTH_WAIT_SEC:-8}"

cd "$APP_ROOT"

echo "→ PaginiumCMS deploy update"
echo "   APP_ROOT=$APP_ROOT"
echo "   GIT_REF=$GIT_REF"

git fetch origin --tags

if [[ "$GIT_REF" == origin/* ]]; then
  BRANCH="${GIT_REF#origin/}"
  git checkout "$BRANCH"
  git pull origin "$BRANCH"
elif git rev-parse "$GIT_REF" >/dev/null 2>&1; then
  git checkout "$GIT_REF"
else
  echo "ERROR: unknown GIT_REF=$GIT_REF (fetch tags or use origin/main)" >&2
  exit 1
fi

echo "→ At commit: $(git log -1 --oneline)"

if [[ "${SKIP_COMPOSER:-0}" != "1" ]]; then
  echo "→ composer install --no-dev"
  composer install --no-dev --optimize-autoloader
fi

if [[ "${SKIP_FRONTEND:-0}" != "1" ]]; then
  echo "→ frontend build:prod"
  cd frontend
  npm ci
  npm run build:prod
  cd ..
fi

if [[ "${SKIP_RESTART:-0}" != "1" && -n "$STACK_DIR" && -x "$STACK_DIR/stack.sh" ]]; then
  echo "→ restart PHP via $STACK_DIR/stack.sh"
  "$STACK_DIR/stack.sh" restart php
  echo "→ waiting ${HEALTH_WAIT_SEC}s (502 right after restart is normal — ISS-096)"
  sleep "$HEALTH_WAIT_SEC"
fi

HEALTH_URL="http://127.0.0.1:${BACKEND_PORT}/api/health"
echo "→ health: $HEALTH_URL"
curl -sf "$HEALTH_URL" | head -c 400
echo ""

# Optional CORS smoke for demo (ISS-098) — set SMOKE_DEMO_CORS=1 or DEMO_MODE=true in .env
if [[ "${SMOKE_DEMO_CORS:-0}" == "1" ]] || grep -q '^DEMO_MODE=true' "$APP_ROOT/.env" 2>/dev/null; then
  DEMO_ORIGIN="$(grep -E '^APP_URL=' "$APP_ROOT/.env" 2>/dev/null | cut -d= -f2- | tr -d '"')"
  if [[ -n "$DEMO_ORIGIN" ]]; then
    echo "→ CORS smoke (Origin: $DEMO_ORIGIN)"
    HTTP_CODE="$(curl -sS -o /dev/null -w '%{http_code}' \
      -X POST "${DEMO_ORIGIN%/}/api/auth/login" \
      -H 'Content-Type: application/json' \
      -H "Origin: ${DEMO_ORIGIN%/}" \
      -d '{"email":"demo@paginiumcms.com","password":"Demo123!"}' || echo '000')"
    echo "   login+Origin → HTTP $HTTP_CODE (expect 200; 401 empty = fix APP_URL or pull SameOriginCors)"
  fi
fi

echo "→ content:diagnose (summary)"
php backend/bin/console content:diagnose --json 2>/dev/null | head -c 600 || true
echo ""
echo "Done."
