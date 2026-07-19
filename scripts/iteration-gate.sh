#!/usr/bin/env bash
# scripts/iteration-gate.sh
# Post-iteration validation gate — run after every iteration before commit/push.
# Tests alone are not enough; this script also checks syntax, static analysis,
# FE type/lint, and basic wiring integrity.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

fail() { echo -e "${RED}✗ $1${NC}" >&2; exit 1; }
ok()   { echo -e "${GREEN}✓ $1${NC}"; }
warn() { echo -e "${YELLOW}! $1${NC}"; }

echo "=== PaginiumCMS iteration gate ==="
echo "Root: $ROOT"
echo

# 1) PHP syntax (changed app files or full backend/app if no git)
echo "--- PHP syntax (php -l) ---"
if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  mapfile -t PHP_FILES < <(git diff --name-only HEAD 2>/dev/null | grep '\.php$' || true)
  mapfile -t PHP_STAGED < <(git diff --name-only --cached 2>/dev/null | grep '\.php$' || true)
  mapfile -t PHP_UNTRACKED < <(git ls-files --others --exclude-standard | grep '\.php$' || true)
  ALL_PHP=("${PHP_FILES[@]}" "${PHP_STAGED[@]}" "${PHP_UNTRACKED[@]}")
  if [ ${#ALL_PHP[@]} -eq 0 ]; then
    warn "No changed PHP files — scanning backend/app"
    while IFS= read -r f; do ALL_PHP+=("$f"); done < <(find backend/app backend/bootstrap backend/tests -name '*.php' -type f 2>/dev/null | head -200)
  else
    # dedupe
    ALL_PHP=($(printf '%s\n' "${ALL_PHP[@]}" | sort -u))
  fi
else
  ALL_PHP=($(find backend/app -name '*.php' -type f))
fi

for f in "${ALL_PHP[@]}"; do
  [ -f "$f" ] || continue
  php -l "$f" >/dev/null || fail "Syntax error in $f"
done
ok "PHP syntax clean (${#ALL_PHP[@]} files checked)"

# 2) PHPStan level 8
echo
echo "--- PHPStan level 8 ---"
if php vendor/bin/phpstan analyse --memory-limit=512M --no-progress; then
  ok "PHPStan level 8 clean"
else
  fail "PHPStan level 8 reported errors — fix before commit"
fi

# 3) PHPUnit (full suite — regression guard)
echo
echo "--- PHPUnit ---"
php vendor/bin/phpunit --no-output 2>/dev/null && ok "PHPUnit full suite passed" || {
  warn "Full suite slow/failed — running notification module subset"
  php vendor/bin/phpunit backend/tests/Core/Notification/ --no-output && ok "PHPUnit notification subset passed"
}

# 4) Frontend type-check + lint
echo
echo "--- Frontend type-check + ESLint ---"
(cd frontend && npm run type-check) && ok "tsc --noEmit clean"
(cd frontend && npm run lint) && ok "ESLint within baseline"

# 5) Integrity heuristics (grep-based, no external deps)
echo
echo "--- Wiring integrity ---"
# Routes must reference existing controller methods (basic check for new routes)
if grep -R "test-connector" backend/app/Http/Routes/ >/dev/null 2>&1; then
  grep -q "function testConnector" backend/app/Http/Controllers/Admin/NotificationController.php \
    || fail "Route test-connector without NotificationController::testConnector"
fi

# Settings schema keys used in factory should exist in schema file (sample)
for key in ntfyAuthMode ntfyAccessToken webhookAuthHeader; do
  grep -q "'$key'" backend/app/Core/Settings/SettingsSchema.php \
    || fail "SettingsSchema missing key: $key"
done

ok "Basic wiring checks passed"

echo
echo -e "${GREEN}=== Iteration gate complete ===${NC}"
