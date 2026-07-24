#!/usr/bin/env bash
# scripts/create-github-releases.sh
# Creates GitHub Releases for Public Beta tags (requires: gh auth login).
# Idempotent: skips tags that already have a release on GitHub.
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

echo "=== PaginiumCMS — GitHub Releases ==="
echo "Repo: $(gh repo view --json nameWithOwner -q .nameWithOwner 2>/dev/null || echo '(unknown)')"
echo

# Ensure beta tags exist on remote (ignore if already pushed)
for tag in v2.1.0-beta.1 v2.1.0-beta.2 v2.1.0-beta.3; do
  git push origin "$tag" 2>/dev/null || true
done

# v2.1.0-beta.1 — Wave 7 (docs scope)
if ! gh release view v2.1.0-beta.1 >/dev/null 2>&1; then
  gh release create v2.1.0-beta.1 \
    --title "v2.1.0-beta.1 — Public Beta 1" \
    --notes "$(cat <<'EOF'
## Public Beta 1 (Wave 7)

First public beta scope for testers and early adopters.

- [PUBLIC_BETA1.md](docs/PUBLIC_BETA1.md) — scope and known limitations
- [BETA_TESTER.md](docs/user/BETA_TESTER.md) — smoke checklist

**Note:** For security review and testing, use **v2.1.0-beta.3** (latest patch).
EOF
)"
  echo "Created release v2.1.0-beta.1"
else
  echo "Release v2.1.0-beta.1 already exists — skipped"
fi

# v2.1.0-beta.2 — Beta 1 Testing
if ! gh release view v2.1.0-beta.2 >/dev/null 2>&1; then
  gh release create v2.1.0-beta.2 \
    --title "v2.1.0-beta.2 — Beta 1 Testing" \
    --notes "$(cat <<'EOF'
## Summary

Pre-push security gate (audit trail CSV sanitization, ISS-077).

**Superseded by [v2.1.0-beta.3](https://github.com/techberode/paginiumcms-architecture/releases/tag/v2.1.0-beta.3)** — React Router npm GHSA fix (ISS-078). Use beta.3 for testers and security review.

## Fixed

- **C11-AUDITTRAIL-CSV** — `AuditTrailService::exportAuditToCsv()` — all cells via `LogSanitizer` + CSV quoting. Regression test `AuditTrailServiceTest`.

## Documentation

- [SECURITY_REVIEW.md](docs/SECURITY_REVIEW.md) · [SECURITY.md](SECURITY.md)
- [PUBLIC_BETA1.md](docs/PUBLIC_BETA1.md) · [BETA_TESTER.md](docs/user/BETA_TESTER.md)

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

# v2.1.0-beta.3 — Beta 1 patch (recommended)
if ! gh release view v2.1.0-beta.3 >/dev/null 2>&1; then
  gh release create v2.1.0-beta.3 \
    --title "v2.1.0-beta.3 — Beta 1 patch (React Router + CMS info)" \
    --notes "$(cat <<'EOF'
## Summary

Patch after **v2.1.0-beta.2**. Three **moderate** React Router npm advisories were published on GitHub **after** the beta.2 tag — not visible at `npm audit --audit-level=high`. Fixed by upgrading to `react-router-dom@7.18.1`. Also adds read-only **Settings → PaginiumCMS – info**.

## Fixed — ISS-078 (dependency)

| GHSA | Title |
|------|-------|
| [GHSA-wrjc-x8rr-h8h6](https://github.com/advisories/GHSA-wrjc-x8rr-h8h6) | Open redirect via backslash in `<Link>` / `useNavigate` (CVE-2025-68470 bypass) |
| [GHSA-jjmj-jmhj-qwj2](https://github.com/advisories/GHSA-jjmj-jmhj-qwj2) | Open redirect leading to XSS |
| [GHSA-337j-9hxr-rhxg](https://github.com/advisories/GHSA-337j-9hxr-rhxg) | Arbitrary constructor injection via `deserializeErrors()` (SSR hydration) |

**PaginiumCMS note:** Beta 1 admin is SPA-only (`BrowserRouter`) — SSR hydration issue does not apply. Open redirect risk is low for static admin routes; still patched upstream.

- `react-router-dom`: **6.30.4** → **7.18.1**
- `npm audit --audit-level=moderate` → **0 vulnerabilities**

## Added

- **Settings → Systém → PaginiumCMS – info** — version, MIT license link, locales, stack, doc links
- Root `LICENSE` (MIT)

## Docs

- [ISS-078](docs/ISSUES.md) · [SECURITY_REVIEW.md](docs/SECURITY_REVIEW.md#post-publication-dependency-disclosures-after-v2110-beta2)

## Verification

- `./scripts/iteration-gate.sh`
- `composer audit` + `npm audit --audit-level=moderate` = 0 CVE

## Quick start

```bash
git checkout v2.1.0-beta.3
./scripts/first-run.sh
docker compose up -d
```

## Notes

- **Recommended** tag for testers and security review.
- beta.2 release notes claimed `npm audit --audit-level=high` = 0 — still true; these were **moderate** and disclosed post-tag.
EOF
)"
  echo "Created release v2.1.0-beta.3"
else
  echo "Release v2.1.0-beta.3 already exists — skipped"
fi

echo
echo "Done. View: gh release list"
echo "Latest:  https://github.com/$(gh repo view --json nameWithOwner -q .nameWithOwner)/releases/latest"
