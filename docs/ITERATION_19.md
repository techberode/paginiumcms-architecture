# Iteration 19 – Admin UX, security runtime & auth

**Status:** 🚧 In progress (It.18e remainder)  
**Version:** 2.0.45 (unreleased)

## Summary

Grouped admin navigation and translation policy (19a); wire security schema to runtime (19b); custom locales and user avatars (19c); auth/login UX and configurable password policy (19d). Dashboard as standalone default landing.

---

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

### Hotfix (19a)

| Change | Detail |
|--------|--------|
| `HookManager` import | Fixed DI — 146 PHPUnit errors resolved |

---

## Done ✅ (19b · 2.0.45)

| Change | Detail |
|--------|--------|
| `UploadSecurityValidator` | Wired to `MediaRepository` — double extension, executables, MIME intersection, magic bytes toggle |
| `ContentSecuritySanitizer` | Wired to `ContentBodyRenderer` — HTML whitelist, script/SVG/XXE guards |
| Monaco markers | Translation policy errors highlighted inline (`translation-policy` owner) |
| `AdminHintCard` | Reusable hint panel (info/warning/danger) in Settings security + Translation editor |

---

## Done ✅ (19c · 2.0.45)

| Change | Detail |
|--------|--------|
| `SupportedLocalesRegistry` | `config/i18n/locales.json` — dynamic locale list |
| `LocaleScaffoldService` | Copies SK/EN templates → new locale (backend lang + FE modules) |
| Translation API | `GET/POST /api/admin/translations/locales` |
| Translation editor | Create locale UI + dynamic locale picker |
| User avatars | `avatarUrl` on User, upload/remove API, `UserAvatarPicker` UI |
| SuperAdmin guards | Only SuperAdmin assigns `SUPER_ADMIN`; 2FA secret visible to SuperAdmin |
| It.18e (partial) | `users` i18n module + `UsersManager` migrated to `useI18n()` |

---

## Done ✅ (19d · auth UX · 2.0.45)

| Change | Detail |
|--------|--------|
| `AuthShell` | Large dual-panel layout for login/register/forgot/reset |
| Login layout | Info panel **left**, form **right** |
| Register layout | Form **left** (wider), info panel **right** — CSS slide animation |
| `TotpCodeInput` | 6-digit boxes, paste support, focus chain |
| Login settings (`login` group) | Page title, description, background image URL, info bullets |
| Password policy (admin) | `security.passwordMinLength`, `passwordMaxLength`, require upper/lower/number/special |
| `SettingsBackedPasswordPolicy` | DI reads policy from settings; `/api/validation/rules/password` dynamic |
| Public API | `GET /api/settings/public` exposes `login` + password policy slice |

---

## Done ✅ (nav · 2.0.45)

| Change | Detail |
|--------|--------|
| Dashboard primary nav | `ADMIN_NAV_PRIMARY_ITEM` — top of sidebar, no category |
| Default route | `ADMIN_DEFAULT_ROUTE` (`/dashboard`) — GuestRoute, LoginModal, post-login |

---

## Hotfixes ✅ (2026-07-21 · 2.0.45)

| ID | Symptóm | Príčina | Riešenie |
|----|---------|---------|----------|
| [ISS-044](ISSUES.md#iss-044--servicesphp-parse-error-api-500) | `POST /api/*` → 500, parse error line 301 | Orphan `->constructor(...)` after `ValidationController` closure edit | Odstránený duplicitný riadok v `Http/Config/services.php` |
| [ISS-045](ISSUES.md#iss-045--localescaffoldservice-projectroot-phpstan--phpunit) | PHPUnit exit 1 (689 passed); PHPStan 7× undefined `$projectRoot` | Dynamic property assignment v PHP 8.2+ | `private string $projectRoot;` v `LocaleScaffoldService` |

---

## Remaining ⏳

- Migrate remaining admin screens to i18n (media, dashboard, navigation…)
- Dynamic FE locale loading without dev-server restart (optional `import.meta.glob` expansion)
- Settings `general.language` enum from registry
- Login background — upload via Media manager (dnes len URL pole)
- SSO callback page — zdieľať `AuthShell` (voliteľné)

---

## Related

- [ITERATION_18.md](ITERATION_18.md) — i18n foundation
- [ISSUES.md](ISSUES.md) — ISS-044, ISS-045
- [developer/RELEASE.md](developer/RELEASE.md) — C&P 2.0.45
- [ROADMAP.md](ROADMAP.md)
