#!/usr/bin/env bash
# Generate a GitHub deploy key for admin UI git fetch (no PAT in CMS settings).
#
# Usage (on host, once):
#   SECRETS_DIR=/var/lib/paginiumcms/secrets ./scripts/bootstrap-github-deploy-key.sh
#
# Then add the printed public key in GitHub → repo → Settings → Deploy keys (read-only),
# mount the private key into the PHP container, and set GITHUB_DEPLOY_SSH_KEY_PATH (see output).
set -euo pipefail

SECRETS_DIR="${SECRETS_DIR:-/var/lib/paginiumcms/secrets}"
KEY="${SECRETS_DIR}/github_deploy_key"

if [[ ! -d "$SECRETS_DIR" ]]; then
  echo "→ creating $SECRETS_DIR"
  sudo mkdir -p "$SECRETS_DIR"
  sudo chmod 750 "$SECRETS_DIR"
fi

if [[ ! -f "$KEY" ]]; then
  echo "→ generating ed25519 deploy key at $KEY"
  sudo ssh-keygen -t ed25519 -f "$KEY" -N "" -C "paginiumcms-deploy@$(hostname -f 2>/dev/null || hostname)"
fi

sudo chmod 600 "$KEY"
sudo chmod 644 "${KEY}.pub"

echo ""
echo "=== GitHub (repo → Settings → Deploy keys → Add, read-only) ==="
sudo cat "${KEY}.pub"
echo ""
echo "=== Docker Compose (php service) ==="
echo "  volumes:"
echo "    - ${KEY}:/run/secrets/github_deploy_key:ro"
echo "  environment:"
echo "    GITHUB_DEPLOY_SSH_KEY_PATH: /run/secrets/github_deploy_key"
echo ""
echo "Recreate PHP after editing compose. In admin: System update → Verify connection."
echo "GitHub token in settings stays optional (needed only for release API compare on private repos)."
