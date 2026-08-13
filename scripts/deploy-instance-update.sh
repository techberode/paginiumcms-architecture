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
#   DEPLOY_FORCE=1    — for tag/commit checkout: discard tracked local diffs and
#                       move untracked files that would block checkout to DEPLOY_CACHE_ROOT/backups/
#   SKIP_COMPOSER=1   — skip composer install
#   SKIP_FRONTEND=1   — skip npm build (PHP-only hotfix)
#   SKIP_RESTART=1    — skip docker restart
#   DEPLOY_CACHE_ROOT — composer/npm caches (default: $APP_ROOT/backend/storage/app/deploy-cache)
#   SMOKE_DEMO_CORS=1   — POST login with Origin header (ISS-098); auto when DEMO_MODE=true
#
# Docker admin UI deploy (www-data): run once on host:
#   APP_ROOT=/var/www/paginiumcms.com ./scripts/bootstrap-deploy-permissions.sh
# App .env inside container: APP_ROOT=/var/www/html
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

# Docker admin UI: www-data + bind-mount — git safe.directory, writable composer/npm caches.
DEPLOY_CACHE_ROOT="${DEPLOY_CACHE_ROOT:-$APP_ROOT/backend/storage/app/deploy-cache}"
mkdir -p "$DEPLOY_CACHE_ROOT/composer" "$DEPLOY_CACHE_ROOT/npm" 2>/dev/null || true
export COMPOSER_HOME="${COMPOSER_HOME:-$DEPLOY_CACHE_ROOT/composer}"
export COMPOSER_ALLOW_SUPERUSER=1
export npm_config_cache="${npm_config_cache:-$DEPLOY_CACHE_ROOT/npm}"
export HOME="${HOME:-$DEPLOY_CACHE_ROOT}"
export GIT_CONFIG_COUNT=1
export GIT_CONFIG_KEY_0=safe.directory
export GIT_CONFIG_VALUE_0="$APP_ROOT"

git() {
  command git -c safe.directory="$APP_ROOT" "$@"
}

assert_checkout_writable() {
  local path blocked=()
  while IFS= read -r path; do
    [[ -z "$path" ]] && continue
    if [[ -e "$path" && ! -w "$path" ]]; then
      blocked+=("$path")
    fi
  done < <(
    {
      git diff --name-only 2>/dev/null || true
      git diff --cached --name-only 2>/dev/null || true
      git ls-files --others --exclude-standard 2>/dev/null || true
    } | sort -u
  )

  if [[ ${#blocked[@]} -eq 0 ]]; then
    return 0
  fi

  echo "ERROR: permission denied — paths not writable by $(whoami) (uid=$(id -u)):" >&2
  printf '  %s\n' "${blocked[@]}" >&2
  ls -la "${blocked[0]}" 2>/dev/null | sed 's/^/  /' >&2 || true
  echo "" >&2
  echo "Cause: admin UI deploy (www-data in Docker) or PHP touched files owned by another user." >&2
  echo "Fix once on host (requires sudo), then rerun deploy:" >&2
  echo "  APP_ROOT=$APP_ROOT ./scripts/bootstrap-deploy-permissions.sh" >&2
  echo "  sudo usermod -aG www-data \$(whoami)   # new login if group was added" >&2
  exit 1
}

prepare_checkout_for_ref() {
  local target_ref="$1"
  local backup_dir="$DEPLOY_CACHE_ROOT/pre-checkout-backup/$(date +%Y%m%d-%H%M%S)-${target_ref//\//_}"

  if [[ "${DEPLOY_FORCE:-0}" != "1" ]]; then
    if ! git diff --quiet || ! git diff --cached --quiet; then
      echo "ERROR: local tracked changes block checkout. Stash/commit or rerun with DEPLOY_FORCE=1." >&2
      git status --short >&2 || true
      exit 1
    fi
  fi

  mkdir -p "$backup_dir"

  while IFS= read -r -d '' path; do
    if git cat-file -e "${target_ref}:${path}" 2>/dev/null; then
      echo "→ backup untracked blocker: $path"
      mkdir -p "$backup_dir/$(dirname "$path")"
      mv "$path" "$backup_dir/$path"
    fi
  done < <(git ls-files --others --exclude-standard -z)

  if [[ "${DEPLOY_FORCE:-0}" == "1" ]]; then
    assert_checkout_writable
    git reset --hard HEAD
  fi
}

echo "→ PaginiumCMS deploy update"
echo "   APP_ROOT=$APP_ROOT"
echo "   GIT_REF=$GIT_REF"

git fetch origin --tags

if [[ "$GIT_REF" == origin/* ]]; then
  BRANCH="${GIT_REF#origin/}"
  git checkout "$BRANCH"
  git pull origin "$BRANCH"
elif git rev-parse "$GIT_REF" >/dev/null 2>&1; then
  prepare_checkout_for_ref "$GIT_REF"
  git checkout -f "$GIT_REF"
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
  if ! command -v npm >/dev/null 2>&1; then
    echo "ERROR: npm not found — rebuild PHP image (Node in docker/php/Dockerfile) or run with SKIP_FRONTEND=1 and build on host:" >&2
    echo "  cd \$APP_ROOT/frontend && npm ci && npm run build:prod" >&2
    exit 127
  fi
  echo "→ frontend build:prod"
  cd frontend
  npm ci
  npm run build:prod
  cd ..
fi

if [[ "${SKIP_RESTART:-0}" != "1" && -n "$STACK_DIR" && -x "$STACK_DIR/stack.sh" ]]; then
  echo "→ recreate PHP via $STACK_DIR/stack.sh (reload code + opcache)"
  "$STACK_DIR/stack.sh" up -d --force-recreate php
  echo "→ waiting ${HEALTH_WAIT_SEC}s (502 right after restart is normal — ISS-096)"
  sleep "$HEALTH_WAIT_SEC"
elif [[ "${SKIP_RESTART:-0}" != "1" && -n "$STACK_DIR" ]]; then
  echo "→ SKIP_RESTART: stack.sh not reachable from this environment (restart PHP on host)"
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
