#!/usr/bin/env bash
# Deploy frontend dist to LAN nginx (same-origin API via /api proxy).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT/frontend"

HOST="${DEPLOY_HOST:-192.168.10.26}"
PORT="${DEPLOY_SSH_PORT:-49555}"
USER="${DEPLOY_USER:-marian}"
REMOTE="${DEPLOY_PATH:-/var/www/paginium-test/dist/}"
# Použi ~/.ssh/config Host (napr. homelab) — rovnaký kľúč/user ako pri bežnom SSH
SSH_HOST="${DEPLOY_SSH_HOST:-}"

if [[ -n "$SSH_HOST" ]]; then
  RSYNC_SSH=(ssh)
  RSYNC_TARGET="${SSH_HOST}:${REMOTE}"
  SSH_CMD=(ssh "$SSH_HOST")
else
  RSYNC_SSH=(ssh -p "$PORT")
  RSYNC_TARGET="${USER}@${HOST}:${REMOTE}"
  SSH_CMD=(ssh -p "$PORT" "${USER}@${HOST}")
fi

echo "→ Building production bundle (same-origin API)..."
npm run build:prod

JS="$(grep -o 'index-[^"]*\.js' dist/index.html)"
echo "→ Local bundle: $JS"

echo "→ Uploading to ${RSYNC_TARGET}"
rsync -av --delete -e "${RSYNC_SSH[*]}" dist/ "${RSYNC_TARGET}"

REMOTE_JS="$("${SSH_CMD[@]}" "grep -o 'index-[^\"]*\\.js' ${REMOTE}index.html")"
echo "→ Remote bundle: ${REMOTE_JS}"

if [[ "$JS" != "$REMOTE_JS" ]]; then
  echo "ERROR: remote index.html does not match local build" >&2
  exit 1
fi

echo "→ Health check"
curl -sf "http://${HOST}:8081/api/health" | head -c 200
echo ""
echo "Done. Open http://${HOST}:8081/ and hard-refresh (Ctrl+Shift+R)."
