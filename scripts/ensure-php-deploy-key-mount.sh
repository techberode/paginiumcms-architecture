#!/usr/bin/env bash
# Remount the GitHub deploy key into the PHP container so admin UI git fetch works.
# Run ON THE HOST (not inside admin / not inside PHP).
#
#   STACK_DIR=/var/lib/docker/compose/paginiumcms ./scripts/ensure-php-deploy-key-mount.sh
set -euo pipefail

STACK_DIR="${STACK_DIR:-/var/lib/docker/compose/paginiumcms}"
KEY="${GITHUB_DEPLOY_KEY_FILE:-/var/lib/paginiumcms/secrets/github_deploy_key}"

if [[ ! -f "$KEY" ]]; then
  echo "Missing $KEY — run scripts/bootstrap-github-deploy-key.sh first." >&2
  exit 1
fi

if [[ ! -x "$STACK_DIR/stack.sh" ]]; then
  echo "Missing executable $STACK_DIR/stack.sh" >&2
  exit 1
fi

ENV_FILE="$STACK_DIR/.env"
if [[ ! -r "$ENV_FILE" ]]; then
  echo "Missing or unreadable $ENV_FILE" >&2
  exit 1
fi

# shellcheck disable=SC1090
set -a && source "$ENV_FILE" && set +a
: "${APP_ROOT:?APP_ROOT missing in stack .env}"

OVERLAY_SRC="$APP_ROOT/docs/deploy/docker-compose.deploy-key.yml"
if [[ ! -f "$OVERLAY_SRC" ]]; then
  OVERLAY_SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/docs/deploy/docker-compose.deploy-key.yml"
fi
if [[ ! -f "$OVERLAY_SRC" ]]; then
  echo "Missing deploy-key compose overlay (docs/deploy/docker-compose.deploy-key.yml)." >&2
  exit 1
fi

install -m 640 "$OVERLAY_SRC" "$STACK_DIR/docker-compose.deploy-key.yml"
if [[ -f "$APP_ROOT/docs/deploy/stack.sh" ]]; then
  install -m 750 "$APP_ROOT/docs/deploy/stack.sh" "$STACK_DIR/stack.sh"
  if id www-data >/dev/null 2>&1; then
    chown root:www-data "$STACK_DIR/stack.sh" 2>/dev/null || true
  fi
fi

export GITHUB_DEPLOY_KEY_FILE="$KEY"
echo "→ recreate php with deploy-key overlay"
"$STACK_DIR/stack.sh" up -d --force-recreate php
echo "→ done. In admin: System update → Verify connection."
