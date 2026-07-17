#!/usr/bin/env bash
# PaginiumCMS API smoke test (Iteration 21) – requires backend on :8080
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BASE_URL="${BASE_URL:-http://localhost:8080}"
COLLECTION="$ROOT/docs/api/PaginiumCMS.postman_collection.json"

echo "API smoke → $BASE_URL"

if ! curl -sf "$BASE_URL/api/health" >/dev/null; then
  echo "Backend not reachable at $BASE_URL – start: cd backend/public && php -S localhost:8080"
  exit 1
fi

npx --yes newman run "$COLLECTION" \
  --env-var "baseUrl=$BASE_URL" \
  --bail \
  --color on

echo "Newman smoke OK"
