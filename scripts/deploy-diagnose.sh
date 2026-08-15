#!/usr/bin/env bash
# PaginiumCMS — deploy troubleshooting (run ON THE SERVER).
#
# Example:
#   APP_ROOT=/var/www/paginiumcms.com \
#   STACK_DIR=/var/lib/docker/compose/paginiumcms \
#   BACKEND_PORT=8089 \
#   ./scripts/deploy-diagnose.sh
set -euo pipefail

APP_ROOT="${APP_ROOT:?Set APP_ROOT}"
STACK_DIR="${STACK_DIR:-}"
BACKEND_PORT="${BACKEND_PORT:-8089}"

cd "$APP_ROOT"

echo "=== PaginiumCMS deploy diagnose ==="
echo "user=$(whoami) uid=$(id -u) groups=$(id -Gn)"
echo "APP_ROOT=$APP_ROOT"
echo "STACK_DIR=${STACK_DIR:-<unset>}"
echo ""

echo "→ git"
git -c safe.directory="$APP_ROOT" log -1 --oneline 2>/dev/null || echo "  git log failed"
git -c safe.directory="$APP_ROOT" describe --tags --always 2>/dev/null || echo "  git describe failed"
git -c safe.directory="$APP_ROOT" status --short 2>/dev/null | head -20 || true
echo ""

echo "→ beta.51 markers on disk"
for marker in applyBulkStatus removeByPath; do
  if grep -rq "$marker" backend/app/Core 2>/dev/null; then
    echo "  OK: $marker found"
  else
    echo "  MISSING: $marker (checkout not at beta.51+)"
  fi
done
echo ""

echo "→ writable check (sample paths)"
for path in backend/app backend/vendor frontend/dist .git/index; do
  if [[ -e "$path" ]]; then
    if [[ -w "$path" ]]; then
      echo "  writable: $path"
    else
      echo "  NOT writable: $path  ($(stat -c '%U:%G %a' "$path" 2>/dev/null || echo '?'))"
    fi
  fi
done
echo ""

echo "→ health http://127.0.0.1:${BACKEND_PORT}/api/health"
curl -sS "http://127.0.0.1:${BACKEND_PORT}/api/health" 2>/dev/null | head -c 500 || echo "  health unreachable"
echo ""
echo ""

if [[ -n "$STACK_DIR" && -x "$STACK_DIR/stack.sh" ]]; then
  echo "→ docker stack ($STACK_DIR)"
  (cd "$STACK_DIR" && ./stack.sh ps) 2>/dev/null || echo "  stack.sh ps failed"
else
  echo "→ STACK_DIR/stack.sh not available — PHP restart must be done on host"
fi

echo ""
echo "=== Recommended prod deploy (SSH on host) ==="
echo "DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.52 APP_ROOT=$APP_ROOT \\"
echo "STACK_DIR=${STACK_DIR:-/var/lib/docker/compose/paginiumcms} BACKEND_PORT=$BACKEND_PORT \\"
echo "./scripts/deploy-instance-update.sh"
