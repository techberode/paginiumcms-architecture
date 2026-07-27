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
| **Unsubscribe** | ❌ neimplementované |

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
