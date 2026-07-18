# PaginiumCMS – Backlog iterácií (27+)

> Plánované moduly a rozšírenia po **Iterácii 28 (2.0.16)**.  
> Legenda: ⏳ plánované · 🟡 stredná priorita · 🔵 nižšia · 🔴 kritická

**Aktuálne hotové:** It.1–24 ✅ · It.26–28 ✅ · It.25 ⏳ (setup wizard — odložené)

**Ďalšia iterácia:** [It.29 – Cron planner + Job Queue](ITERATION_BACKLOG.md#iterácia-29--cron-planner--job-queue--background-worker-)

**Incidenty a opravy:** [ISSUES.md](ISSUES.md)

---

## Prehľad

| It. | Verzia | Názov | Priorita | Poznámka |
|-----|--------|-------|----------|----------|
| 25 | TBD | [Setup wizard](ITERATION_25.md) | 🟡 | Odložené — téma cez Settings |
| 26 | 2.0.14 | [Media preview + binárny hotfix](ITERATION_26.md) | ✅ | Lightbox + strict formats |
| **27** | **2.0.15** | **[Admin view modes + SEO panel](ITERATION_27.md)** | **✅** | List / list+preview / grid + SEO UX + metadata modal |
| **28** | **2.0.16** | **[Bulk actions platform](ITERATION_28.md)** | **✅** | Shared bulk bar + batch APIs |
| **29** | TBD | **Cron planner + Job Queue** | **🟡 ďalšia** | Plánované akcie: obsah, trash, backups, notifikácie, users, komentáre |
| **41** | TBD | **Email OTP schvaľovanie** | **🟡** | Registrácia (USER), komentáre/príspevky (EDITOR) – zap/vyp v admin |
| **42** | TBD | **Admin počty položiek** | **🟡** | Badge počty: články, stránky, media, komentáre, správy, zálohy, kôš, users |
| **30** | TBD | **Contextual Actions** | 🟡 | Akcie podľa kontextu (content, media, user) |
| **31** | TBD | **Live Preview** | 🟡 | Náhľad stránky/článku pred publikovaním |
| **32** | TBD | **React chunking + PHP OPcache** | 🔵 | Výkon FE/BE |
| **33** | TBD | **Analytics: GeoIP, zariadenie, referrer** | 🟡 | Rozšírenie `Analytics/*` |
| **34** | TBD | **System overview (PHP + FE engine)** | 🟡 | Dashboard verzií stacku |
| **35** | TBD | **Flat-File Inspector** | 🟡 | Prehliadač súborov na disku |
| **36** | TBD | **Pagination (admin + verejný obsah)** | 🟡 | Stránkovanie zoznamov |
| **37** | TBD | **Inline edit na FE po prihlásení** | 🟡 | Tlačidlá „Upraviť“ na verejnom webe |
| **38** | TBD | **Feature flags (FE moduly on/off)** | 🟡 | Settings skupina `features.*` |
| **39** | TBD | **Komentáre: schvaľovanie rolou + hostia** | 🟡 | Role-based approve, guest toggle |
| **40** | TBD | **Section FileManager (scoped DAM)** | 🟡 | Priečinky + práva per sekcia |

---

## Iterácia 27 – Admin view modes + SEO panel ✅

Display modes in admin SPA and SEO metadata workflow. **Full spec (English):** [ITERATION_27.md](ITERATION_27.md)

---

## Iterácia 28 – Bulk actions (platform) ✅

Hromadné operácie nad viacerými entitami. **Full spec:** [ITERATION_28.md](ITERATION_28.md)

- Unified `BulkActionBar` + `useBulkSelection`
- Content / trash / comments batch endpoints
- Media bulk delete refactored to shared UI

---

## Iterácia 29 – Cron planner + Job Queue / Background Worker ⏳

Plánovač (Cron) pre **plánované spúšťanie akcií** mimo HTTP requestu:

| Doména | Príklady úloh |
|--------|----------------|
| Obsah | auto-publish draft, archivácia starých článkov |
| Kôš | permanent delete po retention |
| Zálohy | plánovaný export + rotácia |
| Notifikácie | digest e-mail, queue flush |
| Používatelia | deaktivácia neaktívnych, cleanup tokenov |
| Komentáre | auto-moderácia, spam cleanup |

**Backend:**
- Flat-file alebo Redis fronta úloh + `CronSchedule` v Settings
- Worker CLI (`php bin/worker.php`) + systémový cron / Docker `ofelia`
- Admin UI: CRUD plánov, posledný beh, log
- Status API: `GET /api/admin/jobs`, `GET /api/admin/jobs/{id}`

**Frontend:**
- Admin sekcia „Plánovač“ – zap/vyp jednotlivých jobov, CRON výraz, náhľad ďalšieho behu

---

## Iterácia 41 – Email OTP schvaľovanie ⏳

Automatizované schvaľovanie jednorazovým kódom na e-mail (všetko **zapínateľné v administrácii**):

| Tok | Rola | Popis |
|-----|------|--------|
| Registrácia | USER (striktne) | Nový účet až po OTP z mailu |
| Komentár | EDITOR | Schválenie komentára cez kontrolný kód mailom |
| Nový príspevok | EDITOR | Publikácia až po OTP schválení editorom |

**Backend:**
- Settings skupina `workflows.*` – `enabled`, TTL kódu, šablóny mailov
- Flat-file register `data/otp-pending.json` alebo per-entity token
- Napojenie na `NotificationService` / SMTP z It.6

**Frontend:**
- Admin prepínače v Settings
- Verejný register flow + editor modals pre OTP

---

## Iterácia 42 – Admin počty položiek ⏳

Zobrazenie **počtu položiek** v administrácii (sidebar, nadpisy zoznamov, dashboard KPI):

| Modul | Zdroj |
|-------|--------|
| Články / stránky | content index / paginated `total` |
| Media | `MediaRepository::count()` |
| Komentáre | comments registry |
| Správy | messages inbox |
| Zálohy | `BackupManager` |
| Kôš | `TrashService` |
| Používatelia | `UserRepository::findAll()` |

**Backend:** `GET /api/admin/counts` – jeden agregovaný endpoint  
**Frontend:** Settings `ui.showListCounts` – globálne zap/vyp badge  
**Naviazané:** oprava `DashboardView` user count (ISS-007) ✅

---

## Iterácia 30 – Contextual Actions ⏳

Kontextové menu / toolbar podľa miesta v admin SPA (stránka obsahu, media grid, audit log).

- `ContextActionRegistry` (FE) + permission map (BE)

---

## Iterácia 31 – Live Preview ⏳

Náhľad publikovanej stránky bez opustenia editora.

- iframe / draft URL token
- Prepojenie s locking (It.1) a drafts (It.2)

---

## Iterácia 32 – React chunking + PHP OPcache ⏳

**Frontend:** route-based code splitting, lazy admin routes  
**Backend:** OPcache preload config, dokumentácia deploy

---

## Iterácia 33 – Analytics enrichment ⏳

Rozšírenie existujúceho `Core/Analytics/`:

| Pole | Zdroj |
|------|--------|
| Geo (krajina/mesto) | GeoIP služba / MaxMind lite |
| Referrer / UTM | HTTP hlavičky |
| Zariadenie | existujúci `DeviceDetector` |
| IP (hash/anonymized) | GDPR-aware nastavenie |

---

## Iterácia 34 – System overview ⏳

Admin panel: PHP verzia, OPcache stav, Vite/React build info, PaginiumCMS verzia, disk usage.

- `GET /api/admin/system/overview`

---

## Iterácia 35 – Flat-File Inspector ⏳

Prehliadač `storage/app/content/` s read-only režimom a DEV unlock pre zápis.

- Strom súborov, náhľad JSON/MD, validácia schémy

---

## Iterácia 36 – Pagination ⏳

- Admin zoznamy (content, users, audit, media) — server-side `page` + `limit`
- Verejný blog/pages — už čiastočne It.19; dorobiť FE + API kontrakt

---

## Iterácia 37 – Frontend inline editing ⏳

Po prihlásení editor/admin na verejnom webe:

- Floating „Edit this page“ / „New article“
- Presmerovanie do admin editora s lockom

---

## Iterácia 38 – Feature flags (FE modules) ⏳

Všetky voliteľné FE funkcie zapínateľné v administrácii.

- Settings skupina `features` (komentáre, analytics widget, WYSIWYG, …)
- `PublicSiteContext` + admin guard

---

## Iterácia 39 – Komentáre: moderácia + hostia ⏳

- Schválenie komentára rolou (EDITOR vs ADMIN)
- `comments.allowGuestComments` — už v schéme, dorobiť FE + enforcement
- Front-end formulár pre hostí s CAPTCHA (voliteľne)

---

## Iterácia 40 – Section FileManager ⏳

Jednotný alebo per-sekcia správca súborov (blog/media, pages/assets, …).

- Scoped roots + ACL (`media:upload` per path prefix)
- Reuse `MediaRepository` + folder API z It.24

---

## Odporúčané poradie implementácie

```
It.28/2.0.16 ✅ → It.29 (cron + job queue) ← ďalšia
                → It.42 (počty v admin) → It.41 (email OTP workflows)
                → It.36 (pagination) → It.38 (feature flags)
                → It.39 (komentáre) → It.37 (inline FE edit)
                → It.33 (analytics) → It.34 (system overview)
                → It.35 (inspector) → It.40 (section FileManager)
                → It.30 (contextual) → It.31 (live preview)
                → It.32 (performance)
```

---

## Súvisiace dokumenty

- [ROADMAP.md](ROADMAP.md) — hlavná mapa It.1–24
- [ISSUES.md](ISSUES.md) — incidenty a opravy (2026-07-18)
- [ITERATION_27.md](ITERATION_27.md) — admin view modes
- [ITERATION_24.md](ITERATION_24.md) — DAM v1 + stock knižnica
- [ITERATION_26.md](ITERATION_26.md) — media lightbox + 2.0.14 hotfix
