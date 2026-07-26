# PaginiumCMS – Backlog iterácií (27+)

> Plánované moduly a rozšírenia po **Iterácii 28 (2.0.16)**.  
> Legenda: ⏳ plánované · 🟡 stredná priorita · 🔵 nižšia · 🔴 kritická

**Aktuálne hotové:** It.1–24 ✅ · It.26–28 ✅ · It.25 ⏳ (setup wizard — odložené)

**Ďalšia iterácia:** [It.60](ITERATION_60.md) (modulárny editor) · backlog It.59–61  
**Posledná shipped:** [It.56 Rich navigation](ITERATION_56.md) — **`v2.1.0-beta.5`** · [It.57](ITERATION_57.md) — **`v2.1.0-beta.4`** · deps/security — **`v2.1.0-beta.7`**

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
| **41** | TBD | **Email OTP schvaľovanie** | **✅** | Registrácia, komentáre, publikácia – zap/vyp v admin |
| **42** | TBD | **Admin počty položiek** | **✅** | Sidebar badges cez `/api/admin/counts` |
| **43** | **Unreleased** | **Pokročilé vyhľadávanie (FE + BE)** | **✅** | Command palette `Ctrl+K`, scoped `/api/search` — [ITERATION_43.md](ITERATION_43.md) |
| **44** | **2.0.37** | **Filtre, blog pagination, prev/next** | **✅** | [ITERATION_44.md](ITERATION_44.md) — complete (FE + BE) |
| **45** | TBD | **[Redis – voliteľná infra](ITERATION_45.md)** | **🔵** | Absorbované do **It.49** |
| **51** | **2.0.32** | **[Live preview + tagy + blog štítky](ITERATION_51.md)** | **✅** | SitePreviewModal, reading time, date badges |
| **52** | **2.0.36** | **[Dashboard v2 + kontakt](ITERATION_52.md)** | **✅** | It.52a–c complete |
| **53** | **2.0.39** | **[Smooth SPA reload](ITERATION_53.md)** | **✅** | React Query cache, scroll restore, skeletons |
| **54** | **2.0.42** | **[Modular MD + WYSIWYG profiles](ITERATION_54.md)** | **✅** | It.15 · Tiptap |
| **55** | **2.0.43** | **[Tiptap JSON + media upload](ITERATION_55.md)** | **✅** | WYSIWYG JSON + BE render + upload |
| **56** | **`v2.1.0-beta.5`** | **[Rich navigation items](ITERATION_56.md)** | **✅** | Popis, ikona, hover preview |
| **57** | **`v2.1.0-beta.4`** | **[Auto tags & description](ITERATION_57.md)** | **✅** | suggest-meta API |
| **58** | TBD | **[Page layout builder + color schemes](ITERATION_58.md)** | **🟡** | ⛔ po It.15 · [alternatívy](ITERATION_58_ALTERNATIVES.md) · layout + 5 presetov |
| **59** | TBD | **[Odložená publikácia — plánovač v editore](ITERATION_59.md)** | **🟡** | Kalendár v editore + admin filtre; job `content.scheduled_publish` (It.29) |
| **60** | TBD | **[Vlastné komponenty editora](ITERATION_60.md)** | **🟡** | Nastavenia → Stránka → Editor; pluginy; nie rola USER |
| **61** | TBD | **[Newsletter vo footeri](ITERATION_61.md)** | **🟡** | Rýchly odber + admin zap/vyp; ≠ maintenance newsletter |
| **15** | **2.0.38** | **[External plugins](ITERATION_15.md)** | **✅** | Import ZIP, registry, hooks, routes, admin UI |
| **47** | TBD | **[Notification connector auth](ITERATION_47.md)** | **✅** | ntfy Bearer/Basic + test-connector |
| **48** | TBD | **[PHP templates + static/dynamic web](ITERATION_48.md)** | **🟡** | Front matter šablóny, JSON/INI meta, static HTML |
| **49** | TBD | **[Unified cache layer](ITERATION_49.md)** | **🟡** | File + Redis prepínač podľa hostingu (rýchlosť/bezpečnosť) |
| **50** | TBD | **[In-App Micro Firewall (WAF)](ITERATION_50.md)** | **🔴** | Detekcia, jail, permanent ban, admin dashboard |
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

## Iterácia 41 – Email OTP schvaľovanie ✅

Automatizované schvaľovanie jednorazovým kódom na e-mail (všetko **zapínateľné v administrácii**):

| Tok | Rola | Popis | Stav |
|-----|------|--------|------|
| Registrácia | USER | Nový účet až po OTP z mailu | ✅ |
| Komentár | EDITOR+ | Schválenie komentára cez kontrolný kód mailom | ✅ |
| Nový príspevok | EDITOR+ | Publikácia až po OTP schválení editorom | ✅ |

**Backend:** `workflows.*`, `OtpChallengeStore`, `OtpWorkflowService`, auth + admin workflow routes — viz [ITERATION_41.md](ITERATION_41.md)

**Frontend:** RegisterModal, `OtpConfirmModal`, CommentsManager, MarkdownEditor publish flow

**Poznámka:** hromadné schválenie komentárov OTP nevyžaduje (single-action only).

---

## Iterácia 42 – Admin počty položiek ✅

Zobrazenie **počtu položiek** v administrácii (sidebar badge):

| Modul | Pole v `/api/admin/counts` |
|-------|----------------------------|
| Články / stránky | `articles`, `pages` |
| Media | `media` |
| Komentáre | `comments` (admin) |
| Správy | `messages` (admin) |
| Zálohy | `backups` |
| Kôš | `trash` (admin) |
| Používatelia | `users` (admin) |

**Backend:** `AdminCountsService`, `GET /api/admin/counts` — viz [ITERATION_42.md](ITERATION_42.md)  
**Frontend:** `useAdminCounts`, `AdminSidebar` badges, Settings `ui.showListCounts`

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
- **Kontaktný formulár (rozšírenie):** pevné predmety (`Všeobecný dotaz`, `Technická podpora`, …) + voľba „Vlastný predmet“; voliteľná **priorita** správy (`low` / `normal` / `high` / `urgent`) — backend model `ContactMessage` už pripravený (It.42+ inbox UX)

**Dokumentácia:** [CONTENT_COMMENTS_NAV.md](CONTENT_COMMENTS_NAV.md) — per-article komentáre, globálne schvaľovanie, nested menu (3 úrovne).

---

## Iterácia 40 – Section FileManager ⏳

Jednotný alebo per-sekcia správca súborov (blog/media, pages/assets, …).

- Scoped roots + ACL (`media:upload` per path prefix)
- Reuse `MediaRepository` + folder API z It.24

---

## Iterácia 43 – Pokročilé vyhľadávanie (FE + BE) ✅

Rýchle skoky v kontexte — nad rámec základného `GET /api/search?q=`.

| Vrstva | Popis |
|--------|--------|
| **Backend** | Unified search endpoint (obsah, media, users, nastavenia, routes); skórovanie, limit per typ, published filter na verejnom API |
| **Admin FE** | Command palette (`Ctrl+K`) — prejsť na stránku/článok/medium, otvoriť editor, skok do modulu |
| **Verejný FE** | Instant search s náhľadom (titulok, excerpt, typ) + deep link na výsledok |

**API (návrh):** `GET /api/search?q=&scope=admin|public&types=page,article,media`  
**UX:** klávesové skratky, história posledných skokov, zvýraznenie zhody.

---

## Iterácia 44 – Filtre, zoradenia & verejný blog ⏳

Konzistentné filtrovanie a sort naprieč zoznamami. **Prvá dodávka (It.44a):** verejný blog — [ITERATION_44.md](ITERATION_44.md).

| Modul | Stav |
|-------|------|
| Verejný blog – pagination zo settings | ✅ `content.blogItemsPerPage` |
| Verejný blog – URL sync | ✅ `?page=&tag=&sort=` |
| Verejný blog – prev/next článok | ✅ |
| Verejný blog – sort dropdown | ✅ newest / oldest / title |
| Admin FilterBar + URL sync (pages/articles) | ✅ It.44b |
| `ui.openLinksInNewTab` setting | ✅ default false (same tab) |
| Admin FilterBar + URL sync (media, comments, trash) | ✅ It.44c |
| Backend `filter[tag]`, author, date | ✅ It.44d (2.0.37) |
| Server-side public blog API | ✅ It.44d |

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

## Iterácia 50 – In-App Micro Firewall (interný WAF) ⏳

Ľahký WAF v PHP — bez externého ModSecurity, plne integrovaný s flat-file CMS a React adminom.

**Full spec:** [ITERATION_50.md](ITERATION_50.md)

| Fáza | Popis |
|------|--------|
| **A – Detekcia** | Scenáre (regex) na URI / UA / query — wp-admin probe, `.env`, traversal, SQL probe |
| **B – Jail** | Dočasný IP ban (15 min), incident log, okamžitý 403 |
| **C – Permanent** | Eskalácia po opakovaných jailoch → trvalý ban |

**Úložisko:** default flat-file JSON (`data/security/firewall/`), scenáre v `config/firewall_scenarios.php` (OPcache). SQLite voliteľne neskôr.

**Admin:** `GET /api/admin/firewall/*`, React modul Unban / Whitelist / incidenty, Settings `firewall.enabled`.

**Nadväznosť:** dopĺňa `RateLimitMiddleware` + `LoginAttemptTracker`, nenahrádza ich.

---

## Post–It.15 wave (It.53–58) ⛔ plánované

Implementácia **až po dokončení It.15**. Prehľad: [ITERATION_WAVE_POST_15.md](ITERATION_WAVE_POST_15.md).

| It. | Téma |
|-----|------|
| 53 | Plynulý reload SPA |
| 54 | Modulárny Markdown + WYSIWYG (profily) |
| 55 | Tiptap JSON → flat-file + upload obrázkov |
| 56 | Menu: popis, ikona, hover náhľad |
| 57 | Generátor tagov a popisu |
| 58 | Layout builder (5 šablón, bloky) + farebné schémy & appearance |
| 59 | Odložená publikácia — editor + kalendár + cron job |
| 60 | Vlastné MD/WYSIWYG komponenty (settings + pluginy) |
| 61 | Footer newsletter — odber + admin toggle |

---

## Iterácia 59 – Odložená publikácia ⏳

Plánovač publikácie priamo v editore stránok/článkov; rozbalovací kalendár v editore a admin filtroch; backend handler na existujúcom job queue (It.29).

**Full spec:** [ITERATION_59.md](ITERATION_59.md)

---

## Iterácia 60 – Vlastné komponenty editora ⏳

Rozšírenie Markdown/WYSIWYG o custom bloky — inštalácia pluginom alebo **Nastavenia → Stránka → Editor**. Konfigurácia pre EDITOR/ADMIN/SUPER_ADMIN (nie USER).

**Full spec:** [ITERATION_60.md](ITERATION_60.md) · nadväzuje na It.54 + It.15.

---

## Iterácia 61 – Newsletter vo footeri ⏳

Rýchle prihlásenie na odber v pätičke verejného webu; zapnutie/vypnutie v administrácii. Oddelené od maintenance newsletter (2.0.51).

**Full spec:** [ITERATION_61.md](ITERATION_61.md)

---

## Odporúčané poradie implementácie

```
… existujúce vetvy …
                → It.44 BE (blog filters) — paralelne s It.15
                → It.15 (PluginManager) ← BLOKÁTOR pre editor wave
                → [WAVE POST-15 — ITERATION_WAVE_POST_15.md]
                    It.53 (smooth reload)
                    → It.54 → It.55 (modular editor + Tiptap storage)
                    → It.56 (rich menu) — môže paralelne po It.53
                    → It.57 (meta generators)
                    → It.58 (layout builder)
                    → It.59 (scheduled publish) — po It.29 job infra
                    → It.60 (editor custom components) — po It.54/15
                    → It.61 (footer newsletter) — môže paralelne s It.52 follow-ups
```

⛔ **It.53–58: iba dokumentácia v repozitári — implementácia až po dokončení It.15.**

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
- [ITERATION_50.md](ITERATION_50.md) — in-app micro firewall (WAF)
- [ITERATION_WAVE_POST_15.md](ITERATION_WAVE_POST_15.md) — It.53–58 editor & UX (after It.15)
- [ITERATION_53.md](ITERATION_53.md) … [ITERATION_58.md](ITERATION_58.md) — wave detail specs
- [ITERATION_59.md](ITERATION_59.md) — scheduled publish
- [ITERATION_60.md](ITERATION_60.md) — custom editor components
- [ITERATION_61.md](ITERATION_61.md) — footer newsletter
