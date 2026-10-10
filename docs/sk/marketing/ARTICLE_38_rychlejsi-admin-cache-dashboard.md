---
title: "Rýchlejší admin: dashboard, úlohy a plánovač bez zbytočného čakania"
slug: rychlejsi-admin-cache-dashboard
perex: "PaginiumCMS ukladá ťažké prehľady adminu do cache s obnovou na pozadí a ponúka cron warm-up — menej sekúnd pri otvorení dashboardu na jednej inštancii."
kategoria: Vylepšenia
tagy: paginiumcms, administracia, vykon, dashboard, cache, docker
serie: "PaginiumCMS po beta.96"
serie_diel: 38
release_anchor: "Unreleased (Iteration 100)"
status: draft
---

# Rýchlejší admin: dashboard, úlohy a plánovač bez zbytočného čakania

*Naväzuje na diel 37 (koncepty v editore). Tu riešime **otvorenie administrácie** keď naraz pracuje viac ľudí alebo viac panelov.*

Flat-file CMS môže byť na jednom serveri veľmi rýchly — ale **dashboard** agreguje audit, frontu úloh, APM a plánovač projektu. Pri prvom načítaní po reštarte alebo pri súbehu requestov sa latencia ukáže v logu aj v pocite „pomalého adminu“.

Iteration **100** pridáva vrstvu **P2 cache** (Redis alebo súborová obálka): odpoveď sa vráti hneď, ak existuje čerstvá alebo mierne zastaraná kópia; na pozadí sa prepočíta pravda z disku.

## Čo redaktor pocíti

- **Dashboard** a súvisiace widgety sa načítajú skôr, najmä po druhom otvorení alebo keď beží **cron warm-up**.
- Veľké JSON odpovede (napr. prehľad úloh) môžu byť **gzip** v nginx — menej dát cez sieť.
- Frontend **odloží** niektoré sekundárne panely (React Query), aby prvá obrazovka nečakala na všetko naraz.

## Čo prevádzka spraví raz

Na serveri s Dockerom / PHP 8.5 odporúčame minútový cron (rovnaká PHP verzia ako worker):

```bash
cd /cesta/k/paginiumcms && php8.5 backend/bin/console cache:warm-admin
```

Príkaz je **idempotentný** — neprepisuje obsah stránok, len predpočíta admin prehľady.

## Čo nepretvaráme

- Pri **viacerých paralelných requestoch** na jednu session (viac tabov) môže PHP ešte sekvenčovať session lock — to je samostatná téma ladenia.
- **Desk** (komentáre, správy) bol zrýchlený samostatnou opravou (menej duplicitnej práce na serveri) — diel 39 to spomína stručne.

Interné ladenie: [ITERATION_100.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/ITERATION_100.md), simulácia load probe v [ADMIN_LOAD_SIMULATION.md](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/developer/ADMIN_LOAD_SIMULATION.md) (pre prevádzkovateľov, nie koncový článok).

<!-- SCREENSHOT: Dashboard s načítanými kartami; voliteľne Performance Guard ak je viditeľný. -->

**CTA ďalší diel:** [Stôl a prihlásenie: menej trenia](stol-csrf-vykon-redakcia.md).
