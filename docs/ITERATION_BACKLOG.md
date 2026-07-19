# PaginiumCMS – Backlog iterácií (27+)

> Plánované moduly a rozšírenia po **Iterácii 28 (2.0.16)**.  
> Legenda: ⏳ plánované · 🟡 stredná priorita · 🔵 nižšia · 🔴 kritická

**Aktuálne hotové:** It.1–24 ✅ · It.26–28 ✅ · It.25 ⏳ (setup wizard — odložené)

**Ďalšia iterácia:** [It.41 – Email OTP schvaľovanie](ITERATION_BACKLOG.md#iterácia-41--email-otp-schvaľovanie-)

**Incidenty a opravy:** [ISSUES.md](ISSUES.md)

---

## Prehľad

| It. | Verzia | Názov | Priorita | Poznámka |
|-----|--------|-------|----------|----------|
| 25 | TBD | [Setup wizard](ITERATION_25.md) | 🟡 | Odložené — téma cez Settings |
| 26 | 2.0.14 | [Media preview + binárny hotfix](ITERATION_26.md) | ✅ | Lightbox + strict formats |
| **27** | **2.0.15** | **[Admin view modes + SEO panel](ITERATION_27.md)** | **✅** | List / list+preview / grid + SEO UX + metadata modal |
| **28** | **2.0.16** | **[Bulk actions platform](ITERATION_28.md)** | **✅** | Shared bulk bar + batch APIs |
| **29** | **2.0.18** | **[Cron planner + Job Queue](ITERATION_29.md)** | **✅** | Registry + CLI + `/scheduler` |
| **41** | TBD | **Email OTP schvaľovanie** | **🟡 ďalšia** | Registrácia (USER), komentáre/príspevky (EDITOR) – zap/vyp v admin |
| **42** | TBD | **Admin počty položiek** | **🟡** | Badge počty: články, stránky, media, komentáre, správy, zálohy, kôš, users |
| **43** | TBD | **Pokročilé vyhľadávanie (FE + BE)** | **🟡** | Command-palette / quick jump v admin aj verejnom webe |
| **44** | TBD | **Filtre a zoradenia (admin + FE)** | **🟡** | Zoznamy: status, typ, dátum, abeceda; zdieľané query parametre |
| **45** | TBD | **[Redis – voliteľná infra](ITERATION_45.md)** | **🔵** | Absorbované do **It.49** |
| **46** | TBD | **[Server metrics agent](ITERATION_46.md)** | **🟡** | CPU/RAM/disk/Docker → It.7 report + dashboard |
| **47** | TBD | **[Notification connector auth](ITERATION_47.md)** | **🟡** | ntfy token/Basic + test per konektor |
| **48** | TBD | **[PHP templates + static/dynamic web](ITERATION_48.md)** | **🟡** | Front matter šablóny, JSON/INI meta, static HTML |
| **49** | TBD | **[Unified cache layer](ITERATION_49.md)** | **🟡** | File + Redis prepínač podľa hostingu (rýchlosť/bezpečnosť) |
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

## Iterácia 29 – Cron planner + Job Queue ✅

Plánovač pre **plánované spúšťanie akcií** mimo HTTP requestu. **Full spec:** [ITERATION_29.md](ITERATION_29.md)

- Flat-file job registry + run history + optional queue
- CLI `scheduler:run` + `worker:process`
- Admin `/scheduler` + `GET/POST /api/admin/jobs`

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

## Iterácia 43 – Pokročilé vyhľadávanie (FE + BE) ⏳

Rýchle skoky v kontexte — nad rámec základného `GET /api/search?q=`.

| Vrstva | Popis |
|--------|--------|
| **Backend** | Unified search endpoint (obsah, media, users, nastavenia, routes); skórovanie, limit per typ, published filter na verejnom API |
| **Admin FE** | Command palette (`Ctrl+K`) — prejsť na stránku/článok/medium, otvoriť editor, skok do modulu |
| **Verejný FE** | Instant search s náhľadom (titulok, excerpt, typ) + deep link na výsledok |

**API (návrh):** `GET /api/search?q=&scope=admin|public&types=page,article,media`  
**UX:** klávesové skratky, história posledných skokov, zvýraznenie zhody.

---

## Iterácia 44 – Filtre a zoradenia (admin + FE) ⏳

Konzistentné filtrovanie a sort naprieč zoznamami.

| Modul | Filtre (príklady) | Zoradenie |
|-------|-------------------|-----------|
| Články / stránky | status, autor, dátum, SEO issue | updated, title, published |
| Media | typ, folder, dátum | name, size, created |
| Komentáre | schválené / pending | date, author |
| Admin zoznamy | full-text + facet | ASC/DESC per stĺpec |
| Verejný blog | kategória / tag (ak existuje) | newest, oldest, title |

**Backend:** rozšírenie index API — `filter[status]=`, `sort=-updated_at` (zdieľaný kontrakt s It.19).  
**Frontend:** reusable `FilterBar` + URL sync (`?sort=&filter=`) pre admin aj verejný web.

---

## Iterácia 45 – Redis (voliteľná infra vrstva) ⏳

Flat-file ostáva zdroj pravdy; Redis = zdieľaná cache / queue / rate-limit pre viac workerov.

**Full spec:** [ITERATION_45.md](ITERATION_45.md) · **Implementácia:** [ITERATION_49.md](ITERATION_49.md) (unified cache + admin prepínač)

- `RedisDriver` v `ChainedDriver` (Memory → Redis → File)
- Voliteľný `JobQueueStore` backend, Settings `redis.*`
- **Kedy:** 2+ PHP repliky, contention na flat-file queue, nie pre single-node MVP

---

## Iterácia 47 – Notification connector auth ⏳

Privátne ntfy topicy a self-hosted inštancie vyžadujú **Bearer token** alebo **Basic auth**. Dnes `NtfyAdapter` posiela bez credentials.

**Full spec:** [ITERATION_47.md](ITERATION_47.md)

- Settings: `ntfyAuthMode`, `ntfyAccessToken`, `ntfyUsername`/`ntfyPassword`
- `POST /api/admin/notifications/test-connector` per channel
- Rovnaký pattern pre webhook custom auth header ak treba

---

## Iterácia 48 – PHP frontmatter templates & static/dynamic web ⏳

Vlastné šablóny cez **PHP + front matter** (nie Twig), metadata **YAML / JSON / INI**, generovanie **statického HTML**, prepínač **dynamic vs static** verejného webu.

**Full spec:** [ITERATION_48.md](ITERATION_48.md)

- `PhpTemplateRenderer`, `StaticSiteGenerator`, rebuild jobs (It.29)
- Admin: render mode, template editor, stale static badge
- Oba režimy: SPA (dynamic) + `storage/static/` (static)

---

## Iterácia 49 – Unified cache layer ⏳

**File + Redis** s prepínačom `auto|file|redis|memory` podľa hostingu a system probe. Zachováva rýchlosť, bezpečnosť, spolahlivosť (fallback keď Redis down).

**Full spec:** [ITERATION_49.md](ITERATION_49.md) · zahŕňa rozsah It.45

- `CacheDriverFactory`, `CacheCapabilityProbe`, Settings `cache.*`
- Admin UI + HealthChecker rozšírenie
- Queue / rate-limit / lock voliteľne na Redis

---

## Iterácia 46 – Server metrics agent ⏳

Doplnenie It.7 reportov o **host metriky** (uptime, CPU, RAM, disk, Docker).

**Full spec:** [ITERATION_46.md](ITERATION_46.md)

- Cron agent → `data/metrics/host-latest.json`
- Ingest API + sekcia v HTML monitoring reporte
- Dashboard widget (naviazané na It.34)

---

## Odporúčané poradie implementácie

```
It.28/2.0.16 ✅ → It.29/2.0.18 ✅ → It.41 (email OTP) ← ďalšia
                → It.42 (počty v admin)
                → It.43 (advanced search / quick jump) → It.44 (filtre + sort)
                → It.47 (notification auth — ntfy token) — paralelne s It.41 ak treba ntfy OTP
                → It.36 (pagination) → It.38 (feature flags)
                → It.39 (komentáre) → It.37 (inline FE edit)
                → It.33 (analytics) → It.34 (system overview) → It.46 (host metrics agent)
                → It.35 (inspector) → It.40 (section FileManager)
                → It.48 (static templates + dynamic/static web toggle)
                → It.30 (contextual) → It.31 (live preview)
                → It.32 (performance) → It.49 (unified cache: file + Redis)
```

---

## Súvisiace dokumenty

- [ROADMAP.md](ROADMAP.md) — hlavná mapa It.1–24
- [ISSUES.md](ISSUES.md) — incidenty a opravy (2026-07-18)
- [ITERATION_27.md](ITERATION_27.md) — admin view modes
- [ITERATION_24.md](ITERATION_24.md) — DAM v1 + stock knižnica
- [ITERATION_26.md](ITERATION_26.md) — media lightbox + 2.0.14 hotfix
- [ITERATION_45.md](ITERATION_45.md) — Redis driver (detail)
- [ITERATION_46.md](ITERATION_46.md) — server metrics agent
- [ITERATION_47.md](ITERATION_47.md) — notification connector auth
- [ITERATION_48.md](ITERATION_48.md) — PHP templates + static/dynamic web
- [ITERATION_49.md](ITERATION_49.md) — unified cache layer
