# Release checklist — PaginiumCMS

> Posledná verzia: **2.0.31** · 2026-07-20  
> Tento súbor obsahuje **copy-paste** bloky pre GitHub Release. Samotný release/tag zatiaľ nevytváraj, kým nie je schválený deploy.

---

## 2.0.31 — pred release kontrola

```bash
# Backend
./vendor/bin/phpstan analyse backend --level=8
./vendor/bin/phpunit

# Frontend
cd frontend && npm run type-check && npm run lint && npm test && npm run build:prod
```

**Po deployi:**

1. **Nastavenia → Obsah** → `Článkov na stránku (blog)` = napr. `6` → `/blog` pagination
2. **Nastavenia → Admin UI** → `Otvárať náhľady… v novej karte` (default vypnuté)
3. Admin **Články** → filtruj + skopíruj URL → refresh (query params ostávajú)
4. Detail článku → **Predchádzajúci / Ďalší** bez návratu na zoznam

---

## GitHub Release — copy-paste (2.0.31)

**Title:**

```
2.0.31 — It.44 blog pagination, admin URL filters, link target setting
```

**Tag:** `v2.0.31` · **Target:** `main`

**Body (skopíruj celé):**

```markdown
## Summary

Release **2.0.31** delivers **Iteration 44** (public blog + admin list UX): settings-driven blog pagination, article prev/next navigation, admin list filters synced to URL, and a toggle for opening previews/external links in a new tab (default: same tab).

Detail: [docs/ITERATION_44.md](docs/ITERATION_44.md)

## Added

- **`content.blogItemsPerPage`** (default 6) — public blog list pagination
- **Public blog:** `/blog?page=&tag=&sort=` URL sync, sort (newest/oldest/title), prev/next article nav
- **`ui.openLinksInNewTab`** — optional new-tab for previews, “Go to website”, media, footer demo link
- **`AdminListFilterBar`** + **`useAdminListQueryParams`** — pages/articles admin lists: `?q=&status=&sort=&page=&seo=1`
- Vitest: `blogArticles.test.ts`, `linkTarget.test.ts`

## Changed

- Blog list reads `blogItemsPerPage` from settings (not hardcoded 6)
- Preview links no longer force `_blank` unless setting enabled
- PagesManager: “Clear filters” + shareable filter URLs

## Test plan

- [ ] Set blog items per page to 6 → `/blog` shows pagination with 7+ articles
- [ ] Open article → prev/next navigates without returning to list
- [ ] Admin articles: filter + copy URL → reload preserves filters
- [ ] Toggle `openLinksInNewTab` → preview opens same tab vs new tab
- [ ] `./vendor/bin/phpstan analyse backend --level=8` green
- [ ] `./vendor/bin/phpunit` green
- [ ] `cd frontend && npm run type-check && npm test && npm run build:prod` green

## Full changelog

[CHANGELOG.md#2031--2026-07-20](CHANGELOG.md#2031--2026-07-20)
```

---

## Git commit message (optional)

```
Release 2.0.31: It.44 blog pagination, admin URL filters, and link target setting.

Adds blogItemsPerPage, public blog prev/next, admin list URL sync, and ui.openLinksInNewTab toggle.
```

---

## 2.0.30 — pred release kontrola

```bash
# Backend
./vendor/bin/phpstan analyse backend --level=8
./vendor/bin/phpunit

# Frontend
cd frontend && npm run type-check && npm run lint && npm test && npm run build:prod
```

**LAN deploy (split nginx + PHP):**

```bash
# Na .26 (nginx SPA)
./scripts/deploy-frontend-lan.sh

# Na .20 (PHP) — pull + reštart kontajnera
curl -s http://192.168.10.26:8081/api/health | jq .
```

**Po deployi:**

1. Vymazať cookies pre host
2. Otestovať login → dashboard (bez druhého hesla)
3. Nový staff user → login → `/account/security` → QR → verify
4. Existujúci „rozbitý“ user: opraviť JSON alebo reset 2FA polí (ISS-031)

**Post-release hotfix (2026-07-20):** ak CI padlo na `type-check` po `f5061e6`, pull **`3fbc595`** (ISS-036). Žiadna zmena `.env` — len frontend typy + `updateUser` v 2FA settings.

---

## GitHub Release — copy-paste (2.0.30)

**Title:**

```
2.0.30 — 2FA setup fixes, login loop, dev TOTP toggle
```

**Tag:** `v2.0.30` · **Target:** `main`

**Body (skopíruj celé):**

```markdown
## Summary

Release **2.0.30** opravuje kritické 2FA UX chyby: QR kód zmizne počas setupu, nový staff user dostane TOTP bez secretu, a frontend hard-reload spôsoboval „dvojitý login“. Pridáva dev prepínač `TWO_FACTOR_REQUIRED=false`. Incidenty: [docs/ISSUES.md](docs/ISSUES.md) (ISS-030–ISS-036).

## Added

- **`TWO_FACTOR_REQUIRED`** env + `TwoFactorPolicy` — vypnutie TOTP len v dev/test (nie na produkcii)
- **`setup_pending`** v `/api/auth/2fa/status` — rozlíšenie prvého QR setupu vs login TOTP
- Admin banner „Dokončite nastavenie 2FA“ + redirect na `/account/security`
- Auth custom events namiesto `window.location` pri 401

## Fixed

| ID | Issue |
|----|-------|
| ISS-030 | QR disappears after 2FA enable → kicked to login TOTP |
| ISS-031 | Staff user created with `twoFactorEnabled` but no secret |
| ISS-032 | `twoFactorVerifiedAt` not saved in user JSON |
| ISS-033 | 401 → hard redirect caused double password login |
| ISS-029 | Login loop follow-up for 2FA / new users |
| ISS-035 | PHPStan ClientIpResolver dead `??` (CI hotfix) |
| ISS-036 | FE type-check: 2FA `setup_pending` unwrap + `updateUser` (post-2.0.30 CI hotfix `3fbc595`) |

## Ops — dev `.env` (optional)

```env
APP_ENV=development
TWO_FACTOR_REQUIRED=false
SESSION_LIFETIME=28800
SESSION_STRICT=false
TRUSTED_PROXIES=127.0.0.1,::1,192.168.10.26
```

Restart PHP. Clear browser cookies.

**Broken account recovery:** in `storage/app/users/<id>.json` set `twoFactorEnabled: false`, `twoFactorSecret: null`, `twoFactorVerifiedAt: null`, then re-login and complete setup at `/account/security`.

## Test plan

- [ ] Login (no 2FA) → dashboard without second password prompt
- [ ] Create staff user → login → `/account/security` → QR stays visible → verify works
- [ ] Logout → login with 2FA → TOTP step only (not QR setup again)
- [ ] `TWO_FACTOR_REQUIRED=false` in dev skips TOTP middleware
- [ ] `./vendor/bin/phpstan analyse backend --level=8` green
- [ ] `./vendor/bin/phpunit` green
- [ ] `npm run build:prod` green

## Full changelog

[CHANGELOG.md#2030--2026-07-19](CHANGELOG.md#2030--2026-07-19)
```

---

## Git commit message (optional, keď schváliš commit)

```
Release 2.0.30: fix 2FA setup/login loop and add dev TOTP toggle.

Fixes ISS-030–ISS-035 (QR setup vs login TOTP, staff user without secret,
twoFactorVerifiedAt persistence, FE 401 hard redirect). Adds TWO_FACTOR_REQUIRED env.
```

---

## Hotfix commit message (2.0.30 post-release, ISS-036)

```
fix(frontend): resolve 2FA TypeScript CI errors on main.

Map setup_pending in auth API layer, use updateUser in TwoFactorSettings, and align test fixtures.
```

Commit: **`3fbc595`** · pushed to `main` 2026-07-20.

---

## Predchádzajúce release

- **2.0.29** — Session hardening, cache admin — [CHANGELOG.md#2029--2026-07-19](../CHANGELOG.md#2029--2026-07-19)
- **2.0.28** — It.12 Blueprint + It.13 Demo sandbox v2 — [CHANGELOG.md#2028--2026-07-19](../CHANGELOG.md#2028--2026-07-19)
