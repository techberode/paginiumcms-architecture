# PaginiumCMS — filozofia a dôvod vzniku

> Hlavná myšlienka projektu. Platí pre všetky iterácie, nasadenia a budúci vývoj.

---

## Prečo PaginiumCMS existuje

PaginiumCMS vznikol ako **malý kľúč do veľkého sveta vývoja webových aplikácií**.

Cieľ nie je predať ďalší „CMS balík“, ale ukázať **celú cestu** — od flat-file úložiska cez REST API až po React admin — tak, aby sa dalo **učiť, skúšať a rozumieť**, nie len kliknúť a nevedieť, čo sa deje pod kapotou.

Projekt je zároveň **laboratórium**: každá iterácia pridáva reálne zručnosti (auth, RBAC, cache, feeds, SEO, WAF, blueprinty…) v jednom ucelenom repozitári, ktorý môžeš forknúť, prečítať a prispôsobiť.

---

## Základné zásady (nemeniteľné)

| Zásada | Význam |
|--------|--------|
| **100 % open source** | Kód je verejný, auditovateľný, forkovateľný. Žiadne uzavreté „jadro“. |
| **Bez poplatkov** | PaginiumCMS sa **nepredáva** a **nesmie byť ponúkané ako platené riesenie** (SaaS za licenciu, white-label za odmenu, „Pro“ edícia atď.). |
| **Vlastník obsahu** | Flat-file first — dáta sú tvoje súbory, nie ruky dodávateľa. |
| **Transparentnosť** | API first, dokumentácia, testy — aby bolo jasné, *ako* to funguje. |
| **Spolupráca** | Projekt rastie v tíme — ľudský smer + implementácia v kóde; rozhodnutia a história patria repozitáru a komunite. |

Tieto body nie sú marketing — sú **smerovaním repozitára**. Ak by niekto PaginiumCMS balil ako komerčný produkt za peniaze, išlo by to proti pôvodnému účelu projektu.

---

## Čo PaginiumCMS **nie je**

- ❌ Platená platforma ani „freemium“ s paywallom  
- ❌ Uzavretý hosting, kde nevidíš kód  
- ❌ Black box, kde admin funguje, ale nikto nevie prečo  
- ❌ Demo modul ako predajný feature v zákazníckom balíku  

---

## Demo subdoména = predvádzacie vozidlo

`demo.paginiumcms.com` je **trenažér / predvádzacie auto**:

- **Nedá sa kúpiť** — slúži len na vyskúšanie, čo CMS dokáže.  
- **Nie je súčasť zákazníckeho balíka** — zákaznícka produkcia beží bez `DEMO_MODE`.  
- Po skúške sa prostredie **resetuje** — ďalší návštevník dostane čistú jazdu.  

Rovnaký open-source kód; iný účel nasadenia. Detail: [ITERATION_13.md](ITERATION_13.md).

---

## Technické smerovanie (ako to podporuje myšlienku)

- **Flat-file** — vidíš obsah v súboroch, nie v cudzej databáze.  
- **Modularita** — Core tenký, funkcie v moduloch; dá sa študovať po častiach.  
- **Iterácie** — každá kapitola (It.5 auth, It.19 index, It.21 API kontrakt…) je učebnica + kód.  
- **Docs first** — `ROADMAP`, `ITERATION_*.md`, architektúra — aby projekt slúžil aj na učenie.  

---

## Pre koho je projekt

- Pre **vývojárov**, ktorí chcú pochopiť moderný full-stack (PHP + React + flat-file).  
- Pre **tvorcov obsahu**, ktorí chcú vlastniť dáta na disku.  
- Pre **komunitu**, ktorá môže prispievať bez licenčných prekážok.  

---

## Súvisiace dokumenty

- [README.md](README.md) — stav projektu a architektúra  
- [ROADMAP.md](ROADMAP.md) — iterácie a smer vývoja  
- [CONTINUATION.md](CONTINUATION.md) — kde sme a čo ďalej  
- [ITERATION_13.md](ITERATION_13.md) — demo sandbox (iba subdoména)  

---

*Táto stránka je referenčný bod pri rozhodnutiach: ak návrh odporuje open-source a bezplatnému poslaniu, nepatrí do jadra PaginiumCMS.*
