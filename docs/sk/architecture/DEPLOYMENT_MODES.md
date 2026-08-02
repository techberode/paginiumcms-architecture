# Režimy nasadenia — hostingové profily

> **Nadradený dokument:** [HYBRID_ENGINE.md](./HYBRID_ENGINE.md)  
> **Pravidlo:** všetky režimy používajú **No-SQL súborový zdroj pravdy** — [NOSQL_MANDATE.md](./NOSQL_MANDATE.md)

---

## Prehľad

Jeden kódový základ podporuje viac **profilov nasadenia**. Administrátor zvolí profil priamo alebo ho odporučí inštalátor podľa veľkosti projektu, pracovného postupu tímu a dostupnej infraštruktúry.

Profily nie sú samostatné produkty ani edície. Menia iba zapojenie ovládačov, cache, Git publish workflow a verejného renderovania.

| Profil | Typický projekt | Zdroj pravdy | Index | Cache | Git | Monitoring |
|--------|-----------------|--------------|-------|-------|-----|------------|
| **Blog / prezentačný web** | Osobná stránka, portfólio, vizitka | Lokálny disk | `content.json` | Súbor / APCu | Vypnutý | Základné logy |
| **Marketing / firemný web** | Firemná stránka, kampaň, produktový web | Lokálny disk | JSON index | APCu / Redis | Voliteľný okamžitý push | Upozornenia administrátora |
| **Spravodajstvo / portál** | Redakčný tím, časté zmeny, vysoká návštevnosť | Lokálny disk | JSON index | Redis + HTTP cache | Dávkový publish | Performance Guard + alerty |

---

## Režim A — Classic flat-file

> **Stav:** ✅ aktuálny predvolený režim

### Správanie

- CMS číta a zapisuje priamo do lokálneho súborového úložiska.
- Po úspešnom zápise sa aktualizuje alebo prebuduje index.
- Git automatizácia nie je potrebná.
- Redis nie je povinný.
- Verejný web používa REST API a React SPA.

### Vhodné použitie

- lacný VPS alebo zdieľaný hosting s podporovaným PHP,
- osobný web,
- jeden alebo malý počet editorov,
- vývojové a vzdelávacie inštalácie,
- self-hosting bez ďalších infraštruktúrnych služieb.

### Požiadavky

- PHP a závislosti projektu,
- zapisovateľné adresáre `storage/` a `data/`,
- web server alebo PHP development server,
- voliteľný cron pre plánovač a front úloh.

### Fallback

Ak chýbajú nové kľúče `engine.*`, systém sa musí správať ako Classic. Tento režim je kompatibilný základ a bezpečný fallback ostatných profilov.

---

## Režim B — Hybrid

> **Stav:** ⏳ cieľ It.68–70

### Správanie

- Zdroj pravdy zostáva na lokálnom disku rovnako ako v Classic režime.
- Čítanie používa index a read-through cache.
- Cache môže byť súborová, APCu alebo Redis.
- Po publikovaní možno voliteľne vykonať okamžitý Git commit a push.
- Verejný web môže zostať dynamický alebo kombinovať dynamické API so statickými časťami.

### Vhodné použitie

- marketingové a firemné weby,
- malé tímy s CI/CD,
- projekty, ktoré chcú rýchle čítanie bez zmeny No-SQL kontraktu,
- nasadenia s voliteľným Redis sidecarom,
- obsah distribuovaný do repozitára alebo build pipeline.

### Požiadavky

Všetko z Classic režimu a podľa konfigurácie:

- APCu alebo Redis,
- Git remote,
- deploy key alebo bezpečne uložený token,
- webhook, GitHub Actions alebo vlastný build proces.

### Bezpečnostné pravidlá

- Git poverenia sa ukladajú šifrovane.
- Push sa vykonáva až po úspešnom lokálnom zápise.
- Zlyhanie Git push nesmie odstrániť lokálny obsah.
- Audit rozlišuje „uložené“, „publikované lokálne“ a „distribuované cez Git“.

---

## Režim C — Git-headless / Jamstack

> **Stav:** ⏳ cieľ It.70 + It.48

### Správanie

- Editor ukladá do súborového SSOT v lokálnom worktree alebo kontrolovanom checkout adresári.
- Zmeny možno publikovať okamžite alebo zaradiť do dávky.
- Pri dávkovom režime hlavný editor vytvorí jeden konzistentný commit a push.
- Build hook spustí generovanie statického webu alebo aktualizáciu headless klienta.
- Verejný web môže byť statický HTML výstup, API-driven SPA alebo hybrid oboch prístupov.

### Vhodné použitie

- redakcie a viacstupňové schvaľovanie,
- weby s vysokou návštevnosťou,
- oddelená CMS a prezentačná infraštruktúra,
- CDN-first a Jamstack nasadenia,
- projekty, ktoré chcú obsah verzovať a distribuovať cez Git workflow.

### Požiadavky

Všetko z Hybrid režimu a navyše:

- plánovač a job queue — It.29 je už implementovaná,
- bezpečný Git worker,
- build alebo deploy pipeline,
- definovaná stratégia konfliktov,
- monitoring neúspešných publish úloh.

### Autorita dát

Git môže byť distribučná alebo replikačná vrstva, ale dokumentácia konkrétneho nasadenia musí jasne určiť autoritatívny worktree. Systém nesmie vytvoriť dve nezdokumentované autority, ktoré sa navzájom prepisujú.

---

## Plánované nastavenia

| Kľúč | Hodnoty | Iterácia |
|------|---------|----------|
| `engine.deploymentMode` | `classic` \| `hybrid` \| `git_headless` | It.68 |
| `engine.cache.driver` | `auto` \| `file` \| `redis` \| `memory` | It.69 |
| `engine.git.enabled` | `true` \| `false` | It.70 |
| `engine.git.publishStrategy` | `immediate` \| `queued` | It.70 |
| `engine.git.remote` | URL alebo pomenovaný remote | It.70 |
| `engine.git.branch` | názov vetvy | It.70 |
| `site.renderMode` | `dynamic` \| `static` \| `hybrid` | It.48 |
| `engine.performanceGuard.enabled` | `true` \| `false` | It.71 |

Predvolené hodnoty musia pri chýbajúcich kľúčoch zachovať Classic správanie cez bezpečné `??` fallbacky.

---

## Rozhodovacia pomôcka

| Otázka | Odporúčanie |
|--------|-------------|
| Chceš minimum služieb a jednoduchý self-hosting? | **Classic** |
| Potrebuješ Redis alebo automatický Git push, ale verejný web zostáva dynamický? | **Hybrid** |
| Obsah má prechádzať cez commit, review a externý build? | **Git-headless** |
| Nie je Redis dostupný? | Classic alebo Hybrid so súborovou/APCu cache |
| Nie je Git dostupný? | Classic; prípadne Hybrid bez Git distribúcie |
| Nie je nakonfigurovaný worker? | Nepovoľuj queued publish |

---

## Nginx a Docker

Hostiteľský nginx môže naďalej obsluhovať statický frontend `dist/`, zatiaľ čo PHP API beží v kontajneri alebo cez PHP-FPM. Zmena režimu nevyžaduje automaticky inú topológiu nginxu.

Rozdiely vznikajú najmä v:

- premenných prostredia,
- nastaveniach `engine.*`,
- voliteľnom Redis sidecare,
- Git workerovi,
- build hooku a statickom výstupe.

Podrobnosti: [../deploy/DEPLOY.md](../deploy/DEPLOY.md).

---

## Migrácia medzi režimami

1. Vytvor plnú zálohu a over jej obnovu.
2. Spusť diagnostiku obsahu a oprav poškodené dokumenty.
3. Prebuduj index a vyčisti starú cache.
4. Zmeň `engine.deploymentMode`.
5. Aktivuj nový cache driver a over fallback bez neho.
6. Pri Git režime nastav testovací remote alebo neprodukčnú vetvu.
7. Over prvý commit/push bez produkčného build hooku.
8. Až potom zapni automatickú distribúciu a monitoring.

SQL migračné skripty nie sú potrebné — žiadna databáza nie je zdrojom pravdy.

---

## Návrat na predchádzajúci režim

Rollback musí byť možný konfiguráciou:

- vypnúť Git publish,
- zastaviť worker,
- prepnúť cache na `file` alebo ju úplne vyčistiť,
- nastaviť `engine.deploymentMode=classic`,
- prebudovať index,
- overiť lokálne čítanie a zápis.

Zmena režimu nesmie vyžadovať konverziu dokumentov do iného formátu.

---

## Súvisiace dokumenty

- [HYBRID_ENGINE.md](./HYBRID_ENGINE.md) — cieľová architektúra
- [NOSQL_MANDATE.md](./NOSQL_MANDATE.md) — nemenné dátové pravidlo
- [../deploy/DEPLOY.md](../deploy/DEPLOY.md) — produkčný runbook
- [../ITERATION_70.md](../ITERATION_70.md) — implementácia Git publish
- [../ITERATION_48.md](../ITERATION_48.md) — statický výstup
