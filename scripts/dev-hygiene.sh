#!/usr/bin/env bash
# Maintainer dev-machine cleanup — ONLY prefixed test artefacts (qa-*, seo-test-*, @example.com).
# Real pages/articles/media/users/backups are never targeted.
#
# Usage:
#   ./scripts/dev-hygiene.sh scan
#   ./scripts/dev-hygiene.sh purge
#   ./scripts/dev-hygiene.sh purge --include-logs

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

PHP_BIN="${PHP_BIN:-php}"
ACTION="${1:-scan}"
shift || true

case "$ACTION" in
  scan)
    exec "$PHP_BIN" backend/bin/console dev:hygiene --scan "$@"
    ;;
  purge|purge-tests|purge-dev)
    if [[ "$ACTION" == "purge-dev" ]]; then
      echo "Note: purge-dev now uses the same prefix-only rules as purge (real content is preserved)." >&2
    fi
    exec "$PHP_BIN" backend/bin/console dev:hygiene --confirm "$@"
    ;;
  *)
    echo "Usage: $0 {scan|purge} [--include-logs]" >&2
    exit 1
    ;;
esac
