# PaginiumCMS — roadmapa projektu

> **Dokumentačný checkpoint:** 2. august 2026  
> **Najnovšie vydanie zachytené v tomto balíku:** `v2.1.0-beta.23`  
> **Smerovanie:** Hybrid Headless Content Engine · No-SQL súborový zdroj pravdy · API-first  
> **Stav kódu:** implementácia It.68+ je pozastavená do dokončenia dvojjazyčnej dokumentácie

Táto roadmapa je kanonická mapa **budúceho smerovania**. História jednotlivých vydaní patrí do [`CHANGELOG.md`](../CHANGELOG.md), detailné implementačné špecifikácie do `ITERATION_*.md` a incidenty do [`ISSUES.md`](ISSUES.md).

**Architektúra:** [architecture/HYBRID_ENGINE.md](architecture/HYBRID_ENGINE.md) · **No-SQL mandát:** [architecture/NOSQL_MANDATE.md](architecture/NOSQL_MANDATE.md) · **Backlog:** [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md)

| Symbol | Význam |
|--------|--------|
| ✅ | dodané a zapojené end-to-end |
| 🟡 | čiastočne dodané alebo zostáva jasne ohraničený zvyšok |
| ⏳ | naplánované, implementácia nezačala |
| ⏸️ | vedome pozastavené |
| 🔴 | kritická priorita pre architektúru alebo bezpečnosť |

---

## 1. Strategický cieľ

PaginiumCMS sa vyvíja z produkčne použiteľného flat-file CMS na **Hybrid Headless Content Engine**. Pivot nemení základný dátový princíp:

1. JSON, Markdown a YAML súbory zostávajú primárnym zdrojom pravdy.
2. Index, cache, Redis, Git a externé médiové úložiská sú odvodené alebo distribučné vrstvy.
3. Classic režim zostáva plne podporovaný a je bezpečný fallback bez Redis, S3 alebo Git služby.
4. Administrátorská session, CSRF, RBAC a 2FA zostávajú primárnym modelom administrácie.
5. API kľúče a JWT sa pridajú aditívne pre headless klientov; nesmú obísť existujúcu autorizáciu.
6. Každá nová vrstva musí mať diagnostiku, fallback a obnovu zo súborového zdroja pravdy.

---

## 2. Aktuálny míľnik — dokumentačná Fáza 0

| Oblasť | Stav | Výsledok |
|--------|------|----------|
| Identita a filozofia | ✅ | projekt je jednotne opísaný ako Hybrid Headless Content Engine |
| No-SQL mandát | ✅ | primárny a odvodený stav sú jasne oddelené |
| Režimy nasadenia | ✅ | Classic, Hybrid a Git-headless profily |
| Roadmapa a backlog | ✅ táto iterácia | odstránené staré priority a duplicitné čísla |
| Dvojjazyčná dokumentácia | 🚧 | samostatné, štruktúrne zhodné stromy `SK/` a `EN/` |
| Implementácia Hybrid Engine | ⏸️ | začne až po dokumentačnom gate |

**Dokumentačný gate je splnený**, keď si SK a EN vydanie neprotirečia, stavy funkcií sú zhodné a plánované schopnosti nie sú prezentované ako hotové.

---

## 3. Dodaný základ Public Beta

Namiesto opakovania desiatok historických špecifikácií roadmapa zoskupuje hotové funkcie podľa schopností.

| Oblasť | Stav | Hlavné schopnosti |
|--------|------|-------------------|
| Obsah a spolupráca | ✅ | stránky, články, zámky, auto-save, revízie, 3-way merge, konflikty, plánovaná publikácia |
| Editory | ✅ | Markdown, modulárny Tiptap/WYSIWYG, JSON storage, upload médií, vlastné editorové komponenty |
| Verejný web | ✅ | React web, blog, SK/EN i18n, SEO, feedy, navigácia, galéria funkcií, newsletter |
| Administrácia | ✅ | dashboard, vyhľadávanie, filtre, bulk akcie, koš, zálohy, scheduler, systémový update |
| Bezpečnosť | ✅ priebežne | session, CSRF, RBAC, 2FA, šifrovanie, WAF, rate limits, SSRF/Zip-Slip/path ochrany, audit |
| Rozšírenia | ✅ základ | externé pluginy, hooky, Code Policy, Developer Mode, Code Editor |
| Prevádzka | ✅ základ | Docker onboarding, health, monitoring, logy, release a deploy workflow |
| Hybrid Engine vrstvy | 🟡 | index a file/memory cache existujú; jednotné abstrakcie, Redis/Git/APM/S3 sú plánované |

Detailný inventár: [FEATURE_OVERVIEW.md](FEATURE_OVERVIEW.md).

---

## 4. Aktívna Hybrid Engine vlna

| It. | Téma | Priorita | Stav | Závislosti / poznámka |
|-----|------|----------|------|----------------------|
| **68** | Storage abstraction, schema registry a engine settings | 🔴 | ⏸️ | prvá implementácia po docs |
| **69** | Unified cache, Redis, `ETag`, `Last-Modified` | 🔴 | ⏳ | absorbuje staré It.45 a It.49 |
| **70** | Git publish — immediate a queued | 🟡 | ⏳ | používa scheduler/queue |
| **71** | Performance Guard APM | 🟡 | ⏳ | meranie latencie, I/O, pamäte a incidentov |
| **72** | Flysystem media drivers, S3/CDN | 🟡 | ⏳ | lokálny driver zostáva default |
| **73** | Viacjazyčný obsah v jednom dokumente | 🟡 | ⏳ | predpoklad pre asistované preklady |
| **74** | Aditívne API kľúče a JWT | 🟡 | ⏳ | admin session + CSRF bez zmeny |
| **75** | CMS-aware AI agent | 🔵 | ⏳ | návrhy s ľudským schválením, žiadny autonómny publish |
| **76** | Self-hosted asistovaný preklad | 🔵 | ⏳ | LibreTranslate / kompatibilný driver |
| **77** | Cloud asistovaný preklad | 🔵 | ⏳ | DeepL, Google a ďalšie ovládače |

Prehľad závislostí: [ITERATION_WAVE_HYBRID_ENGINE.md](ITERATION_WAVE_HYBRID_ENGINE.md).

---

## 5. Odporúčané poradie implementácie

### HE-1 — bezpečná abstrakcia úložiska

**It.68** musí vzniknúť pred ďalšími vrstvami:

- `StorageInterface` a kompatibilný lokálny driver,
- registry JSON schém pre dokumenty zapisované cez admin/Monaco,
- nastavenia `engine.*` s bezpečnými Classic defaultmi,
- migračné testy nad existujúcimi súbormi,
- rebuild a diagnostika bez SQL migrácie.

### HE-2 — výkon bez zmeny zdroja pravdy

**It.69** zjednotí cache a HTTP podmienené odpovede:

- memory/file/Redis drivers,
- bezpečný fallback pri nedostupnom Redis,
- invalidácia po úspešnom zápise,
- `ETag` a `Last-Modified`,
- test, že úplné vymazanie cache nestratí obsah.

### HE-3 — distribúcia a statický výstup

**It.70** zavedie Git publish workflow. **It.48** môže paralelne doplniť statický/Jamstack výstup, ale zdrojový dokument sa najprv bezpečne uloží lokálne.

### HE-4 — pozorovateľnosť

**It.71** a zostávajúci rozsah It.46 pridajú aplikačné a hostiteľské metriky. Performance Guard nesmie meniť obsah; môže iba merať, upozorňovať a vykonať dokumentované bezpečné fallbacky.

### HE-5 — médiá a headless prístup

**It.72** pridá médiové ovládače. **It.74** pridá scope-limited API keys/JWT. Obe vrstvy používajú rovnaké validátory, ACL a audit ako lokálny/session tok.

### HE-6 — lokalizovaný obsah a asistované workflow

Poradie je **It.73 → It.76/77 → It.75**. AI agent smie iba navrhovať zmeny; zápis a publikovanie vyžadujú explicitné potvrdenie oprávneného používateľa.

---

## 6. Paralelné prúdy

| Prúd | Stav | Kedy |
|------|------|------|
| **It.58d** — zostávajúce layout bloky/polish | ⏳ | po dokumentácii; môže bežať popri skorom Hybrid Engine |
| **It.67** — untrusted surfaces defense-in-depth | 🔴 | pred rozšírením importov, tém a generovaného kódu |
| **It.25** — setup wizard a zjednodušený update UX | 🟡 pre-Final | po stabilizácii beta feedbacku, pred 1.0 |
| **It.48** — static/dynamic render | 🟡 | zosúladiť s It.70, aby nevznikli dve publish pipeline |
| Komunitné beta testovanie | 🔴 | priebežne pred 1.0 |
| Dokumentácia a bezpečnostná revízia | 🔴 | pri každej vydanej vlne |

---

## 7. Cesta k Final 1.0

```text
Dvojjazyčná dokumentácia
    → It.68 foundation
    → prvé Hybrid Engine stabilizačné vydania
    → It.67 security gate
    → komunitná beta a opravy
    → It.25 onboarding/update UX
    → finálna dokumentácia + SECURITY_REVIEW
    → 1.0.0 GA
```

Final 1.0 **nie je podmienený dodaním všetkých It.68–77**. Rozsah GA musí byť uzamknutý samostatným release rozhodnutím. Minimálny gate:

- žiadne otvorené kritické bezpečnostné chyby,
- reprodukovateľná čistá inštalácia,
- funkčný backup/restore a diagnostika,
- zdokumentovaný cron a update postup,
- overený Classic fallback,
- vykonané beta smoke testy mimo vývojového prostredia autora.

---

## 8. Architektonické zákony

1. **No-SQL SSOT:** SQL ani dokumentová databáza nesmie byť autoritou obsahu alebo konfigurácie.
2. **Externý kód mimo Core:** pluginy a používateľský kód nesmú nekontrolovane rásť v `Core/`.
3. **API ↔ FE parita:** nový administrátorský endpoint má typovaný klient, oprávnenia, UI alebo explicitný dôvod, prečo je headless-only.
4. **Fail closed pri zápise:** neplatná schéma, cesta, oprávnenie alebo kód zastaví mutáciu.
5. **Odvodené vrstvy sú obnoviteľné:** index a cache sa dajú zahodiť a znovu vytvoriť.
6. **Classic fallback:** voliteľná služba nesmie byť skrytým povinným predpokladom základného CMS.
7. **Dokumentácia je súčasť release:** roadmapa, feature overview, API a changelog sa aktualizujú spolu s funkciou.

---

## 9. Definition of Done

Každá implementačná iterácia musí mať:

1. schválený rozsah a explicitné non-goals,
2. bezpečnostnú a No-SQL kontrolu návrhu,
3. backend kontrakt, validáciu a autorizáciu,
4. typovaný frontend alebo zdokumentovaný headless kontrakt,
5. PHPUnit, PHPStan L8, TypeScript, ESLint a Vitest gate,
6. migračný/fallback scenár,
7. aktualizované SK aj EN dokumenty,
8. changelog a release poznámky,
9. manuálny smoke test kritickej cesty.

---

## 10. Pravidlá údržby roadmapy

- Roadmapa obsahuje budúci smer a veľké míľniky, nie detailný incident log.
- Stav vydaných funkcií sa overuje podľa `CHANGELOG.md` a kódu, nie podľa starej plánovacej tabuľky.
- Číslo iterácie sa nesmie znovu použiť pre inú funkciu.
- Absorbovaný návrh sa označí ako „absorbed by“, nie ako samostatná aktívna iterácia.
- „Najnovšia verzia“ sa uvádza ako **najnovšie vydanie zachytené v dokumentačnom snapshot-e**, kým ju nepotvrdí nový release záznam.
