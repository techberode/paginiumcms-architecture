#!/usr/bin/env bash
# Fail when docs/ROADMAP.md still marks shipped Hybrid Engine iterations as ⏳.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ROADMAP="$ROOT/docs/ROADMAP.md"

if [[ ! -f "$ROADMAP" ]]; then
  echo "check-roadmap-stale: missing $ROADMAP" >&2
  exit 1
fi

fail=0

assert_not_planned() {
  local iteration="$1"
  local probe_class="$2"
  if [[ -f "$ROOT/backend/app/Modules/Origin/Probes/${probe_class}.php" ]]; then
    if grep -E "\\*\\*${iteration}\\*\\*.*⏳" "$ROADMAP" >/dev/null 2>&1; then
      echo "ROADMAP still marks It.${iteration} as ⏳ but ${probe_class} exists." >&2
      fail=1
    fi
  fi
}

assert_not_planned 71 PerformanceGuardFeatureProbe
assert_not_planned 74 ApiKeysFeatureProbe
assert_not_planned 73 MultiLocaleFeatureProbe

if [[ "$fail" -ne 0 ]]; then
  echo "Update docs/ROADMAP.md or en/operations/ROADMAP_SYNC.md." >&2
  exit 1
fi

echo "check-roadmap-stale: OK"
