# PaginiumCMS – Backlog iterácií (27+)

> Plánované moduly a rozšírenia po **Iterácii 26** (2.0.13).  
> Legenda: ⏳ plánované · 🟡 stredná priorita · 🔵 nižšia · 🔴 kritická

**Aktuálne hotové:** It.1–24 ✅ · It.26 ✅ · It.25 ⏳ (setup wizard — odložené)

---

## Prehľad

| It. | Verzia | Názov | Priorita | Poznámka |
|-----|--------|-------|----------|----------|
| 25 | TBD | [Setup wizard](ITERATION_25.md) | 🟡 | Odložené — téma cez Settings |
| 26 | 2.0.13 | [Media preview lightbox](ITERATION_26.md) | ✅ | Fit + 1:1 náhľad |
| **27** | TBD | **Bulk actions (platform)** | 🟡 | Hromadné akcie naprieč adminom |
| **28** | TBD | **Job Queue / Background Worker** | 🔵 | Dlhé úlohy mimo requestu |
| **29** | TBD | **Contextual Actions** | 🟡 | Akcie podľa kontextu (content, media, user) |
| **30** | TBD | **Live Preview** | 🟡 | Náhľad stránky/článku pred publikovaním |
| **31** | TBD | **React chunking + PHP OPcache** | 🔵 | Výkon FE/BE |
| **32** | TBD | **Analytics: GeoIP, zariadenie, referrer** | 🟡 | Rozšírenie `Analytics/*` |
| **33** | TBD | **System overview (PHP + FE engine)** | 🟡 | Dashboard verzií stacku |
| **34** | TBD | **Flat-File Inspector** | 🟡 | Prehliadač súborov na disku |
| **35** | TBD | **Pagination (admin + verejný obsah)** | 🟡 | Stránkovanie zoznamov |
| **36** | TBD | **Inline edit na FE po prihlásení** | 🟡 | Tlačidlá „Upraviť“ na verejnom webe |
| **37** | TBD | **Feature flags (FE moduly on/off)** | 🟡 | Settings skupina `features.*` |
| **38** | TBD | **Komentáre: schvaľovanie rolou + hostia** | 🟡 | Role-based approve, guest toggle |
| **39** | TBD | **Section FileManager (scoped DAM)** | 🟡 | Priečinky + práva per sekcia |

---

## Iterácia 27 – Bulk actions (platform) ⏳

Hromadné operácie nad viacerými entitami v administrácii (nie len Media Library).

- Unified `BulkActionBar` pattern (content, users, trash, comments)
- Backend batch endpointy s ACL
- **Media bulk delete** už existuje (It.24) — zovšeobecniť

---

## Iterácia 28 – Job Queue / Background Worker ⏳

- Flat-file alebo Redis fronta úloh
- Worker CLI / cron: stock import batch, backup, index rebuild
- Status API pre admin (`GET /api/jobs/{id}`)

---

## Iterácia 29 – Contextual Actions ⏳

Kontextové menu / toolbar podľa miesta v admin SPA (stránka obsahu, media grid, audit log).

- `ContextActionRegistry` (FE) + permission map (BE)

---

## Iterácia 30 – Live Preview ⏳

Náhľad publikovanej stránky bez opustenia editora.

- iframe / draft URL token
- Prepojenie s locking (It.1) a drafts (It.2)

---

## Iterácia 31 – React chunking + PHP OPcache ⏳

**Frontend:** route-based code splitting, lazy admin routes  
**Backend:** OPcache preload config, dokumentácia deploy

---

## Iterácia 32 – Analytics enrichment ⏳

Rozšírenie existujúceho `Core/Analytics/`:

| Pole | Zdroj |
|------|--------|
| Geo (krajina/mesto) | GeoIP služba / MaxMind lite |
| Referrer / UTM | HTTP hlavičky |
| Zariadenie | existujúci `DeviceDetector` |
| IP (hash/anonymized) | GDPR-aware nastavenie |

---

## Iterácia 33 – System overview ⏳

Admin panel: PHP verzia, OPcache stav, Vite/React build info, PaginiumCMS verzia, disk usage.

- `GET /api/admin/system/overview`

---

## Iterácia 34 – Flat-File Inspector ⏳

Prehliadač `storage/app/content/` s read-only režimom a DEV unlock pre zápis.

- Strom súborov, náhľad JSON/MD, validácia schémy

---

## Iterácia 35 – Pagination ⏳

- Admin zoznamy (content, users, audit, media) — server-side `page` + `limit`
- Verejný blog/pages — už čiastočne It.19; dorobiť FE + API kontrakt

---

## Iterácia 36 – Frontend inline editing ⏳

Po prihlásení editor/admin na verejnom webe:

- Floating „Edit this page“ / „New article“
- Presmerovanie do admin editora s lockom

---

## Iterácia 37 – Feature flags (FE modules) ⏳

Všetky voliteľné FE funkcie zapínateľné v administrácii.

- Settings skupina `features` (komentáre, analytics widget, WYSIWYG, …)
- `PublicSiteContext` + admin guard

---

## Iterácia 38 – Komentáre: moderácia + hostia ⏳

- Schválenie komentára rolou (EDITOR vs ADMIN)
- `comments.allowGuestComments` — už v schéme, dorobiť FE + enforcement
- Front-end formulár pre hostí s CAPTCHA (voliteľne)

---

## Iterácia 39 – Section FileManager ⏳

Jednotný alebo per-sekcia správca súborov (blog/media, pages/assets, …).

- Scoped roots + ACL (`media:upload` per path prefix)
- Reuse `MediaRepository` + folder API z It.24

---

## Odporúčané poradie implementácie

```
It.26 ✅ → It.35 (pagination) → It.37 (feature flags)
         → It.38 (komentáre) → It.36 (inline FE edit)
         → It.32 (analytics) → It.33 (system overview)
         → It.34 (inspector) → It.39 (section FileManager)
         → It.27 (bulk platform) → It.28 (jobs) → It.29 (contextual) → It.30 (live preview)
         → It.31 (performance)
```

---

## Súvisiace dokumenty

- [ROADMAP.md](ROADMAP.md) — hlavná mapa It.1–24
- [ITERATION_24.md](ITERATION_24.md) — DAM v1 + stock knižnica
- [ITERATION_26.md](ITERATION_26.md) — media lightbox
