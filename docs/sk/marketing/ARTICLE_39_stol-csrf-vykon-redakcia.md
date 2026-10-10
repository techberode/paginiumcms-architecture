---
title: "Stôl, komentáre a spoľahlivejšie ukladanie po prihlásení"
slug: stol-csrf-vykon-redakcia
perex: "Menšie úpravy, ktoré šetria nervy: rýchlejší stôl pri komentároch, menej falošných CSRF chýb po login-e a plynulejšia práca v editore."
kategoria: Vylepšenia
tagy: paginiumcms, stol, komentare, bezpecnost, csrf, vykon
serie: "PaginiumCMS po beta.96"
serie_diel: 39
release_anchor: "Unreleased"
status: draft
---

# Stôl, komentáre a spoľahlivejšie ukladanie po prihlásení

*Naväzuje na diel 38 (cache adminu). Tu tri **jemné** zmeny, ktoré sa nepredávajú ako veľká funkcia, ale v praxi znížia počet „prečo mi to neuložilo?“.*

## Rýchlejší stôl (desk)

Poll `/api/auth/me/desk` beží v admin rozhraní pravidelne. Pri väčšom počte komentárov server predtým opakovane prechádzal rovnaké dáta. Úprava **indexuje odpovede** a skladá payload **raz na request** — izolované merania na produkcii ukázali desk v **desiatkach ms**, zatiaľ čo pri **burst** viacerých panelov môže dashboard stále chvíľu čakať vo fronte PHP-FPM (súvisí s dielom 38).

Frontend navyše **nepolluje desk**, keď je karta prehliadača skrytá — menej zbytočnej práce na pozadí.

## CSRF token hneď po prihlásení

React admin posiela mutácie s hlavičkou `X-CSRF-TOKEN`. Po login-e alebo 2FA sa token **obnoví**, aby prvý POST (náhľad, uloženie, nastavenia) neskončil `403 csrf_invalid`. V logu ide o **INFO**, nie alarm — ale redaktor uvidí úspech namiesto tichého zlyhania.

Detaily pre vývojárov: [SECURITY.md §10.1](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/developer/SECURITY.md), [ISS-196](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/ISSUES.md#iss-196).

## Ako to spojiť v článku pre verejnosť

Nepísať o PHP-FPM ani N+1. Písať:

> „Odpovede stola a prvé akcie po prihlásení sú stabilnejšie; tím môže sústrediť pozornosť na obsah.“

<!-- SCREENSHOT: Stôl s frontou komentárov; bez reálnych e-mailov a mien. -->

**CTA:** Späť na [Neuložený koncept v editore](neulozeny-obsah-autosave-koncept.md) alebo na sériu [ARTICLE_SERIES](../../marketing/ARTICLE_SERIES_SK.md) (lokálne v `docs/marketing/`).
