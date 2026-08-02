#!/usr/bin/env bash
# Capture PHPUnit output off-console, sanitize, verify, then publish safe log only.
set -u

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

RAW_LOG="${RUNNER_TEMP:-/tmp}/paginium-backend-tests.raw.log"
SAFE_LOG="${RUNNER_TEMP:-/tmp}/paginium-backend-tests.safe.log"

export PAGINIUMCMS_CI_STRICT_OUTPUT=1

set +e
./vendor/bin/phpunit --colors=never >"$RAW_LOG" 2>&1
test_exit=$?
set -e

python3 .github/scripts/sanitize-ci-log.py "$RAW_LOG" >"$SAFE_LOG"
chmod +x .github/scripts/verify-ci-log-redaction.sh
.github/scripts/verify-ci-log-redaction.sh "$SAFE_LOG"

echo "=== Sanitized PHPUnit output ==="
cat "$SAFE_LOG"

exit "$test_exit"
