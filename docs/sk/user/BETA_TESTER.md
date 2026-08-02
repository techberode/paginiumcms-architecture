---
title: Príručka beta testera
description: Reprodukovateľné funkčné, prevádzkové a bezpečnostné testovanie PaginiumCMS
icon: material/test-tube
---

# Beta tester — pracovný návod

> Testuj presný tag z release stránky rodiny **`v2.1.0-beta.*`**. Do reportu nepíš iba „latest“; po ďalšom release by nebolo jasné, čo si testoval.

## 1. Rozsah beta testu

Beta má overiť:

- čistú inštaláciu a prvé prihlásenie,
- každodenné editovanie obsahu,
- permission a bezpečnostné hranice,
- recovery a prevádzkové workflow,
- rozdiel medzi implementovanou a plánovanou Hybrid Engine schopnosťou,
- zrozumiteľnosť dokumentácie.

Beta nie je povolenie testovať cudziu verejnú inštanciu bez súhlasu. Bezpečnostné testy vykonávaj iba vo vlastnom alebo výslovne autorizovanom prostredí.

## 2. Pred testom zaznamenaj prostredie

```text
PaginiumCMS tag/commit:
Inštalácia: Docker | native | VPS
OS a architektúra:
PHP verzia:
Node verzia (ak relevantná):
Web server / proxy:
Prehliadač:
APP_ENV:
Reprodukcia na čistých dátach: áno/nie
```

Secrets, hostname interného servera a verejnú IP zverejňuj iba ak sú potrebné a bezpečné.

## 3. Rýchly smoke test

| # | Úloha | Výsledok |
|---|---|---|
| 1 | inštalácia podľa [INSTALLATION.md](INSTALLATION.md) | [ ] |
| 2 | `/api/health` odpovie úspešne | [ ] |
| 3 | bootstrap login a zmena hesla | [ ] |
| 4 | 2FA setup a opätovný login | [ ] |
| 5 | dashboard bez neošetrenej 500 | [ ] |
| 6 | stránka: draft → preview → publish | [ ] |
| 7 | článok s tagom a obrázkom | [ ] |
| 8 | upload média a verejné zobrazenie | [ ] |
| 9 | navigácia a anonymná kontrola webu | [ ] |
| 10 | audit/log zodpovedá vykonanej akcii | [ ] |
| 11 | vytvorenie a overenie zálohy | [ ] |
| 12 | cron/worker podľa profilu | [ ] |

## 4. Editor a konflikty

Otestuj dve oddelené session:

1. otvor rovnaký obsah v oboch,
2. over lock upozornenie/heartbeat,
3. ulož z prvej session,
4. skús uložiť stale verziu z druhej,
5. over, že systém neprepíše novšiu verziu potichu,
6. zdokumentuj conflict resolver alebo chybový kontrakt.

Pri reporte uveď request status a redigované response `code`/`requestId`, ak ich build poskytuje.

## 5. Roly a ACL

Vytvor účty `EDITOR` a podľa potreby `ADMIN`. Otestuj:

- povolenú akciu cez UI,
- tú istú povolenú akciu cez API,
- zakázanú mutáciu cez priamy API request,
- Path ACL povolenú a zamietnutú cestu,
- rozdiel 404 pri skrytí existencie a 403 pri zakázanom zápise,
- že SUPER_ADMIN bypass zodpovedá dokumentovanému kontraktu.

Neoznač UI-only schovanie tlačidla za úspešný security test.

## 6. Prevádzkové testy

- vypni alebo znefunkčni odvodenú cache a over recovery/rebuild,
- simuluj plný alebo read-only storage iba v izolovanom prostredí,
- over správanie pri nedostupnom outbound providerovi,
- zastav worker a sleduj stav jobu bez opakovaného spúšťania,
- vykonaj restore test do oddelenej cesty,
- over, že logs/backups/data nie sú priamo dostupné cez web.

## 7. Firewall a logging

Na vlastnej inštancii over bezpečný probe, napríklad neexistujúcu WordPress cestu, a skontroluj incident/jail podľa konfigurácie. Nepoužívaj deštruktívny payload a neskenuj cudzie siete.

Over, že:

- WAF neblokuje bežný editorový obsah,
- klientská IP je správna za proxy,
- auth endpointy neredigujú heslo alebo TOTP do logu,
- log retention a purge fungujú,
- plain-text WAF 403 nerozbije frontend nekontrolovaným JSON parse.

## 8. Extension a Developer Mode test

Testuj iba dôveryhodný referenčný balík. Over import → disabled stav → explicitnú aktiváciu → smoke endpoint → deaktiváciu. Frontendový zdroj môže vyžadovať build/redeploy; jeho nezobrazenie bez buildu nie je automaticky backendový bug.

Code Editor Save neznamená plugin enable, Git commit alebo deploy. Reportuj konkrétny chýbajúci krok.

## 9. Čo je plánované, nie hotové

Bez potvrdenia release notes nečakaj univerzálne:

- API key/JWT integráciu It.74,
- Redis Performance Guard It.69/71,
- S3 media driver It.72,
- viacjazyčný content dokument It.73,
- AI agent It.75,
- self-hosted/cloud preklady It.76/77,
- úplne automatický Git publish It.70.

Ak UI obsahuje placeholder, reportuj ho ako UX/docs problém len vtedy, ak sa tvári ako funkčná hotová capability.

## 10. Hlásenie bežného bugu

Pred vytvorením issue:

1. skontroluj [ISSUES.md](../ISSUES.md),
2. reprodukuj na čistom profile alebo nových dátach,
3. skús posledný potvrdený beta tag,
4. oddeľ konfiguráciu/proxy problém od aplikačného bugu.

Dobrý report obsahuje:

```markdown
### Verzia a prostredie
...

### Kroky reprodukcie
1. ...
2. ...

### Očakávané správanie
...

### Skutočné správanie
...

### Log / request ID
redigovaný výsek

### Reprodukcia
vždy | občas | raz
```

## 11. Bezpečnostný nález

Neotváraj verejný issue s neopraveným exploitom, tokenom alebo dumpom dát. Postupuj podľa koreňového `SECURITY.md`. Uveď dopad, predpoklady, minimálnu reprodukciu a návrh mitigácie; neposielaj viac osobných dát, než je nevyhnutné.

## 12. Dokumentačný test

Dokumentácia je súčasť produktu. Nahlás:

- neplatný link,
- starý tag alebo screenshot,
- endpoint, ktorý v release neexistuje,
- plánovanú funkciu opísanú ako hotovú,
- chýbajúci rollback alebo bezpečnostné upozornenie,
- rozdiel medzi SK a EN významom.

Ďakujeme — beta tester, ktorý pošle čistú reprodukciu, ušetrí maintainerovi viac času než desať správ typu „nejde to“. 🧪
