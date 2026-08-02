---
title: Logy a audit
description: Prevádzkové logy, request korelácia, retencia, redakcia údajov a bezpečná diagnostika
icon: material/text-box-search
---

# Logy — príručka administrátora

> Logy pomáhajú vysvetliť správanie systému. Nie sú autoritatívnym obsahom, nemajú obsahovať secrets a nie sú náhradou doménového auditu.

## 1. Typy záznamov

| Zdroj | Účel |
|---|---|
| application | chyby a významné runtime udalosti |
| HTTP/request | method, path, status, duration a korelačné metadata |
| audit | kto vykonal významnú zmenu nad akým cieľom |
| security/event | lockout, WAF, backup, worker a systémové udalosti |
| user/activity | používateľské aktivity iba v povolenom rozsahu |

Konkrétne adresáre a názvy zdrojov over v release. Všetky log adresáre musia byť mimo priameho webového prístupu.

## 2. Request log

Bezpečný request záznam typicky obsahuje:

- UTC timestamp,
- method a normalizovanú path,
- status,
- `duration_ms`,
- client IP podľa trusted proxy policy,
- user ID alebo service principal, ak je autentifikovaný,
- request/correlation ID,
- route name alebo kategóriu.

Nemá obsahovať celé session cookie, Authorization bearer, CSRF token, heslo, TOTP, API key alebo celé citlivé body.

## 3. Severity

| Úroveň | Použitie |
|---|---|
| `debug` | lokálna diagnostika; produkcia iba obmedzene |
| `info` | normálna významná udalosť |
| `warning` | očakávaný problém, 4xx vzor, slow request |
| `error` | zlyhanie jednej operácie |
| `critical` | strata dostupnosti, integrity alebo zásadnej capability |

Nie každá 404 je warning hodný dlhodobej retencie. WAF probe môže byť security event; bežná neexistujúca stránka nemusí zaplaviť error log.

## 4. Audit versus request log

Audit má odpovedať:

```text
kto → čo urobil → nad čím → kedy → s akým výsledkom
```

Request log odpovedá na technický priebeh HTTP volania. Jedna admin akcia môže mať viac requestov, ale jeden doménový audit záznam. Audit export musí chrániť CSV/Formula injection a redigovať citlivý context.

## 5. Auth endpointy

Prihlásenie a reset hesla sú citlivé. Aj keď request logging existuje, loguj iba metadata potrebné na obranu:

- výsledok a reason code,
- anonymizovaný/normalizovaný identifikátor podľa policy,
- IP a user agent v primeranom rozsahu,
- request ID,
- rate-limit/lockout stav.

Nikdy neloguj heslo, reset token, TOTP code, recovery code alebo kompletný OAuth callback query.

## 6. Redakcia údajov

Centralizovaný sanitizer má redigovať názvy ako:

```text
password, pass, secret, token, api_key, authorization,
cookie, csrf, totp, recovery_code, private_key
```

Redakcia podľa názvu nestačí. Log message musí odstrániť CR/LF/ANSI injection a obmedziť dĺžku neznámeho inputu. Binárny upload a celý content body sa nemajú ukladať do logu.

## 7. Request ID a korelácia

Na každom requeste generuj alebo validuj bezpečný request ID. Vráť ho v response header/envelope, zapíš do logov a používaj pri podpore. Nedôveruj nekontrolovanej dlhej hodnote od klienta; normalizuj formát alebo vygeneruj nový ID.

Background job má vlastný job ID a môže niesť parent request ID. Tým sa dá sledovať save → event → Git/preklad/notifikácia bez miešania identít.

## 8. Admin prehliadač logov

UI môže poskytovať filtre podľa severity, source, času, fulltextu a archived stavu. Bulk archive/delete musí:

- vyžadovať privilegovanú rolu a CSRF,
- zobraziť počet záznamov,
- potvrdiť nevratné zmazanie,
- zvládnuť partial failure,
- zapísať audit bez vloženia celého mazaného logu.

„Vymazať všetko“ nie je štandardná diagnostická akcia. Pred incident response zachovaj dôkazy.

## 9. Retencia a rotácia

Retenciu nastav podľa kapacity, privacy a incident potrieb. Denné JSON súbory alebo ring buffer musia mať atomickú rotáciu. Purge nesmie zmazať aktívne otvorený súbor spôsobom, ktorý poškodí writer.

Zálohy logov oddeľ od záloh autoritatívneho obsahu; nepotrebuješ držať debug log večne len preto, že zálohuješ CMS.

## 10. Čas a timezone

Ukladaj UTC a v UI zobrazuj používateľskú timezone. Pri reporte vždy uveď timezone alebo ISO timestamp. Nesynchronizovaný čas rozbije TOTP, koreláciu, scheduler aj incident timeline; server musí používať NTP.

## 11. Slow request a výkon

`slowRequestMs` je diagnostický prah, nie dôkaz bezpečnostného incidentu. Pri spomalení koreluj route, storage I/O, lock wait, outbound provider a worker load. Nezapínaj Redis automaticky iba na základe jedného pomalého requestu; Performance Guard musí prejsť capability testom.

## 12. Externý log shipping

Loki/syslog/SIEM export je prevádzková integrácia a môže byť environment-specific. Pred odoslaním:

- používaj TLS alebo lokálny dôveryhodný transport,
- rediguj secrets ešte pred odchodom z aplikácie,
- definuj retry/backpressure,
- nedovoľ, aby výpadok log providera blokoval content save,
- rešpektuj retenciu a jurisdikciu osobných údajov.

## 13. Diagnostický postup

1. zaznamenaj čas, používateľa, akciu a release,
2. získaj request ID z UI/network panelu,
3. filtruj request/application log,
4. koreluj audit a security event,
5. over worker/provider log pri async akcii,
6. rediguj výsek pred zdieľaním,
7. po oprave pridaj regresný test alebo alert.

## 14. Bežné problémy

| Symptóm | Kontrola |
|---|---|
| IP je vždy proxy | `TRUSTED_PROXIES` a forwarded headers |
| chýba request | minimum severity, request logging, auth exclusion, disk rights |
| log rastie príliš rýchlo | 404/bot noise, debug, retention, duplicate middleware |
| JSON je poškodený | súbežný zápis, chýbajúci lock/atomic rename |
| UI parse chyba | neplatný riadok; zachovať súbor a izolovať poškodený záznam |
| secrets v logu | incident: obmedziť prístup, rotovať secret, opraviť sanitizer, purge podľa policy |

## 15. Súvisiace dokumenty

- [Firewall](FIREWALL.md)
- [API kontrakt](../architecture/API_CONTRACT.md)
- [Core hardening](../architecture/CORE_HARDENING.md)
- [Príručka administrátora](ADMIN_GUIDE.md)
