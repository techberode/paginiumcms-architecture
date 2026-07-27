# Príručka administrátora PaginiumCMS

> Kompletný prehľad admin panelu — moduly, roly, bežné úlohy.  
> Začni inštaláciou: [INSTALLATION.md](INSTALLATION.md) → [FIRST_STEPS.md](FIRST_STEPS.md).

---

## Obsah

1. [Roly a oprávnenia](#roly-a-oprávnenia)
2. [Pracovný priestor](#pracovný-priestor)
3. [Schránka](#schránka)
4. [Platforma](#platforma)
5. [Vývoj a rozšírenia](#vývoj-a-rozšírenia)
6. [Bezpečnosť](#bezpečnosť)
7. [Prevádzka](#prevádzka)
8. [Klávesové skratky a tipy](#klávesové-skratky-a-tipy)

---

## Roly a oprávnenia

| Rola | Admin panel | Typické oprávnenia |
|------|-------------|-------------------|
| **USER** | ❌ | Registrácia, komentáre (ak povolené) |
| **EDITOR** | ✅ | Stránky, články, médiá, navigácia, audit (čítanie), zálohy |
| **ADMIN** | ✅ | + používatelia, nastavenia, firewall, logy, schránka, preklady |
| **SUPER_ADMIN** | ✅ | + Path ACL, extensions, blueprints, GitHub (demo reset = ADMIN+) |

**Pravidlo:** EDITOR vidí obsah; ADMIN spravuje platformu; SUPER_ADMIN spravuje aj bezpečnostné a vývojové nástroje.

Priradenie rolí: **Používatelia** → editácia účtu → pole **Rola**.

Staff účty (EDITOR+) musia mať zapnutú **2FA** — nastavenie v **Účet → Bezpečnosť** (`/account/security`).

---

## Pracovný priestor

### Stránky (`/pages`)

Statické podstránky webu (O nás, Kontakt, …).

| Akcia | Postup |
|-------|--------|
| Zoznam | Filtre, vyhľadávanie, stránkovanie, bulk akcie |
| Nová | **Nová stránka** → editor |
| Editácia | Klik na riadok → `/pages/{slug}` |
| Šablóna | Vo formulári: default, home, about, contact, landing, … |
| Náhľad | Tlačidlo **Náhľad** (staff) — simulácia verejného vzhľadu |

Detail: [CONTENT_EDITOR.md](CONTENT_EDITOR.md).

### Články (`/articles`)

Blogové príspevky — rovnaký editor + tagy, excerpt, komentáre.

| Pole | Účel |
|------|------|
| Tagy | Filtrovanie na `/blog` |
| OG / náhľadový obrázok | Karta v zozname + sociálne siete |
| Komentáre | Povolenie / schvaľovanie / hostia (tri-stav: globálne / áno / nie) |

### Médiá (`/media`)

Digitálny asset manager (DAM).

| Funkcia | Popis |
|---------|--------|
| Upload | Drag & drop, viac súborov, priečinky |
| Metadata | Alt, title, SEO health badge |
| Stock import | Témy obrázkov (ak povolené) |
| Lightbox | Náhľad obrázkov |
| Bulk | Hromadné mazanie, SEO filter |

**Dôležité:** verejné URL začína `/storage/app/content/media/…` — nginx musí proxyovať `/storage` na backend.

### Navigácia (`/navigation`)

Strom položiek hlavného menu verejného webu.

- Presúvanie poradia (drag & drop)
- Vnorené položky (parent)
- Cieľ `_self` / `_blank`

Zmeny sa prejavia okamžite na verejnom webe po uložení.

---

## Schránka

*Len ADMIN / SUPER_ADMIN.*

### Komentáre (`/comments`)

Moderácia komentárov pod článkami.

| Stav | Význam |
|------|--------|
| Čaká na schválenie | Viditeľný až po approve |
| Schválený | Na verejnom webe |
| Zamietnutý | Skrytý |

Bulk schválenie / zmazanie. Globálne pravidlá v **Nastavenia → Komentáre**.

### Správy (`/messages`)

Kontaktný formulár z verejného webu → admin inbox.

- Priorita (normálna, vysoká, urgentná)
- Archivácia, bulk akcie
- Detail správy v rozbalenom riadku

---

## Platforma

### Nastavenia (`/settings`)

Skupiny (záložky v UI):

| Skupina | Čo nastavíš |
|---------|-------------|
| **Všeobecné** | Názov, jazyk, timezone, registrácia |
| **Logo a favicon** | Logo webu + favicon (media picker) → [BRANDING.md](BRANDING.md) |
| **Obsah / Editor / SEO** | Stránkovanie, editor, meta, feedy |
| **Prihlásenie** | Vzhľad login/registrácie, pozadie |
| **Bezpečnosť** | Politika hesiel, 2FA, upload/content security |
| **Oprávnenia rolí** | *SUPER_ADMIN* — RBAC checkboxy + Path ACL → [ACCESS_CONTROL.md](ACCESS_CONTROL.md) |
| **Notifikácie / Firewall / Logy** | SMTP, WAF, retention logov |
| **Cache** | Vymazanie cache obsahu (panel v Systém) |

Zmeny sa ukladajú do `data/settings.json` (flat-file). Citlivé polia (SMTP heslo) sú šifrované.

### Preklady (`/translations`)

*ADMIN+* — správa jazykových mutácií admin UI (SK/EN moduly).

### Používatelia (`/users`)

*ADMIN+* — CRUD účtov.

| Pole | Poznámka |
|------|----------|
| Email | Prihlasovací identifikátor |
| Rola | USER / EDITOR / ADMIN / SUPER_ADMIN |
| Avatar | Upload alebo URL |
| Aktívny | Neaktívny sa nemôže prihlásiť |

**Nikdy** nepriraďuj SUPER_ADMIN bežným editorom bez dôvodu.

### Notifikácie (`/notifications`)

Konfigurácia kanálov (email, ntfy, Telegram, webhook) + test odoslania.

### Plánovač (`/scheduler`)

*ADMIN+* — cron úlohy (zálohy, monitoring, demo reset, fronta jobov).

### Bezpečnosť účtu (`/account/security`)

Pre **prihláseného** používateľa:

- zapnutie / vypnutie 2FA (ak politika dovoľuje)
- QR kód, backup kódy
- zmena hesla

---

## Vývoj a rozšírenia

### Code Editor (`/code-editor`)

Úprava PHP/TS/CSS súborov z prehliadača — **len po odomknutí Developer Mode** (TOTP).

→ [CODE_EDITOR.md](CODE_EDITOR.md) · [DEVELOPER_MODE.md](DEVELOPER_MODE.md)

### Blueprinty (`/blueprints`)

*SUPER_ADMIN* — definícia polí pre typy obsahu (page, article, …). DynamicForm náhľad.

### Doplnky (`/extensions`)

*SUPER_ADMIN* — správa pluginov (inštalácia, enable/disable).

→ [PLUGINS.md](PLUGINS.md)

### Demo (`/demo`)

**Len inštancia `demo.paginiumcms.com`** — správa sandboxu a reset seed dát. Na zákazníckej produkcii `DEMO_MODE=false`, modul neviditeľný.

| Funkcia | Popis |
|---------|--------|
| Sidebar **Demo modul** | Skrytý na demo inštancii — prístup cez amber banner → `/demo` |
| Onboarding panel | Rýchly štart — verejný web, admin moduly, reset |
| Countdown | Ďalší auto-reset podľa `DEMO_AUTO_RESET_MINUTES` |
| Reset seed | **ADMIN+** — obnoví ukážkový snapshot (komentáre, správy, newsletter, kontakt) |
| Amber banner | V admin shell + verejný **DemoPublicStrip** na demo webe |

**Po deployi `v2.1.0-beta.10+`:** spusti **Reset demo seed**, inak chýbajú nové ukážkové dáta.

**Prihlásenie:** login → **Prihlásiť ako demo admin** (heslo nie je v public API). Manuálne: `demo@paginiumcms.com` / `Demo123!`.

### Marketing (prod only)

**Nastavenia → Marketing** — URL demo inštancie a zapnutie footer odkazu „Vyskúšajte CMS“ (`paginiumcms.com` → `demo.paginiumcms.com` v novom tabe).

Detail: [ITERATION_13.md](../ITERATION_13.md) · deploy: [deploy/DEMO_DEPLOY.md](../deploy/DEMO_DEPLOY.md)

---

## Bezpečnosť

### Firewall (`/firewall`)

Interný WAF — jail IP, whitelist, scenáre útokov.

→ [FIREWALL.md](FIREWALL.md)

### Logy (`/logs`)

HTTP access, severity, filtre podľa dátumu.

→ [LOGGING.md](LOGGING.md)

### Audit (`/audit`)

História zmien obsahu a akcií používateľov — export JSON.

### Bezpečnostný audit (`/security/audit`)

Spustenie audit engine (integrita, konfigurácia, výkon) — report v admin UI.

### Oprávnenia rolí a Path ACL

*SUPER_ADMIN* — **Nastavenia → Bezpečnosť → Oprávnenia rolí** (`/settings?category=security&group=accessControl`).

- Globálne oprávnenia pre roly ADMIN, EDITOR, USER (checkboxy)
- Path ACL — obmedzenie prístupu k cestám stránok, článkov a médií (opt-in)

Stará URL `/security/acl` presmeruje sem. Detail: [ACCESS_CONTROL.md](ACCESS_CONTROL.md).

### Logo a favicon

**Nastavenia → Stránka → Logo a favicon** — upload alebo výber z médií. Detail: [BRANDING.md](BRANDING.md).

---

## Prevádzka

### Zálohy (`/backups`)

| Akcia | Popis |
|-------|--------|
| Vytvoriť | Snapshot content + nastavení |
| Stiahnuť | ZIP archív |
| Obnoviť | Import (opatrne — prepíše dáta) |
| Overiť SHA256 | Integrita súboru |

Plánované zálohy: **Plánovač** alebo cron `backup:run-schedule`.

### Režim údržby a newsletter

**Nastavenia → Režim údržby** — režimy **Vypnuté**, **Coming Soon**, **Údržba** (vzájomne vylučujúce).

| Pole | Účel |
|------|------|
| Pozadie (URL) | Obrázok za obsahom — vyber z **Médií** alebo upload (`/storage/…` cesta). Po fixe ISS-095 (`88cbe31`) |
| Newsletter | Formulár na Coming Soon / Údržba stránke |
| Texty | Badge, nadpis, podnadpis, telo — editovateľné bez deploye |

**Staff výnimka:** prihlásený EDITOR/ADMIN/SUPER_ADMIN vidí bežný web aj počas údržby.

**Newsletter odberatelia — admin prehľad:**

Admin → **Newsletter** (`/newsletter`) — tabuľka odberateľov (footer + maintenance), KPI podľa zdroja, CSV export. Nastavenia → **Newsletter** — `footerEnabled`, `footerHint`.

Flat-file zdroj (záloha / CLI):

```
backend/storage/app/content/data/newsletter/subscribers.json
```

Záznamy: `email`, `subscribedAt`, `source` (`footer` | `maintenance`).

**Zatiaľ nie je:** odhlásenie (unsubscribe) — backlog.

Detail: [ITERATION_61.md](../ITERATION_61.md) · incidenty [ISSUES.md § ISS-095–098](../ISSUES.md).

### Kôš (`/trash`)

*ADMIN+* — zmazaný obsah pred trvalým odstránením. Obnovenie alebo permanent delete.

### GitHub sync (`/github`)

*ADMIN+* — push/pull obsahu do Git repozitára (ak `GITHUB_*` v `.env`).

---

## Analytika (`/analytics`)

*ADMIN+* — návštevnosť, zdroje, zariadenia, geografia. Vyžaduje zapnutú analytiku a traffic na webe.

---

## Klávesové skratky a tipy

| Skratka | Kde | Účel |
|---------|-----|------|
| `Ctrl+K` / `Cmd+K` | Admin | Command palette (rýchle vyhľadávanie stránok, článkov, modulov) |
| Uložiť | Editor | Vždy explicitné — autosave ide do **draftu**, nie do publikovanej verzie |

**Tipy:**

- Po väčšej zmene obsahu: **Nastavenia → Cache → vymazať cache obsahu**
- Pred upgrade: **Záloha** + export audit logu
- Pri 429 / lockout: administrátor spustí `php backend/bin/console security:clear-lockouts` na serveri
- Verejný web neukazuje draft — použi **náhľad** alebo dočasne publikuj

---

## Súvisiace dokumenty

| Dokument | Obsah |
|----------|--------|
| [README.md](README.md) | Vstupná stránka príručky |
| [INSTALLATION.md](INSTALLATION.md) | Inštalácia |
| [FIRST_STEPS.md](FIRST_STEPS.md) | Prvé prihlásenie a obsah |
| [CONTENT_EDITOR.md](CONTENT_EDITOR.md) | Editor do hĺbky |
| [deploy/NGINX_API.md](../deploy/NGINX_API.md) | Produkcia nginx |
