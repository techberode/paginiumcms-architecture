# Iteration 61 – Newsletter vo footeri + admin prehľad odberateľov

**Status:** ✅ Done  
**Priorita:** 🟡 Stredná  
**Nadväzuje na:** [It.52 Kontakt / dashboard](ITERATION_52.md) ✅ · maintenance newsletter (2.0.51) · [ISS-097](ISSUES.md#iss-097--newsletter-odberatelia-bez-admin-prehľadu--medzera--it61)

## Cieľ

1. V **pätičke verejného webu** rýchle prihlásenie na odber (email).
2. V **administrácii** prehľad všetkých odberateľov — vrátane tých z **Coming Soon / Údržba** (dnes len flat-file bez UI).

## Súčasný stav (medzera ISS-097)

| Vrstva | Stav |
|--------|------|
| **Ukladanie** | ✅ `POST /api/maintenance/newsletter` → `data/newsletter/subscribers.json` |
| **Backend repo** | ✅ `NewsletterRepository::findAll()` + admin HTTP API |
| **Admin UI** | ✅ `/newsletter` — tabuľka + export CSV |
| **Unsubscribe** | ✅ Phase 3 — HMAC token + `/newsletter/unsubscribe` |

**Dočasný workaround (server):**

```bash
jq . /var/www/paginiumcms.com/backend/storage/app/content/data/newsletter/subscribers.json
```

## Rozsah It.61

| Oblasť | Popis |
|--------|--------|
| **Footer FE** | Kompaktný formulár (email + tlačidlo); SK/EN i18n; GDPR checkbox (voliteľné) |
| **API verejné** | `POST /api/newsletter/subscribe` (rate limit, honeypot) |
| **API admin** | `GET /api/admin/newsletter/subscribers` — zoznam + export CSV |
| **Admin UI** | Modul alebo panel v Nastaveniach / Schránke — tabuľka: email, dátum, zdroj (`footer`, `coming_soon`, `under_maintenance`) |
| **Settings** | `newsletterFooterEnabled`, texty v Nastavenia → Stránka / Kontakt |
| **Odhlásenie** | v2 — mimo MVP ak treba rýchle dodanie zoznamu |

## Odlíšenie od maintenance newsletter (2.0.51)

| | Maintenance (`POST /api/maintenance/newsletter`) | Footer newsletter (It.61) |
|--|--|--|
| Kontext | Coming Soon / Údržba stránky | Bežný footer na celom webe |
| Zapnutie | Režim údržby → `newsletterEnabled` | Samostatný prepínač `newsletterFooterEnabled` |
| Úložisko | **Rovnaký** `subscribers.json` | Rovnaký súbor, iný `source` |
| Admin prehľad | **Chýba dnes** | **Súčasť It.61 pre všetkých odberateľov** |

## Technicky

- Reuse `NewsletterRepository` — pridať `unsubscribe()` a voliteľne `countBySource()` pre dashboard KPI.
- Spam: `RateLimitMiddleware` + honeypot.
- RBAC: zoznam odberateľov **ADMIN+** (GDPR — osobné údaje).

## Acceptance criteria

- [x] Footer formulár skrytý keď `footerEnabled=false` (settings group `newsletter`)
- [x] Úspešné prihlásenie → 201 + deduplikácia emailu
- [x] **Admin zoznam odberateľov** — read-only tabuľka + export CSV
- [x] Existujúci maintenance odber viditeľný v tom istom zozname (`source` stĺpec)
- [x] PHPUnit: subscribe API + admin list API
- [x] Vitest: FooterNewsletter + SubscribersPanel

## Smoke test

1. Coming Soon stránka → prihlás email → over v admin **Newsletter → Odberatelia**.
2. Footer (po implementácii) → rovnaký email → jeden záznam, `created: false` pri duplicite.
3. Export CSV → stiahne súbor s emailmi.

## Súvisiace

- [ISSUES.md § ISS-097](ISSUES.md#iss-097--newsletter-odberatelia-bez-admin-prehľadu--medzera--it61)
- [user/ADMIN_GUIDE.md](user/ADMIN_GUIDE.md) — sekcia Režim údržby a newsletter
- [ITERATION_52.md](ITERATION_52.md) — kontakt a company settings

---

## Newsletter v2 — Phase 1 (2026-07-28)

**Status:** ✅ Done (collect + preferences; **no email sending yet**)

| Area | Delivered |
|------|-----------|
| **Model** | `preferences[]`, `status`, `consentAt` on `subscribers.json` |
| **Settings** | `fromEmail`, `fromName`, `replyTo`, `enabledPreferences`, `requireConsentCheckbox` |
| **Security** | Dedicated rate limit, maintenance honeypot, generic success message, preference allow-list |
| **FE** | Footer + maintenance checkboxes; admin preferences column |
| **Deferred (Phase 2+)** | Send campaigns, unsubscribe link, double opt-in |

### Acceptance (Phase 1)

- [x] Footer/maintenance show only admin-enabled preference types
- [x] At least one preference required; invalid keys rejected
- [x] Re-subscribe merges preferences (union)
- [x] Optional consent checkbox enforced when enabled in settings
- [x] PHPUnit + iteration gate green

---

## Newsletter v2 — Phase 2 (2026-07-28)

**Status:** ✅ Done (email sending; **no unsubscribe / double opt-in yet**)

| Area | Delivered |
|------|-----------|
| **Mail service** | `NewsletterMailService` — weekly digest + new-article notifications via `NotificationService` (`email` adapter) |
| **Scheduler** | System job `newsletter-weekly-digest` (`newsletter.weekly_digest`, cron `0 9 * * 1`) |
| **Hooks** | `content.after_status_change` + `content.after_scheduled_publish` → instant new-article mail |
| **Settings** | `sendEnabled`, `weeklyDigestEnabled`, `newArticleEnabled`, `instantArticleCooldownHours`, `sendBatchLimitPerRun` |
| **State** | `data/newsletter/send-state.json` — last weekly digest + per-email article cooldown |
| **Admin API** | `GET /api/admin/newsletter/send/status`; `POST …/send/weekly-digest` + `…/send/test` (**SUPER_ADMIN**) |
| **Admin UI** | Send status panel + manual digest / test buttons on `/newsletter` |
| **Deferred (Phase 3+)** | Unsubscribe link, double opt-in, CMS release campaigns |

### Acceptance (Phase 2)

- [x] Master `sendEnabled` gates all outbound newsletter mail
- [x] Weekly digest only to active subscribers with `weekly_digest` preference
- [x] New article mail on publish (manual + scheduled) with per-subscriber cooldown
- [x] Batch limit per run from settings
- [x] PHPUnit + iteration gate green

---

## Newsletter v2 — Phase 3 (2026-07-28)

**Status:** ✅ Done (double opt-in + unsubscribe links)

| Area | Delivered |
|------|-----------|
| **Double opt-in** | Optional `requireDoubleOptIn`; subscriber `pending` until confirm link clicked |
| **Confirm API** | `GET /api/newsletter/confirm?token=…` + public page `/newsletter/confirm` |
| **Unsubscribe** | Deterministic HMAC token (APP_KEY + subscriber id); `GET /api/newsletter/unsubscribe?token=…` |
| **Public FE** | Confirm + unsubscribe pages; footer/maintenance show “check your email” when pending |
| **Mail footer** | Unsubscribe link appended to weekly digest + new-article emails |
| **Settings** | `requireDoubleOptIn`, `confirmTokenTtlHours` |
| **Admin** | Status column (`active` / `pending` / `unsubscribed`) |
| **Deferred (Phase 4+)** | CMS release campaigns, preference-scoped unsubscribe |

### Acceptance (Phase 3)

- [x] Pending subscribers excluded from mail sends until confirmed
- [x] Confirm token stored as SHA-256 hash with expiry
- [x] Unsubscribe link works from outbound mail (one-click GET)
- [x] Rate limit on token endpoints (IP)
- [x] PHPUnit + iteration gate green

---

## Newsletter v2 — Phase 4 (2026-07-28)

**Status:** ✅ Done (preference management + CMS release campaigns)

| Area | Delivered |
|------|-----------|
| **Settings UX** | Newsletter group moved to **Settings → System**; editable panel on `/newsletter` |
| **Manage preferences** | `GET/POST /api/newsletter/manage?token=` + public page `/newsletter/manage` |
| **Preference-scoped unsubscribe** | `GET /api/newsletter/unsubscribe?token=&preference=` removes one type; full unsubscribe when none left |
| **Mail footer** | Manage preferences + unsubscribe-all links in outbound emails |
| **CMS release campaigns** | Setting `cmsReleaseEnabled`; `POST /api/admin/newsletter/send/cms-release` (SUPER_ADMIN) |
| **Maintenance API** | `/api/newsletter/*` allowed during maintenance (confirm/manage/unsubscribe) |

### Acceptance (Phase 4)

- [x] Admin can toggle footer/send/digest/release from `/newsletter` without hunting Site settings
- [x] Subscriber can update preferences via token link from email
- [x] Partial unsubscribe removes one preference key only
- [x] CMS release mail sends to active `cms_release` subscribers when enabled
- [x] PHPUnit + iteration gate green

---

## Public UX — footer modal + cookie consent (2026-07-28)

**Status:** ✅ Done

| Area | Delivered |
|------|-----------|
| **Footer newsletter** | Highlighted CTA box (email + button); full signup in **modal** (`NewsletterSubscribeModal`) |
| **Cookie banner** | Settings group `privacy` — banner on first visit; accept / reject optional / customize |
| **Functional storage** | Theme preference (`paginium-public-theme`) written only after functional cookie consent |
| **Footer link** | „Nastavenia cookies“ reopens consent modal after decision |
| **Settings** | Nastavenia → Stránka → **Súkromie a cookies** |

### Acceptance (UX)

- [x] Footer stays compact; preferences + consent only in modal
- [x] Cookie banner configurable (text, policy URL, reject button)
- [x] SK/EN i18n for public cookies + footer CTA
- [x] PHPUnit + iteration gate green

---

## BE ↔ FE wiring audit (2026-07-28)

**Status:** ✅ Done (`v2.1.0-beta.16`)

| Check | Result |
|-------|--------|
| Newsletter API routes ↔ `frontend/src/api/newsletter.ts` | ✅ All 8 endpoints wired |
| Public pages `/newsletter/confirm`, `/newsletter/unsubscribe` | ✅ `App.tsx` + `MaintenanceGate` bypass |
| `GET /api/settings/public` newsletter slice | ✅ `requireDoubleOptIn` exposed; FE `PublicSettings` aligned |
| Settings i18n (EN/SK) for send/opt-in fields | ✅ Full newsletter group labels |
| Admin sidebar count | ✅ `newsletter` in `/api/admin/counts` + `AdminSidebar` |
| CSRF exempt `/api/newsletter` | ✅ GET confirm/unsubscribe + POST subscribe |
| Test hygiene (Vitest stderr) | ✅ `editorToolbar` act mock; `CompanyInfoPanel` SSR iframe test |
| Vite hello-widget chunk | ✅ Removed static+dynamic import conflict |

### Test log fixes (non-blocking warnings addressed)

- React `act(...)` in `editorToolbar.test.tsx` — mock `loadAllowedEditorComponents` + `waitFor`
- happy-dom iframe fetch noise in `CompanyInfoPanel.test.tsx` — `renderToStaticMarkup` for map test
- ESLint 4 → 0: `SystemUpdateView` deps, `PublicSiteContext`, constants split to dedicated files
- Deferred: npm audit (eslint 10 / react-router RSC), bundle >500 kB split
