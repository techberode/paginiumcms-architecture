#!/usr/bin/env bash
# PaginiumCMS — Docker stack wrapper (prod / demo).
# Install: copy to /var/lib/docker/compose/paginiumcms[-demo]/stack.sh + chmod +x
set -euo pipefail

STACK_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$STACK_DIR"

set -a
# shellcheck disable=SC1091
source "$STACK_DIR/.env"
set +a

: "${APP_ROOT:?APP_ROOT missing in stack .env}"
: "${COMPOSE_PROJECT_NAME:?COMPOSE_PROJECT_NAME missing in stack .env}"
: "${BACKEND_PORT:?BACKEND_PORT missing in stack .env}"

BASE="$APP_ROOT/docker-compose.yml"
OVERRIDE="$STACK_DIR/docker-compose.prod.yml"

if [[ ! -f "$BASE" ]]; then
  echo "Missing $BASE — is APP_ROOT correct in stack .env?" >&2
  exit 1
fi

if [[ ! -f "$OVERRIDE" ]]; then
  echo "Missing $OVERRIDE — copy from repo: docs/deploy/docker-compose.prod.yml" >&2
  exit 1
fi

exec docker compose \
  -f "$BASE" \
  -f "$OVERRIDE" \
  --project-name "$COMPOSE_PROJECT_NAME" \
  "$@"
