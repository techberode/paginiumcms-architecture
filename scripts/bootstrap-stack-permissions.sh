#!/usr/bin/env bash
# PaginiumCMS — one-time host bootstrap so admin deploy readiness sees stack.sh (www-data).
#
# Run ON THE SERVER as a user with sudo (not inside PHP as www-data).
#
# Why: deploy readiness runs in PHP-FPM as www-data and calls is_executable() on
# $STACK_DIR/stack.sh. A plain `sudo cp` + `chmod 750` leaves root:root, which
# blocks www-data and shows stack_script_missing in the admin UI.
#
# Required env:
#   STACK_DIR   — e.g. /var/lib/docker/compose/paginiumcms
#
# Optional env:
#   APP_ROOT    — when stack.sh is missing, copy from $APP_ROOT/docs/deploy/stack.sh
#
# Example:
#   STACK_DIR=/var/lib/docker/compose/paginiumcms \
#   APP_ROOT=/var/www/paginiumcms.com \
#   ./scripts/bootstrap-stack-permissions.sh
set -euo pipefail

STACK_DIR="${STACK_DIR:?Set STACK_DIR to the Docker compose stack directory on the host}"
APP_ROOT="${APP_ROOT:-}"
GROUP="www-data"

if [[ ! -d "$STACK_DIR" ]]; then
  echo "ERROR: STACK_DIR is not a directory: $STACK_DIR" >&2
  exit 1
fi

STACK_SCRIPT="$STACK_DIR/stack.sh"

if [[ -n "$APP_ROOT" && -f "$APP_ROOT/docs/deploy/stack.sh" ]]; then
  echo "→ installing stack.sh from $APP_ROOT/docs/deploy/stack.sh"
  sudo cp "$APP_ROOT/docs/deploy/stack.sh" "$STACK_SCRIPT"
elif [[ ! -f "$STACK_SCRIPT" ]]; then
  echo "ERROR: $STACK_SCRIPT missing — set APP_ROOT to copy from docs/deploy/stack.sh" >&2
  exit 1
fi

if [[ -n "$APP_ROOT" && -f "$APP_ROOT/docs/deploy/docker-compose.deploy-key.yml" ]]; then
  echo "→ installing docker-compose.deploy-key.yml"
  sudo cp "$APP_ROOT/docs/deploy/docker-compose.deploy-key.yml" "$STACK_DIR/docker-compose.deploy-key.yml"
  sudo chmod 640 "$STACK_DIR/docker-compose.deploy-key.yml"
fi

echo "→ PaginiumCMS stack permissions bootstrap"
echo "   STACK_DIR=$STACK_DIR"
echo "   group=$GROUP (PHP-FPM / admin deploy readiness)"

sudo chown "root:$GROUP" "$STACK_DIR"
sudo chmod 750 "$STACK_DIR"
sudo chown "root:$GROUP" "$STACK_SCRIPT"
sudo chmod 750 "$STACK_SCRIPT"

REDIS_DATA="$STACK_DIR/redis-data"
sudo mkdir -p "$REDIS_DATA"
# Official redis:7 image runs as uid 999 (redis). Avoid root-owned dumps blocking container writes.
if sudo chown 999:999 "$REDIS_DATA" 2>/dev/null; then
  sudo chmod 750 "$REDIS_DATA"
else
  sudo chown root:root "$REDIS_DATA"
  sudo chmod 777 "$REDIS_DATA"
fi

echo "→ stack.sh: $(stat -c '%U:%G %a' "$STACK_SCRIPT")"
echo "→ redis-data: $(stat -c '%U:%G %a' "$REDIS_DATA")"
echo ""
echo "→ Verify inside the PHP container (must run as www-data, not root):"
echo "   cd \"$STACK_DIR\" && ./stack.sh exec -u www-data php test -x \"$STACK_SCRIPT\" && echo OK"
echo ""
echo "→ Re-copying stack.sh later? Re-run this script (sudo cp alone resets root:root)."
