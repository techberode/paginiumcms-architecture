#!/usr/bin/env bash
# scripts/create-github-releases.sh
# Creates GitHub Releases for Public Beta tags (requires: gh auth login).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if ! command -v gh >/dev/null 2>&1; then
  echo "Install GitHub CLI: https://cli.github.com/" >&2
  exit 1
fi

if ! gh auth status >/dev/null 2>&1; then
  echo "Run: gh auth login" >&2
  exit 1
fi

# Ensure tags exist on remote
git push origin v2.1.0-beta.1 2>/dev/null || true
git push origin v2.1.0-beta.2

# v2.1.0-beta.1 — Wave 7 (docs scope)
if ! gh release view v2.1.0-beta.1 >/dev/null 2>&1; then
  gh release create v2.1.0-beta.1 \
    --title "v2.1.0-beta.1 — Public Beta 1" \
    --notes "$(cat <<'EOF'
## Public Beta 1 (Wave 7)

First public beta scope for testers and early adopters.

- [PUBLIC_BETA1.md](docs/PUBLIC_BETA1.md) — scope and known limitations
- [BETA_TESTER.md](docs/user/BETA_TESTER.md) — smoke checklist

**Note:** For security review and testing, use **v2.1.0-beta.2** (includes pre-push security gate).
EOF
)"
  echo "Created release v2.1.0-beta.1"
else
  echo "Release v2.1.0-beta.1 already exists — skipped"
fi

# v2.1.0-beta.2 — Beta 1 Testing (recommended)
if ! gh release view v2.1.0-beta.2 >/dev/null 2>&1; then
  gh release create v2.1.0-beta.2 \
    --title "v2.1.0-beta.2 — Beta 1 Testing" \
    --notes "$(cat <<'EOF'
## Summary

Recommended tag for beta testers and **security review**. Includes pre-push security gate (audit trail CSV sanitization, ISS-077).

## Fixed

- **C11-AUDITTRAIL-CSV** — `AuditTrailService::exportAuditToCsv()` — all cells via `LogSanitizer` + CSV quoting. Regression test `AuditTrailServiceTest`.

## Documentation

- [SECURITY_REVIEW.md](docs/SECURITY_REVIEW.md) — external auditor guide (attack surface, test checklist)
- [developer/SECURITY.md](docs/developer/SECURITY.md) — security architecture
- [SECURITY.md](SECURITY.md) — vulnerability reporting policy
- [PUBLIC_BETA1.md](docs/PUBLIC_BETA1.md) · [BETA_TESTER.md](docs/user/BETA_TESTER.md)

## Verification

- PHPUnit full suite green · PHPStan L8 = 0
- `composer audit` + `npm audit --audit-level=high` = 0 CVE

## Quick start

```bash
git checkout v2.1.0-beta.2
./scripts/first-run.sh
docker compose up -d
```
EOF
)"
  echo "Created release v2.1.0-beta.2"
else
  echo "Release v2.1.0-beta.2 already exists — skipped"
fi

echo "Done. View: gh release list"
