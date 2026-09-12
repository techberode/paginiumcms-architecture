#!/usr/bin/env bash
# It.66 — focused security regression packs (no public-path cost).
# Usage: ./scripts/security-regression.sh
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "=== CodePolicy + shortcode/theme hostile pack ==="
vendor/bin/phpunit --colors=always \
  backend/tests/Core/CodePolicy/ \
  backend/tests/Core/Layout/ShortcodeDefinitionManagerTest.php \
  backend/tests/Http/Themes/ThemeImporterTest.php

echo "=== XSS / Zip / headers pack ==="
vendor/bin/phpunit --colors=always \
  backend/tests/Core/Security/Services/ContentSecuritySanitizerTest.php \
  backend/tests/Core/Security/Services/ZipEntryGuardTest.php \
  backend/tests/Http/Middleware/SecurityMiddlewareTest.php

echo "=== Upload policy pack (It.78) ==="
vendor/bin/phpunit --colors=always \
  backend/tests/Core/Security/Upload/ \
  backend/tests/Core/Security/Services/UploadSecurityValidatorTest.php

echo "=== Static outbound hygiene ==="
./scripts/security-static-grep.sh

echo "=== FE security vitest ==="
(cd frontend && npm run test:security)

echo "security-regression: OK"
