#!/usr/bin/env bash
# Collect host metrics into PaginiumCMS (It.82d).
# Prefer CLI on the app host: php backend/bin/console metrics:host-collect
# Optional HTTP ingest when HOST_METRICS_INGEST_URL and HOST_METRICS_INGEST_TOKEN are set.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"

if [[ -x "${ROOT}/backend/bin/console" ]]; then
  exec php "${ROOT}/backend/bin/console" metrics:host-collect "$@"
fi

echo "backend/bin/console not found" >&2
exit 1
