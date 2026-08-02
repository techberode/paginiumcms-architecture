# PaginiumCMS — filozofia a dôvod vzniku

> Hlavná myšlienka projektu. Platí pre všetky iterácie, režimy nasadenia a budúci vývoj.

---

## Prečo PaginiumCMS existuje

PaginiumCMS vznikol ako **malý kľúč do veľkého sveta vývoja webových aplikácií**.

Cieľom nie je vytvoriť ďalší nepriehľadný „CMS balík“, ale ukázať **celú cestu** — od súborového úložiska cez REST API až po React administráciu — tak, aby sa na projekte dalo **učiť, experimentovať a rozumieť mu**, nielen klikať bez znalosti toho, čo sa deje pod kapotou.

Projekt je zároveň praktické **vývojové laboratórium**. Každá iterácia pridáva reálne témy, napríklad autentifikáciu, RBAC, indexovanie, cache, feedy, SEO, WAF, blueprinty, plánovač úloh alebo bezpečnostné kontroly. Všetko zostáva v jednom ucelenom repozitári, ktorý možno čítať, testovať, forknúť a prispôsobiť.

PaginiumCMS sa od augusta 2026 neposudzuje iba ako „flat-file CMS“. Cieľom je **Hybrid Headless Content Engine**: API-first systém, ktorý zachováva súbory ako zdroj pravdy a pridáva nad nimi profesionálne vrstvy pre výkon, distribúciu, spoluprácu a automatizáciu.

---

## Nemenné zásady

| Zásada | Význam |
|--------|--------|
| **No-SQL je povinné** | SQL ani externá dokumentová databáza nesmie byť primárnym zdrojom pravdy pre obsah, používateľov, konfiguráciu alebo prevádzkový stav CMS. Dáta zostávajú v JSON, Markdown a YAML súboroch. Redis, APCu a pamäťové vrstvy sú iba odvodená cache alebo dočasná koordinácia. Podrobnosti: [architecture/NOSQL_MANDATE.md](architecture/NOSQL_MANDATE.md). |
| **Súbory sú zdroj pravdy** | Obsah a konfigurácia musia zostať prenositeľné, čitateľné a obnoviteľné bez povinnej externej databázovej služby. |
| **Otvorený zdrojový kód** | Jadro, moduly a mechanizmy systému majú zostať verejne čitateľné, auditovateľné a upraviteľné v rozsahu, ktorý určuje licencia repozitára. |
| **Oficiálny projekt zostáva bezplatný** | PaginiumCMS nemá smerovať k uzavretému jadru, platenej „Pro“ edícii ani paywallu nad základnými funkciami. Právne podmienky používania, redistribúcie a prípadného komerčného využitia vždy určuje súbor `LICENSE`. |
| **API First** | Každá dôležitá administrátorská operácia má mať definovaný API kontrakt a nemá byť dostupná iba cez používateľské rozhranie. |
| **Security by Design** | Autentifikácia, autorizácia, validácia, šifrovanie citlivých údajov a audit patria do jadra návrhu, nie do dodatočného zoznamu opráv. |
| **Tenké jadro a moduly** | Core poskytuje stabilné rozhrania a bezpečnostné pravidlá. Funkcie sa majú rozširovať cez moduly, ovládače, hooky a jasné kontrakty. |
| **Dokumentácia je súčasť produktu** | Rozhodnutia, obmedzenia, stav implementácie a migračné kroky musia byť dohľadateľné v repozitári. |
| **Spolupráca a história** | Smer projektu vzniká kombináciou ľudských rozhodnutí, implementácie, testov a spätnej väzby. Dôležité rozhodnutia nemajú zostať iba v chate alebo v hlave autora. |

Tieto zásady nie sú marketingový slogan. Tvoria **rozhodovací rámec projektu**. Nová funkcia môže zmeniť implementáciu, nesmie však potichu zmeniť zdroj pravdy, bezpečnostný model alebo otvorený charakter jadra.

> **Poznámka k licencii:** filozofia opisuje zámer oficiálneho projektu. Právne práva a obmedzenia určuje výlučne aktuálny súbor `LICENSE` v repozitári. Dokumentácia preto nesmie sľubovať právne obmedzenie, ktoré licencia neobsahuje.

---

## Čo PaginiumCMS nie je

- ❌ Povinná cloudová služba, bez ktorej obsah prestane fungovať
- ❌ SQL aplikácia iba prezlečená za flat-file CMS
- ❌ Uzavretý hosting, v ktorom používateľ nevidí kód ani vlastné dáta
- ❌ Black box s administráciou bez vysvetliteľného API a dátového modelu
- ❌ Produkt s uzavretým jadrom a plateným odomknutím základných funkcií
- ❌ Jednoduchý „blog načítaný zo súboru pri každej návšteve“ bez indexu, cache, zámkov a kontroly súbehu

---

## Demo subdoména je predvádzacie prostredie

`demo.paginiumcms.com` je **trenažér a ukážková inštancia**:

- slúži na vyskúšanie možností CMS,
- nepovažuje sa za produkčné úložisko používateľských dát,
- môže sa pravidelne resetovať do čistého stavu,
- používa rovnaký open-source kód, ale iný režim nasadenia,
- bezpečnostné obmedzenia režimu `DEMO_MODE` sa nesmú automaticky preniesť do produkcie.

Podrobnosti: [ITERATION_13.md](ITERATION_13.md).

---

## Nové technické smerovanie — Hybrid Headless Content Engine

PaginiumCMS **neopúšťa No-SQL princíp**. Rozširuje pôvodný flat-file základ na vrstvený content engine:

1. **Flat-File Core** — fyzické JSON, Markdown a YAML súbory zostávajú jediným zdrojom pravdy.
2. **Storage abstraction** — stabilné rozhranie oddelí doménovú logiku od konkrétneho spôsobu čítania a zápisu.
3. **Index** — agregované metadáta zrýchľujú zoznamy, vyhľadávanie a filtrovanie bez skenovania všetkých súborov.
4. **Cache** — súborová cache, APCu alebo Redis zrýchľujú čítanie; cache musí byť kedykoľvek obnoviteľná.
5. **API layer** — REST API rieši autentifikáciu, oprávnenia, validáciu, konflikty a jednotné odpovede.
6. **Git distribution** — obsah možno publikovať okamžite alebo dávkovo cez commit/push workflow.
7. **Observability** — meranie latencie, pamäte, I/O a chybovosti pomáha chrániť výkon enginu.
8. **Multilingual and AI-assisted workflows** — viac jazykov a asistované preklady sa budujú nad súborovým dokumentovým modelom, nie nad povinnou databázou.

| Dokument | Účel |
|----------|------|
| [architecture/HYBRID_ENGINE.md](architecture/HYBRID_ENGINE.md) | Cieľová architektúra a vrstvy enginu |
| [architecture/DEPLOYMENT_MODES.md](architecture/DEPLOYMENT_MODES.md) | Klasický, hybridný a Git-headless režim |
| [architecture/NOSQL_MANDATE.md](architecture/NOSQL_MANDATE.md) | Nemenné pravidlo súborového zdroja pravdy |
| [ITERATION_WAVE_HYBRID_ENGINE.md](ITERATION_WAVE_HYBRID_ENGINE.md) | Implementačná vlna It.68–77 |
| [ROADMAP.md](ROADMAP.md) | Celková mapa vývoja |

**Implementácia nového smerovania pokračuje až po dokončení a kontrole dvojjazyčnej dokumentácie**, aby slovenská a anglická verzia nevytvárali dve odlišné predstavy o tom istom produkte.

---

## Historické princípy, ktoré zostávajú platné

- **Flat-file first** — dáta sú viditeľné a prenositeľné ako súbory.
- **Modularita** — Core zostáva úzky a funkcie sa rozvíjajú v moduloch.
- **Iteračný vývoj** — každá väčšia funkcia má vlastný návrh, implementáciu, testy a dokumentáciu.
- **Docs first** — architektúra a rozhodnutia sa dokumentujú pred rozsiahlym zásahom do kódu.
- **Testovateľnosť** — automatické testy a statická analýza sú súčasťou definície hotovej práce.

---

## Pre koho je projekt

- Pre **vývojárov**, ktorí chcú pochopiť moderný full-stack v PHP a Reacte.
- Pre **správcov a self-hosterov**, ktorí chcú vlastniť obsah aj prevádzkové dáta.
- Pre **tvorcov obsahu**, ktorí potrebujú administráciu bez odovzdania zdroja pravdy cudzej platforme.
- Pre **komunitu**, ktorá môže auditovať, testovať a rozširovať projekt cez zdokumentované rozhrania.

---

## Súvisiace dokumenty

- [README.md](README.md) — aktuálny stav a vstup do dokumentácie
- [ROADMAP.md](ROADMAP.md) — iterácie a smer vývoja
- [CONTINUATION.md](CONTINUATION.md) — kontext pokračovania projektu
- [architecture/HYBRID_ENGINE.md](architecture/HYBRID_ENGINE.md) — cieľový Hybrid Engine
- [architecture/NOSQL_MANDATE.md](architecture/NOSQL_MANDATE.md) — No-SQL pravidlo
- [ITERATION_13.md](ITERATION_13.md) — demo sandbox

---

*Táto stránka je referenčný bod pri architektonických rozhodnutiach. Návrh, ktorý potichu zavádza povinnú databázu, uzatvára jadro alebo obchádza bezpečnostné kontrakty, nepatrí do hlavnej línie PaginiumCMS.*
