#!/usr/bin/env bash
# Deploy frontend dist to LAN nginx (same-origin API via /api proxy).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT/frontend"

HOST="${DEPLOY_HOST:-192.168.10.26}"
PORT="${DEPLOY_SSH_PORT:-49555}"
USER="${DEPLOY_USER:-maxxim}"
REMOTE="${DEPLOY_PATH:-/var/www/paginium-test/dist/}"

echo "→ Building production bundle (same-origin API)..."
npm run build:prod

JS="$(grep -o 'index-[^"]*\.js' dist/index.html)"
echo "→ Local bundle: $JS"

echo "→ Uploading to ${USER}@${HOST}:${REMOTE}"
rsync -av --delete -e "ssh -p ${PORT}" dist/ "${USER}@${HOST}:${REMOTE}"

REMOTE_JS="$(ssh -p "${PORT}" "${USER}@${HOST}" "grep -o 'index-[^\"]*\\.js' ${REMOTE}index.html")"
echo "→ Remote bundle: ${REMOTE_JS}"

if [[ "$JS" != "$REMOTE_JS" ]]; then
  echo "ERROR: remote index.html does not match local build" >&2
  exit 1
fi

echo "→ Health check"
curl -sf "http://${HOST}:8081/api/health" | head -c 200
echo ""
echo "Done. Open http://${HOST}:8081/ and hard-refresh (Ctrl+Shift+R)."
