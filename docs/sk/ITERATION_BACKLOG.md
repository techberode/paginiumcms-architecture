# PaginiumCMS — konsolidovaný backlog

> **Snapshot:** `v2.1.0-beta.23` · 2. august 2026  
> **Pravidlo:** aktívny backlog obsahuje iba nedodaný alebo presne ohraničený zostávajúci rozsah  
> **No-SQL:** [architecture/NOSQL_MANDATE.md](architecture/NOSQL_MANDATE.md)

Tento dokument opravuje starý backlog, v ktorom sa miešali hotové iterácie, plánované funkcie, absorbované návrhy a znovu použité čísla. História vydaného rozsahu patrí do `CHANGELOG.md`; detail hotových funkcií do [FEATURE_OVERVIEW.md](FEATURE_OVERVIEW.md).

| Symbol | Význam |
|--------|--------|
| 🔴 | kritická priorita |
| 🟡 | stredná priorita |
| 🔵 | nižšia / voliteľná priorita |
| ⏳ | aktívne plánované |
| ⏸️ | pozastavené rozhodnutím |
| 🟡 partial | dodaný základ, zostáva menší explicitný rozsah |
| ↪ | absorbované inou iteráciou |
| ✅ | hotové; uvádza sa iba v konsolidačnej tabuľke, nie ako aktívny backlog |

---

## 1. Aktívne priority

| Poradie | Položka | Priorita | Stav | Dôvod |
|---------|---------|----------|------|-------|
| 1 | Dokončiť dvojjazyčnú dokumentáciu | 🔴 | 🚧 | gate pred zmenou jadra |
| 2 | **It.68** Hybrid Engine foundation | 🔴 | ⏸️ | storage abstraction, schema registry, engine settings |
| 3 | **It.69** Unified cache + Redis + HTTP validators | 🔴 | ⏳ | výkon a zjednotenie starých It.45/49 |
| 4 | **It.67** Untrusted surfaces hardening | 🔴 | ⏳ | importy, shortcode, Monaco, témy, CSP |
| 5 | **It.70** Git publish modes | 🟡 | ⏳ | immediate/queued distribúcia |
| 6 | **It.71** Performance Guard | 🟡 | ⏳ | APM a bezpečné fallbacky |
| 7 | **It.72** Media drivers | 🟡 | ⏳ | local + S3/CDN |
| 8 | **It.73** Multi-locale document | 🟡 | ⏳ | základ pre preklady |
| 9 | **It.74** API keys/JWT | 🟡 | ⏳ | headless integrácie, aditívna auth |
| 10 | **It.58d** Layout remainder | 🟡 | ⏳ | presne uzamknúť zostávajúce bloky |
| 11 | **It.25** Setup wizard/update UX | 🟡 pre-Final | ⏳ | onboarding a GA polish |
| 12 | **It.76/77** Translation providers | 🔵 | ⏳ | po It.73 |
| 13 | **It.75** AI agent | 🔵 | ⏳ | po content locale a provider vrstvách |

---

## 2. Hybrid Engine backlog It.68–77

### It.68 — foundation 🔴

- `StorageInterface` a lokálny kompatibilný driver,
- engine settings s Classic defaultom,
- JSON Schema registry,
- existujúce súbory bez SQL migrácie,
- diagnostika a rebuild,
- bezpečnostné a migračné testy.

### It.69 — cache a HTTP validators 🔴

- memory/file/Redis driver factory,
- read-through cache a deterministická invalidácia,
- Redis fallback,
- `ETag` a `Last-Modified`,
- cache health/diagnose,
- absorbuje návrhy It.45 a It.49.

### It.70 — Git publish 🟡

- immediate a queued strategy,
- commit metadata a audit,
- retry/idempotency,
- outbound/network policy,
- build/deploy webhook ako oddelený krok,
- zosúladenie s It.48 static render.

### It.71 — Performance Guard 🟡

- middleware timing a memory/I/O metrics,
- budgety per route/workflow,
- incidenty a reporty,
- dokumentované self-heal iba pre odvodené vrstvy,
- žiadne automatické zmeny primárneho obsahu.

### It.72 — media drivers 🟡

- `MediaStorageInterface`/Flysystem,
- local driver ako default,
- S3-compatible driver,
- signed/public URL policy,
- migrácia referencií bez straty obsahu,
- MIME/path/security parity.

### It.73 — multi-locale content 🟡

- jeden dokument s locale variants,
- fallback locale,
- per-locale validation a SEO,
- editor tabs/diff,
- kompatibilita so starými single-locale dokumentmi.

### It.74 — API keys a JWT 🟡

- scope-limited keys,
- hash-at-rest, plaintext iba pri vytvorení,
- revoke/rotate/audit/rate limit,
- JWT expiry a audience,
- admin session + CSRF bez regresie.

### It.75 — CMS-aware AI agent 🔵

- tool allow-list,
- agent beží ako prihlásený používateľ,
- návrhy patchov bez automatického uloženia,
- async queue,
- provider adapter a outbound guard,
- audit bez logovania citlivého content payloadu.

### It.76 — self-hosted translation 🔵

- spoločný `TranslationProviderInterface`,
- LibreTranslate-compatible driver,
- preview/diff a explicitné Apply,
- quota/rate limit,
- SSRF ochrana a timeout.

### It.77 — cloud translation 🔵

- DeepL/Google drivers,
- encrypted credentials,
- usage meter a generic errors,
- optional fallback,
- žiadny live network v CI.

---

## 3. Pre-Final backlog

### It.25 — setup wizard a zjednodušený update UX 🟡

Dodaný základ:

- `first-run.sh`,
- admin bootstrap,
- system update engine/panel,
- installation a first steps docs.

Zostáva:

- `/setup` iba pre nenainštalovaný stav,
- admin/site profile krok,
- bezpečné dokončenie s installed flag,
- dashboard update banner a jednoduchý „Update now“,
- rollback/backup pre update UX,
- test čistého install a update z podporovanej beta verzie.

### Komunitné beta testovanie 🔴

- clean install na cudzej infra,
- upgrade a rollback,
- non-maintainer UX feedback,
- bezpečnostný review,
- dokumentačná reprodukovateľnosť.

### Final release gate 🔴

- uzamknutý GA scope,
- žiadne kritické otvorené incidenty,
- release candidate,
- finálna SK/EN dokumentácia,
- backup restore drill,
- release notes a support policy.

---

## 4. Paralelný produktový backlog

| Položka | Stav | Poznámka |
|---------|------|----------|
| **It.58d** layout blocks/polish | ⏳ | nesmie vytvoriť druhý page model |
| **It.48** static/dynamic render | ⏳ | spojiť návrh s It.70 publish pipeline |
| Zostávajúci theme runtime | 🟡 | závisí od It.67 a schema/policy gate |
| Server metrics agent (zvyšok It.46) | ⏳ | koordinovať s It.71 |
| Scoped FileManager | ⏳ candidate | prideliť nové unikátne číslo až po scope approval |
| Frontend inline edit | ⏳ candidate | používa existujúci lock/editor flow |
| Jemnejšia comment moderácia/CAPTCHA | ⏳ candidate | základ policy už je dodaný |
| Contextual Actions | ⏳ candidate | staré označenie „It.30“ sa nesmie použiť |
| System overview polish | ⏳ candidate | môže byť súčasť It.71/ops dashboard |

---

## 5. Absorbované návrhy

| Starý návrh | Nový kanonický cieľ | Stav |
|-------------|----------------------|------|
| It.45 Redis infrastructure | **It.69** | ↪ absorbované |
| It.49 Unified cache | **It.69** | ↪ absorbované |
| It.31 Live Preview | It.51 + It.58d | 🟡 základ dodaný, zvyšok konkretizovať |
| It.36 Pagination | It.19 + It.44 | ✅ dodané |
| It.39 comments/guest foundation | comments policy + workflow | ✅ základ; iba konkrétne rozšírenia zostávajú |
| It.46 metrics | It.71 + host metrics sub-scope | ↪ koordinovať |
| It.48 publish idea | It.70 + static renderer | ↪ spoločný návrh |

---

## 6. Opravené zastarané stavy

Nasledujúce položky boli v starom backlogu chybne označené ako plánované alebo nedokončené:

| It. | Funkcia | Správny stav |
|-----|---------|--------------|
| 43 | Advanced search / command palette | ✅ dodané |
| 44 | Filters a public blog pagination | ✅ dodané |
| 47 | Notification connector auth | ✅ dodaný základ |
| 50 | In-App Micro Firewall | ✅ `2.0.26` |
| 53 | Smooth SPA reload | ✅ `2.0.39` |
| 54 | Modular editor profiles | ✅ `2.0.42` |
| 55 | Tiptap JSON + upload | ✅ `2.0.43` |
| 56 | Rich navigation | ✅ `beta.5` |
| 57 | Auto tags/meta | ✅ `beta.4` |
| 59 | Scheduled publish | ✅ `2.0.53` |
| 60 | Custom editor components | ✅ foundation |
| 61 | Newsletter v2/footer | ✅ `beta.16`–`beta.18` |
| 62 | Scheduler hardening | ✅ `beta.9` |
| 63 | System update | ✅ `beta.12`–`beta.15` |
| 65 | Feature gallery | ✅ phases 1–3 |
| 66 | Security write-time packs | ✅ `beta.22` |
| 58b/58c | Appearance + Layout Switch | ✅ `beta.8` / `beta.23` |

Hotové položky sa ďalej sledujú cez incidenty a follow-up specs, nie ako aktívne celé iterácie.

---

## 7. Číslovanie a backlog hygiene

1. Číslo iterácie je globálne unikátne.
2. Starý nápad bez samostatnej špecifikácie dostane dočasný názov `candidate:<slug>`, nie recyklované číslo.
3. Po dodaní sa iterácia presunie do release histórie; v backlogu zostane iba explicitný remainder.
4. Absorbovaný rozsah má jedného vlastníka a jednu acceptance sadu.
5. Stav sa neurčuje podľa nadpisu starého dokumentu, ale podľa changelogu, kódu, testov a end-to-end zapojenia.
6. „Partial“ musí uviesť presne, čo je hotové a čo zostáva.
7. Každá nová položka obsahuje dependency, non-goals, security gate, No-SQL impact a definition of done.

---

## 8. Odporúčané poradie

```text
Docs gate
  → It.68
  → It.69
  → It.67 security hardening
  → It.70 / It.48 unified publish design
  → It.71 + remaining It.46
  → It.72
  → It.73
  → It.74
  → It.76 / It.77
  → It.75

Parallel where safe: It.58d, beta fixes, community testing.
Pre-Final: It.25 + GA gate.
```

Poradie sa môže zmeniť iba s aktualizáciou roadmapy, závislostí a oboch jazykových vydaní.
