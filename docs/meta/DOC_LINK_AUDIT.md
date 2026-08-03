# Documentation link audit (2026-08-03)

Post-migration audit after **Iteration 18** (`docs/en/`, `docs/sk/`, shared `docs/deploy/`).

## Tools

| Script | Purpose |
|--------|---------|
| [`scripts/audit-doc-links.py`](../../scripts/audit-doc-links.py) | Scan markdown links; fail if target file missing |
| [`scripts/fix-doc-links.py`](../../scripts/fix-doc-links.py) | Re-apply bulk path corrections after locale tree move |

Run from repo root:

```bash
python3 scripts/audit-doc-links.py
python3 scripts/fix-doc-links.py   # idempotent re-apply if needed
python3 scripts/audit-doc-links.py
```

## Initial findings (before fix)

**1 413 broken internal links** across 528 markdown files.

| Root cause | Approx. refs | Example |
|------------|-------------|---------|
| `../CHANGELOG.md` from `docs/en/*` pointed at `docs/CHANGELOG.md` instead of repo root | 628 | `docs/en/ITERATION_26.md` |
| Cross-locale ISSUES paths `../../SK/docs/ISSUES.md` | 605 | stale bundle layout (`EN/` / `SK/` at repo root) |
| Flat `docs/NAVIGATION.md` linked sibling `ITERATION_*.md` not present at `docs/` root | 83 | missing flat symlinks |
| `../SECURITY.md` from `docs/en/NAVIGATION.md` | 28 | should be `../../SECURITY.md` |
| Root README linked `docs/PHILOSOPHY.md` etc. without flat compat symlinks | 13 | flat path missing |

## Fixes applied (2026-08-03)

1. **Flat compatibility symlinks** — `docs/<file>.md` → `docs/en/<file>.md` for top-level EN docs (except `ISSUES.md`, `README.md` kept as copies; `NAVIGATION.md` → `en/NAVIGATION.md`).
2. **Bulk path rewrite** in `docs/en/**`, `docs/sk/**`, flat `docs/ISSUES.md`:
   - `../../SK/docs/ISSUES.md` → `../sk/ISSUES.md` (or `sk/ISSUES.md` from flat `docs/ISSUES.md`)
   - `../CHANGELOG.md` → depth-aware path to repo-root `CHANGELOG.md`
   - Project root files (`SECURITY.md`, `AUDIT_REPORT.md`, …) use `../../` from `docs/en/`, `../../../` from `docs/en/developer/`
3. **NAVIGATION** — `../README.md` → sibling `README.md` inside each locale tree.

## Result

```
Scanned 527 markdown files, 6594 links checked.
Broken links: 0
```

## Caveats (manual / environment)

| Item | Note |
|------|------|
| `AUDIT_REPORT.md`, `RECOMMENDATIONS.md` | Gitignored; links valid only when maintainer keeps local copies |
| Anchor `#iss-xxx` | Audit checks **file existence**, not heading anchors |
| GitHub rendering | Symlinks (`docs/architecture` → `en/architecture`) render correctly in repo browser |
| External URLs | Not validated |

## Maintenance

After editing docs:

1. Run `python3 scripts/audit-doc-links.py` before commit.
2. Prefer links **relative to current file** inside `docs/en/` or `docs/sk/`.
3. From locale tree to repo root: `../../FILE.md` (one level deeper use `../../../`).
4. Cross-locale ISSUES: `../sk/ISSUES.md#iss-NNN` from `docs/en/`, `sk/ISSUES.md#iss-NNN` from flat `docs/ISSUES.md`.
