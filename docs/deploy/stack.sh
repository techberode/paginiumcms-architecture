#!/usr/bin/env bash
# PaginiumCMS — Docker stack wrapper for production/demo.
# Install in /var/lib/docker/compose/paginiumcms[-demo]/stack.sh and chmod 0750.
# The adjacent .env is trusted operator configuration; keep it root/deploy-owned
# and never writable by the web application.
set -euo pipefail
umask 027

STACK_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$STACK_DIR"

ENV_FILE="$STACK_DIR/.env"
if [[ ! -r "$ENV_FILE" ]]; then
  echo "Missing or unreadable $ENV_FILE" >&2
  exit 1
fi

set -a
# shellcheck disable=SC1091
source "$ENV_FILE"
set +a

: "${APP_ROOT:?APP_ROOT missing in stack .env}"
: "${COMPOSE_PROJECT_NAME:?COMPOSE_PROJECT_NAME missing in stack .env}"
: "${BACKEND_PORT:?BACKEND_PORT missing in stack .env}"

BASE="$APP_ROOT/docker-compose.yml"
OVERRIDE="$STACK_DIR/docker-compose.prod.yml"

if [[ ! -f "$BASE" ]]; then
  echo "Missing $BASE — is APP_ROOT correct?" >&2
  exit 1
fi

if [[ ! -f "$OVERRIDE" ]]; then
  echo "Missing $OVERRIDE — copy it from docs/deploy/docker-compose.prod.yml" >&2
  exit 1
fi

COMPOSE=(
  docker compose
  -f "$BASE"
  -f "$OVERRIDE"
  --project-name "$COMPOSE_PROJECT_NAME"
)

# Validate the merged model before executing an operational command.
"${COMPOSE[@]}" config --quiet
exec "${COMPOSE[@]}" "$@"
