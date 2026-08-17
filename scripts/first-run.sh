#!/usr/bin/env bash
# scripts/first-run.sh — prvé spustenie PaginiumCMS (RECOMMENDATIONS Fáza 2)
#
# Pripraví .env, storage adresáre, composer vendor, prvého admina a content diagnose.
#
# Použitie:
#   ./scripts/first-run.sh
#   INSTALL_FRONTEND=1 ./scripts/first-run.sh
#   docker compose run --rm php bash scripts/first-run.sh

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ok()   { echo -e "${GREEN}✓${NC} $1"; }
warn() { echo -e "${YELLOW}!${NC} $1"; }
fail() { echo -e "${RED}✗${NC} $1" >&2; exit 1; }

echo "=== PaginiumCMS first-run ==="
echo "Root: $ROOT"
echo

# ------------------------------------------------------------------ PHP version
PHP_BIN="${PHP_BIN:-php}"
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  fail "PHP not found ($PHP_BIN). Install PHP 8.5+ or run inside Docker: ./stack.sh exec php bash scripts/first-run.sh"
fi
PHP_MAJOR=$("$PHP_BIN" -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR=$("$PHP_BIN" -r 'echo PHP_MINOR_VERSION;')
if (( PHP_MAJOR < 8 || (PHP_MAJOR == 8 && PHP_MINOR < 5) )); then
  echo -e "${RED}✗${NC} PHP $("$PHP_BIN" -v | head -1) — PaginiumCMS requires PHP ^8.5"
  echo
  echo "  Host CLI is too old. Use one of:"
  echo "    • Docker:  cd /srv/docker/paginiumcms-prod && ./stack.sh exec php bash scripts/first-run.sh"
  echo "    • Upgrade: install php8.5-cli on the server, then: PHP_BIN=php8.5 ./scripts/first-run.sh"
  exit 1
fi
ok "PHP $("$PHP_BIN" -r 'echo PHP_VERSION;')"

# ------------------------------------------------------------------ .env
if [[ ! -f .env ]]; then
  cp .env.example .env
  ok "Created .env from .env.example"
else
  ok ".env already exists"
fi

if ! grep -q '^APP_KEY=' .env || grep -qE '^APP_KEY=\s*$' .env; then
  if command -v openssl >/dev/null 2>&1; then
    app_key="base64:$(openssl rand -base64 32 | tr -d '\n')"
    if grep -q '^APP_KEY=' .env; then
      sed -i "s|^APP_KEY=.*|APP_KEY=${app_key}|" .env
    else
      printf '\nAPP_KEY=%s\n' "$app_key" >> .env
    fi
    ok "Generated APP_KEY in .env"
  else
    warn "openssl not found — set APP_KEY manually in .env for at-rest encryption"
  fi
fi

# ------------------------------------------------------------------ storage
STORAGE_DIRS=(
  backend/storage/cache
  backend/storage/cache/locks
  backend/storage/backups
  backend/storage/dev
  backend/storage/logs/app
  backend/storage/logs/audit
  backend/storage/logs/event
  backend/storage/logs/user
  backend/storage/logs/developer
  backend/storage/logs/debug
  backend/storage/app/content/data/users
  backend/storage/app/content/data/index
  backend/storage/app/content/data/jobs
  backend/storage/app/content/pages
  backend/storage/app/content/blog
  backend/storage/app/content/media
  backend/storage/app/content/trash
  backend/storage/app/demo
  backend/storage/app/demo/data/users
  backend/storage/app/demo/data/index
  backend/storage/app/demo/pages
  backend/storage/app/demo/blog
  backend/storage/app/demo/media
)

for dir in "${STORAGE_DIRS[@]}"; do
  mkdir -p "$dir"
done

LOCKS_JSON="$ROOT/backend/storage/app/content/data/locks.json"
if [[ ! -f "$LOCKS_JSON" ]]; then
  printf '[]\n' > "$LOCKS_JSON"
fi

ok "Storage directories ready (${#STORAGE_DIRS[@]} paths)"

# ------------------------------------------------------------------ composer
COMPOSER_FLAGS=(install --no-interaction --prefer-dist)
if [[ "${COMPOSER_NO_DEV:-1}" == "1" ]]; then
  COMPOSER_FLAGS+=(--no-dev --optimize-autoloader)
fi

# Docker bind-mount: repo owned by host user, composer/git run as root in container.
if [[ -d .git ]] && command -v git >/dev/null 2>&1; then
  git config --global --add safe.directory "$ROOT" 2>/dev/null || true
fi

run_composer_install() {
  COMPOSER_ALLOW_SUPERUSER=1 composer "${COMPOSER_FLAGS[@]}"
}

if [[ ! -f vendor/autoload.php ]]; then
  if command -v composer >/dev/null 2>&1; then
    run_composer_install
    ok "Composer dependencies installed"
  else
    fail "composer not found and vendor/ is missing — install PHP Composer first"
  fi
elif ! COMPOSER_ALLOW_SUPERUSER=1 composer validate --no-check-publish >/dev/null 2>&1; then
  warn "composer.lock out of sync — running composer install"
  run_composer_install
  ok "Composer dependencies refreshed"
else
  ok "vendor/ present (run 'composer install' manually if dependencies changed)"
fi

# ------------------------------------------------------------------ first admin
"$PHP_BIN" backend/bin/bootstrap-admin.php
ok "Admin bootstrap finished"

# ------------------------------------------------------------------ content diagnose
if "$PHP_BIN" backend/bin/console content:diagnose --fix; then
  ok "content:diagnose — healthy"
else
  warn "content:diagnose reported issues — review output above"
fi

# ------------------------------------------------------------------ optional frontend
if [[ "${INSTALL_FRONTEND:-0}" == "1" ]]; then
  if command -v npm >/dev/null 2>&1; then
    (cd frontend && npm ci)
    ok "Frontend dependencies installed (npm ci)"
  else
    warn "npm not found — skip frontend install"
  fi
fi

echo
echo -e "${GREEN}=== First-run complete ===${NC}"
echo
echo "Next steps:"
echo "  • Docker:  docker compose up -d"
echo "             curl http://localhost:8080/api/health"
echo "  • Native:  cd backend/public && php -S localhost:8080"
echo "             cd frontend && npm run dev   # http://localhost:3025"
echo "  • Login:   admin@paginium.local / Admin123!ChangeMe (or your FIRST_ADMIN_* env)"
echo "  • Docs:    docs/developer/LOCAL_SETUP.md"
