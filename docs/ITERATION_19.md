# Iteration 19 – Admin UX & translation policy

**Status:** 🚧 In progress (19a shipped in 2.0.44)  
**Version:** 2.0.44+

## Summary

Refactor admin navigation and settings into grouped sections; hardened translation save pipeline with staging, policy validation, and rejected copies.

## Done ✅ (19a · 2.0.44)

### Admin shell

| Change | Detail |
|--------|--------|
| Sidebar sections | 6 groups: Workspace, Inbox, Platform, Build, Security, Operations |
| Collapsible sections | Expand/collapse per section (expanded sidebar) |
| Sidebar collapse | Header toggle + footer button + `localStorage` persistence |
| Narrow sidebar | `w-64` expanded / icon rail collapsed |

### Settings

| Change | Detail |
|--------|--------|
| Category menu | **System · Site · Media · Security** |
| URL sync | `?category=system&group=logging` |
| New schema groups | `contentSecurity`, `uploadSecurity` |
| Cache panel | Shown under System category only |

### Translations

| Change | Detail |
|--------|--------|
| Staging save | Write → validate → promote (no overwrite on failure) |
| `TranslationPolicyValidator` | PHP lint + TS MessageTree policy |
| Rejected copies | `storage/translations/rejected/*.err` |
| Admin UI | Policy error banner + sequential toast (first error per save) |
| API | `POST /api/admin/translations/validate` |

### Hotfix

| Change | Detail |
|--------|--------|
| `HookManager` import | Fixed DI — 146 PHPUnit errors resolved |

## Remaining ⏳

- Wire `contentSecurity` / `uploadSecurity` into runtime sanitizers & upload validator
- Monaco inline error markers for translation policy
- Migrate remaining admin screens to i18n (It.18e+)
- Grav-inspired utility components (cards, hints, quick actions)

## Related

- [ITERATION_18.md](ITERATION_18.md) — i18n foundation
- [ROADMAP.md](ROADMAP.md)
