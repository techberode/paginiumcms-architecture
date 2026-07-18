# PaginiumCMS – Backlog iterácií (27+)

> Plánované moduly a rozšírenia po **Iterácii 26 / hotfix 2.0.14**.  
> Legenda: ⏳ plánované · 🟡 stredná priorita · 🔵 nižšia · 🔴 kritická

**Aktuálne hotové:** It.1–24 ✅ · It.26 ✅ (2.0.13–2.0.14) · It.25 ⏳ (setup wizard — odložené)

**Ďalšia iterácia:** [It.27 – Admin view modes + SEO panel](ITERATION_27.md)

---

## Prehľad

| It. | Verzia | Názov | Priorita | Poznámka |
|-----|--------|-------|----------|----------|
| 25 | TBD | [Setup wizard](ITERATION_25.md) | 🟡 | Odložené — téma cez Settings |
| 26 | 2.0.14 | [Media preview + binárny hotfix](ITERATION_26.md) | ✅ | Lightbox + strict formats |
| **27** | TBD | **[Admin view modes + SEO panel](ITERATION_27.md)** | **🟡 ďalšia** | List / list+preview / grid + SEO UX |
| **28** | TBD | **Bulk actions (platform)** | 🟡 | Hromadné akcie naprieč adminom |
| **29** | TBD | **Job Queue / Background Worker** | 🔵 | Dlhé úlohy mimo requestu |
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

## Iterácia 27 – Admin view modes + SEO panel ⏳ **← next**

Display modes in admin SPA and SEO metadata workflow. **Full spec (English):** [ITERATION_27.md](ITERATION_27.md)

- **View modes:** `list` · `list-preview` · `preview` (grid) — Media, Articles, Pages
- **SEO panel:** alt, tags, meta description, OG image, robots, canonical
- **SEO health:** badge + filter for missing fields in lists
- Builds on `SeoMetaBuilder` (It.23) and DAM sidecar (It.24)

---

## Iterácia 28 – Bulk actions (platform) ⏳

Hromadné operácie nad viacerými entitami v administrácii (nie len Media Library).

- Unified `BulkActionBar` pattern (content, users, trash, comments)
- Backend batch endpointy s ACL
- **Media bulk delete** už existuje (It.24) — zovšeobecniť
- Bulk SEO patch (doplnenie meta polí) — naviazané na It.27

---

## Iterácia 29 – Job Queue / Background Worker ⏳

- Flat-file alebo Redis fronta úloh
- Worker CLI / cron: stock import batch, backup, index rebuild
- Status API pre admin (`GET /api/jobs/{id}`)

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
It.26/2.0.14 ✅ → It.27 (view modes + SEO panel) ← ďalšia
                → It.36 (pagination) → It.38 (feature flags)
                → It.39 (komentáre) → It.37 (inline FE edit)
                → It.33 (analytics) → It.34 (system overview)
                → It.35 (inspector) → It.40 (section FileManager)
                → It.28 (bulk platform) → It.29 (jobs) → It.30 (contextual) → It.31 (live preview)
                → It.32 (performance)
```

---

## Súvisiace dokumenty

- [ROADMAP.md](ROADMAP.md) — hlavná mapa It.1–24
- [ITERATION_27.md](ITERATION_27.md) — **ďalšia iterácia**
- [ITERATION_24.md](ITERATION_24.md) — DAM v1 + stock knižnica
- [ITERATION_26.md](ITERATION_26.md) — media lightbox + 2.0.14 hotfix
