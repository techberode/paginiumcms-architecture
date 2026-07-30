#!/usr/bin/env bash
# It.66 — focused security regression packs (no public-path cost).
# Usage: ./scripts/security-regression.sh
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "=== CodePolicy pack ==="
vendor/bin/phpunit --colors=always backend/tests/Core/CodePolicy/

echo "=== XSS / Zip / headers pack ==="
vendor/bin/phpunit --colors=always \
  backend/tests/Core/Security/Services/ContentSecuritySanitizerTest.php \
  backend/tests/Core/Security/Services/ZipEntryGuardTest.php \
  backend/tests/Http/Middleware/SecurityMiddlewareTest.php

echo "=== Static outbound hygiene ==="
./scripts/security-static-grep.sh

echo "=== FE security vitest ==="
(cd frontend && npm run test:security)

echo "security-regression: OK"
