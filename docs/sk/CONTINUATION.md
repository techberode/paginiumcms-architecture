# PaginiumCMS — kontext pre pokračovanie vývoja

> **Účel:** stručný a aktuálny handoff pre ďalšiu vývojovú reláciu  
> **Checkpoint:** 2. august 2026 · `v2.1.0-beta.23`  
> **Aktívne rozhodnutie:** kód It.68+ je pozastavený, kým nebude hotová dvojjazyčná dokumentácia

Tento dokument nahrádza starý chronologický „denník všetkého“. Historické podrobnosti zostávajú v [`CHANGELOG.md`](../../CHANGELOG.md), [`ISSUES.md`](ISSUES.md) a jednotlivých `ITERATION_*.md`.

---

## 1. Jednou vetou

PaginiumCMS je **No-SQL Hybrid Headless Content Engine**: React/Vite administrácia a verejný web komunikujú cez Slim REST API s PHP Core, pričom JSON/Markdown/YAML súbory zostávajú povinným zdrojom pravdy.

---

## 2. Aktuálny stav

| Oblasť | Stav |
|--------|------|
| Posledné zdokumentované vydanie | ✅ `v2.1.0-beta.23` — It.58c Layout Switch |
| Public Beta základ | ✅ funkčný a priebežne hardenovaný |
| Hybrid Engine Fáza 0 | ✅ architektúra, No-SQL mandát a deployment profily |
| Dvojjazyčná dokumentácia | 🚧 spracováva sa po tematických iteráciách |
| It.68 implementácia | ⏸️ čaká na docs gate |
| It.69–77 | ⏳ naplánované |
| It.58d, It.67, It.25 | ⏳ paralelný / pre-Final backlog |

**Dôležité:** „najnovšie“ v tomto dokumente znamená najnovšie vydanie zaznamenané v zdrojovom balíku z 2. augusta 2026.

---

## 3. Nemenné pravidlá

Pri pokračovaní vývoja sa nesmú porušiť tieto invarianty:

1. **Súbory sú SSOT.** Redis, cache, index, Git remote ani S3 nie sú autoritou primárneho obsahu.
2. **Classic režim funguje bez voliteľných služieb.** Nový driver musí mať lokálny fallback alebo jasné bezpečné zlyhanie.
3. **Zápisy idú cez doménové služby.** Žiadny endpoint ani plugin nesmie priamo obísť validáciu, ACL, audit a bezpečný zápis.
4. **Admin auth zostáva session + CSRF.** Headless auth v It.74 je aditívny.
5. **Nedôveryhodný kód je fail-closed.** Importy, Monaco, pluginy, témy a generovaný kód musia prejsť policy/schema gate.
6. **Dokumentácia a kód majú rovnaký stav.** Plánované funkcie sú `⏳`; čiastočné sú `🟡`; hotové až po end-to-end gate.
7. **SK a EN sa menia spolu.** Názvy tried, endpointov, ciest a konfiguračných kľúčov zostávajú identické.

---

## 4. Čo je hotové a možno na tom stavať

### Obsahové jadro

- CRUD stránok a článkov nad súbormi,
- indexovanie a stránkovanie,
- zámky, heartbeat, auto-save a revízie,
- OCC a HTTP 409 konflikty,
- 3-way merge a konflikt resolver,
- plánovaná publikácia cez scheduler,
- SEO metadata, tagy, filtre a verejný blog.

### Platforma

- session autentifikácia, 2FA, RBAC a ACL,
- nastavenia s encrypted secret fields,
- audit, logy, WAF, rate limiting a bezpečnostné middleware,
- job scheduler, worker, zálohy, koš a diagnostika,
- externé pluginy, hooky, Code Policy a Developer Mode,
- Docker/release/deploy základ.

### Frontend a verejný web

- React SPA s admin a verejnými routami,
- SK/EN i18n,
- Markdown a Tiptap editorové profily,
- DAM, navigácia, komentáre, kontakt, newsletter a galéria,
- systémový update UI a demo režim.

Inventár: [FEATURE_OVERVIEW.md](FEATURE_OVERVIEW.md).

---

## 5. Najbližší implementačný cieľ — It.68

It.68 je foundation iterácia, nie vizuálna feature. Pred prvým commitom musí byť potvrdené:

- hranica medzi `StorageInterface` a existujúcimi repository službami,
- kompatibilita so súčasnými cestami a súbormi,
- bezpečný lokálny driver ako default,
- registry schém a správanie pri neplatnom legacy dokumente,
- engine settings a ich Classic defaults,
- postup index rebuild/diagnose,
- nulová potreba SQL migrácie.

### Očakávaný výstup It.68

1. kontrakty a lokálny driver,
2. integračné testy čítania/zápisu existujúceho obsahu,
3. schema registry pre dokumenty zapisované z administrácie,
4. settings schema + admin zobrazenie bez aktivácie nehotových driverov,
5. migrácia „in place“ alebo kompatibilný adapter,
6. aktualizované architektonické, API, testing a user dokumenty.

---

## 6. Poradie po It.68

```text
It.68 storage/schema/settings
  └─► It.69 cache + Redis + HTTP validators
        ├─► It.70 Git publish
        ├─► It.71 Performance Guard
        └─► It.72 media drivers
              └─► It.73 locale document
                    ├─► It.76 LibreTranslate
                    ├─► It.77 cloud translation
                    └─► It.75 AI agent

It.74 API keys/JWT môže začať po stabilnom It.68 auth/storage kontrakte.
```

Paralelne sa môžu riešiť It.58d a It.67, ale nesmú meniť tie isté abstrakcie bez koordinácie.

---

## 7. Pracovný postup jednej iterácie

1. Prečítať `.cursorrules`, roadmapu, príslušnú špecifikáciu a súvisiace incidenty.
2. Overiť aktuálny tag/commit a reálne testovacie príkazy.
3. Zapísať scope, non-goals a závislosti.
4. Najprv navrhnúť kontrakt a bezpečnostné hranice.
5. Implementovať backend s testami.
6. Doplniť typovaný FE klient a UI alebo headless príklad.
7. Spustiť celý gate.
8. Vykonať manuálny smoke test.
9. Aktualizovať SK/EN dokumentáciu, API a changelog.
10. Až potom tag/release.

### Quality gate

```bash
composer gate
# alebo explicitne:
composer test
composer stan
composer cs

cd frontend
npm run type-check
npm run lint
npm run lint:api-barrel
npm test
```

Skutočné skripty v `composer.json` a `package.json` majú prednosť pred dokumentačným príkladom.

---

## 8. Kontroly pred zásahom do úložiska

Pri každej zmene storage/cache/publish vrstvy odpovedz:

- Ktorý súbor je autorita?
- Je zápis atomický?
- Čo sa stane, ak po zápise zlyhá index?
- Čo sa stane, ak Redis/Git/S3 neodpovedá?
- Dá sa odvodená vrstva kompletne zmazať a obnoviť?
- Prejde operácia rovnakým ACL, sanitizáciou a auditom?
- Je možné vrátiť sa do Classic režimu bez migrácie dát?

Ak odpoveď nie je jasná, implementácia ešte nie je pripravená.

---

## 9. Aktívne otvorené rozhodnutia

| Téma | Potrebné rozhodnutie |
|------|----------------------|
| Licencia | zosúladiť deklarovanú open-source filozofiu a požadované komerčné obmedzenia so skutočným `LICENSE` |
| Rozsah Final 1.0 | určiť, ktoré Hybrid Engine iterácie sú GA blocker a ktoré môžu zostať post-1.0 |
| It.48 vs It.70 | jedna publish pipeline, nie dve konkurenčné implementácie |
| It.58d | presne ohraničiť zvyšok po dodaných 58b/58c |
| It.67 | určiť povinný gate pre import tém, shortcode a generovaný kód |
| Komunitná beta | získať externé smoke testy, nielen maintainer QA |

---

## 10. Čomu sa vyhnúť

- neobnovovať staré priority z archívnych sekcií `CONTINUATION.md`,
- nevytvárať nové „Iteration 30/31…“ pre číslo, ktoré už má inú históriu,
- neoznačovať existujúci GitHub content sync ako hotový Git publish engine,
- neprezentovať file cache ako hotový Redis layer,
- neukladať API kľúče alebo cloud secrets v plaintext,
- nerobiť priamy zápis do content súborov z Reactu, pluginu alebo AI agenta,
- nevydávať release bez aktualizácie oboch jazykových vydaní.

---

## 11. Ready-to-paste pokračovací brief

```text
Pokračujeme vo vývoji PaginiumCMS.

Checkpoint dokumentácie: 2026-08-02
Posledné vydanie zachytené v dokumentoch: v2.1.0-beta.23
Smer: Hybrid Headless Content Engine, povinný No-SQL file SSOT.
Stack: React + TypeScript + Vite ↔ Slim REST API ↔ PHP 8.5 Core.

Aktuálne:
- Fáza 0 architektúry je zdokumentovaná.
- Prebieha dvojjazyčná SK/EN konsolidácia dokumentácie.
- Implementácia It.68+ je do ukončenia docs gate pozastavená.

Najbližší kód:
- It.68: StorageInterface, lokálny driver, schema registry, engine settings,
  kompatibilita existujúcich súborov a rebuild diagnostika.

Povinné zákony:
- súbory sú jediný zdroj pravdy,
- Classic režim musí fungovať bez Redis/Git/S3,
- session + CSRF administrácie sa nemenia,
- všetky zápisy prechádzajú validáciou, ACL, auditom a bezpečným writerom,
- SK a EN dokumentácia sa aktualizuje spolu.

Pred implementáciou over aktuálny git tag, CHANGELOG, príslušný ITERATION dokument,
.cursorrules a reálne test scripts. Po zmene spusti plný gate a smoke test.
```

---

*Pri ďalšom update nech zostane tento súbor krátky a aktuálny. Staré handoff bloky patria do histórie vydaní, nie do aktívneho briefingu.*
