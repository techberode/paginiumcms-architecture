#!/usr/bin/env bash
# PaginiumCMS — one-time host bootstrap so admin UI deploy works from Docker (www-data).
#
# Run ON THE SERVER as a user with sudo (not inside PHP as www-data).
#
# Required env:
#   APP_ROOT   — e.g. /var/www/paginiumcms.com
#
# What it does:
#   - group www-data on checkout (.git, vendor, frontend, storage)
#   - dirs 2775 / files 664 (same pattern as ISS-094 storage)
#   - deploy tool caches under backend/storage/app/deploy-cache
#
# Example:
#   APP_ROOT=/var/www/paginiumcms.com ./scripts/bootstrap-deploy-permissions.sh
set -euo pipefail

APP_ROOT="${APP_ROOT:?Set APP_ROOT to the git checkout root}"

if [[ ! -d "$APP_ROOT/.git" ]]; then
  echo "ERROR: $APP_ROOT is not a git checkout (.git missing)" >&2
  exit 1
fi

OWNER="$(stat -c '%U' "$APP_ROOT")"
GROUP="www-data"

echo "→ PaginiumCMS deploy permissions bootstrap"
echo "   APP_ROOT=$APP_ROOT"
echo "   owner=$OWNER group=$GROUP"

sudo chown -R "$OWNER:$GROUP" "$APP_ROOT"
sudo find "$APP_ROOT" -type d -exec chmod 2775 {} \;
sudo find "$APP_ROOT" -type f -exec chmod 664 {} \;
sudo find "$APP_ROOT/scripts" -type f -name '*.sh' -exec chmod 775 {} \;

CACHE="$APP_ROOT/backend/storage/app/deploy-cache"
sudo mkdir -p "$CACHE/composer" "$CACHE/npm"
sudo chown -R "$GROUP:$GROUP" "$CACHE"
sudo chmod -R 2775 "$CACHE"

echo "→ Done. In app .env (Docker mount) set:"
echo "   APP_ROOT=/var/www/html"
echo "   STACK_DIR=/var/lib/docker/compose/paginiumcms   # host path; restart may be manual from UI deploy"
echo ""
echo "→ Smoke (inside PHP container):"
echo "   docker compose exec -u www-data php git -C /var/www/html fetch origin --tags --dry-run"
