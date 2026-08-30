---
title: Príručka administrátora
description: Roly, moduly, bežné workflow a bezpečná prevádzka administrácie PaginiumCMS
icon: material/view-dashboard
---

# Príručka administrátora PaginiumCMS

> Kompletný funkčný index administrácie pre **`v2.1.0-beta.*`**. Viditeľnosť modulu závisí od roly, permissions, konfigurácie a konkrétneho buildu.

## 1. Základné pravidlá

- Backend autorizácia je rozhodujúca; sidebar a tlačidlá sú iba UX.
- Pred kritickou zmenou vytvor zálohu a poznaj rollback.
- Secrets nikdy nekopíruj do issue, auditu alebo verejného screenshotu.
- `SUPER_ADMIN` nie je bežná pracovná rola editora.
- Uloženie obsahu, publikovanie, Git push, preklad a AI apply sú oddelené akcie.

## 2. Roly

| Rola | Typické úlohy | Nemala by robiť |
|---|---|---|
| `USER` | profil, verejné interakcie podľa policy | admin mutácie |
| `EDITOR` | stránky, články, médiá, navigácia | používateľská a bezpečnostná administrácia |
| `ADMIN` | platformové nastavenia, používatelia, inbox, prevádzka | obchádzanie extension policy a Path ACL bez dôvodu |
| `SUPER_ADMIN` | RBAC, Path ACL, extensions, kritické nastavenia | každodenné editovanie pod privilegovaným účtom |

Presné mapovanie nájdeš v [ACCESS_CONTROL.md](ACCESS_CONTROL.md).

## 3. Dashboard

Dashboard je orientačný prehľad, nie monitoringový systém s garantovanou úplnosťou. Typicky zobrazuje počty obsahu, poslednú aktivitu, storage informácie, log/firewall stav a rýchle odkazy.

Ak panel zlyhá, over jednotlivý API endpoint a log. Nedostupnosť analytiky nesmie blokovať editovanie autoritatívneho obsahu.

### Rýchle vyhľadávanie (command palette)

It.43 admin search umožňuje skoky na stránky, články, médiá a admin moduly.

| Akcia | Ako |
|--------|-----|
| Otvoriť paletu | **Ctrl+Shift+K** (odporúčané vo Firefoxe/Linuxe) alebo **Ctrl+K** v Chromium; alebo tlačidlo **Rýchle vyhľadávanie…** v hlavičke |
| Navigácia vo výsledkoch | ↑↓ výber, **Enter** skok, **Esc** zavrieť |
| Prázdna query | Posledné skoky + skratky admin modulov |
| Vyhľadávanie | ≥2 znaky — index obsahu, médiá, trasy podľa oprávnení |

Vyžaduje aktívnu admin session. Pri 401 na `GET /api/search?scope=admin` pozri [ISS-158](../ISSUES.md#iss-158).

## 4. Stránky

**Stránky** spravujú podstránky verejného webu.

Bežné workflow:

1. vytvorenie draftu,
2. editácia s lock/heartbeat,
3. náhľad,
4. kontrola revision konfliktu,
5. publikovanie alebo archivácia,
6. pridanie do navigácie.

Slug je súčasť URL a identity súboru. Jeho zmena môže vyžadovať redirect a kontrolu interných odkazov. Detail: [CONTENT_EDITOR.md](CONTENT_EDITOR.md).

## 5. Články

Články používajú rovnaké jadro editora, navyše môžu mať excerpt, tagy, featured image, dátum publikovania a komentáre. Scheduled publish vyžaduje funkčný scheduler/worker podľa release.

**Bulk akcie:** Pri výbere riadkov lišta ukáže **„X z Y vybraných“** (Y = celkový počet záznamov podľa aktuálnych filtrov). Potvrdzovacie dialógy pri publikovaní, koncepte, archivácii a mazaní používajú rovnaký pomer.

**Tlač na verejnom blogu:** Nastavenia → Obsah → **Povoliť tlač článkov** (`content.articlePrintEnabled`, predvolene vypnuté). Po zapnutí sa na detaile článku zobrazí **Tlačiť článok**; tlač skryje navigáciu, komentáre a chrome.

Bulk akciu používaj iba po filtrovaní a kontrole počtu položiek. Pri soft delete over kôš a retenciu pred permanentným zmazaním.

## 6. Médiá

Media Manager slúži na upload, metadáta, priečinky a výber assetu do obsahu alebo brandingu.

Administrátor kontroluje:

- povolený MIME typ a reálny obsah súboru,
- limit veľkosti a diskovú kapacitu,
- alt/title metadata,
- verejnú versus internú cestu,
- referencie pred zmazaním,
- proxy/storage konfiguráciu pri 404.

Budúci local/S3 driver z It.72 nemení pravidlo: autoritatívne metadata zostávajú pod kontrolou CMS a migrácia musí byť overiteľná.

## 7. Navigácia

Navigácia definuje strom verejných odkazov, poradie, parent väzby a target. Pred uložením veľkej zmeny exportuj alebo zálohuj konfiguráciu. Otestuj desktop, mobil a keyboard navigation.

## 8. Komentáre a správy

**Komentáre** podporujú moderáciu podľa globálnej a content policy. **Správy** predstavujú kontaktný inbox.

Hromadné akcie (prečítané, vybavené, archivovať, zmazať) používajú rovnaký vzor **„X z Y vybraných“** a potvrdenia ako zoznamy obsahu.

- osobné údaje neexportuj bez právneho dôvodu,
- pri spame kombinuj rate limit, WAF a moderáciu,
- urgentný label nie je náhradou za notifikačný kanál,
- zmazanie môže podliehať retenčnej policy.

## 9. Nastavenia

Nastavenia sú rozdelené do skupín. Typické oblasti:

| Oblasť | Príklady |
|---|---|
| Site | názov, URL, jazyk, timezone, branding |
| Content/SEO | editor, pagination, meta, feedy, **prepínač tlače článkov** |
| Accounts/Security | registrácia, heslá, 2FA, upload policy |
| Access control | RBAC a Path ACL pre SUPER_ADMIN |
| Integrations | SMTP, ntfy, webhook alebo ďalší provider |
| Operations | logging, firewall, cache, maintenance |

Citlivé polia majú byť šifrované at rest a redigované v odpovediach. Po zmene `APP_KEY` bez migračného postupu sa môžu stať nečitateľnými.

## 10. Používatelia a bezpečnosť účtu

Pri vytvorení účtu priraď najnižšiu potrebnú rolu, vynúť unikátne heslo a 2FA pre staff. Pri odchode človeka účet deaktivuj, zruš aktívne session/tokeny a skontroluj vlastníctvo rozpracovaného obsahu.

Používateľská bezpečnostná obrazovka môže spravovať heslo, TOTP a recovery kódy. Vypnutie 2FA privilegovanému účtu má byť auditované a chránené reautentifikáciou.

## 11. Notifikácie a outbound providery

Email, ntfy, Telegram alebo webhook konfiguruj iba na allow-listované HTTPS ciele podľa policy. Test odoslania má redigovať secret a nesmie zapisovať celý payload s osobnými údajmi do logu.

Pri chybe rozlišuj uloženie konfigurácie od úspešného doručenia. Provider môže byť nedostupný aj keď CMS nastavenie prešlo validáciou.

## 12. Plánovač a joby

Admin obrazovka plánovača zobrazuje definované úlohy; reálne vykonanie závisí od cron/worker procesu. Sleduj posledný beh, ďalší beh, duration, lock a poslednú chybu.

Nespúšťaj dlhý job opakovane len preto, že UI nereaguje. Najprv over worker a log, aby nevznikli duplicitné e-maily, backupy alebo Git publish.

## 13. Zálohy a obnova

Záloha musí pokryť autoritatívny obsah, nastavenia, potrebné keys a namespaced dáta rozšírení. Cache a index sú obnoviteľné; nemusíš ich považovať za jediný recovery zdroj.

### Automatické plánované zálohy

Plánované zálohy **nebežia** len z prehliadača. Zapni všetky kroky:

1. **Nastavenia → Plánovač jobov** — hlavný vypínač zapnutý.
2. **Platforma → Plánovač** — zapni job `backup-scheduled` (predvolený cron: denne o 02:00).
3. **Cron na hostiteľovi** — `php backend/bin/console scheduler:run` každú minútu (viď `docs/deploy/CRON.md`).
4. **Platforma → Zálohy** — sekcia **Automatické zálohy**, interval a retencia, uložiť.

UI zobrazí `next_run` / `last_run` po uložení plánu. **Spustiť teraz** na jobe je na test; produkcia stále potrebuje cron.

Bezpečný restore:

1. over checksum a kompatibilitu,
2. zapni maintenance alebo zastav write traffic,
3. vytvor pre-restore snapshot,
4. obnov do staging cesty,
5. spusti diagnostiku,
6. až potom aktivuj obnovené dáta.

## 14. Kôš a permanentné mazanie

Soft delete umožňuje obnovu. Permanent delete je nevratná doménová akcia aj keď existuje filesystem backup. Pred hromadným zmazaním over filter, počet položiek, referencie médií a retenčné požiadavky.

## 15. Git synchronizácia a publish

Existujúce GitHub/Git workflow môže mať odlišný rozsah podľa buildu. V cieľovej It.70 architektúre rozlišuj:

```text
stored → pending_publish → committed → pushed
                         ↘ publish_failed
```

Lokálne uloženie nesmie byť spätne označené ako neúspešné len preto, že vzdialený Git push zlyhal. Retry nesmie vytvoriť duplicitný commit bez idempotency pravidla.

## 16. Preklady a lokalizácia

Správa prekladov admin UI nie je to isté ako viacjazyčný content dokument. Cieľová lokalizačná vetva It.73/76/77 používa návrh a diff; preklad sa nemá automaticky publikovať bez samostatného schválenia.

## 17. Firewall, logy a audit

- [Firewall](FIREWALL.md) blokuje definované probe scenáre a spravuje jaily.
- [Logy](LOGGING.md) pomáhajú diagnostikovať requesty a runtime udalosti.
- Audit eviduje významné používateľské a systémové zmeny.
- [Code policy](../architecture/CODE_POLICY.md) riadi pluginy, témy a nedôveryhodné PHP/JSON plochy (fail-closed pri importe a ukladaní).

Tieto vrstvy sa dopĺňajú, ale nie sú navzájom zameniteľné. Audit nemá byť zaplavovaný každým read requestom a request log nemá nahradiť doménový audit.

### Bezpečnostný checklist (operátor)

| Oblasť | Kde overiť |
|--------|------------|
| RBAC + 2FA pre staff | Nastavenia → Bezpečnosť, Bezpečnosť účtu |
| API kľúče / webhook secret | Platforma → API kľúče / Webhooks; vyžaduje `APP_KEY` / pepper v `.env` |
| Code policy pre rozšírenia | Nastavenia → Code policy; sken pri každom importe pluginu/témy |
| Upload + sanitizácia obsahu | Nastavenia → Upload security, Content security |
| Firewall + outbound URL guard | Platforma → Firewall; SSRF ochrana na konfigurovateľných URL |
| Zálohy + scheduler cron | Platforma → Zálohy, Plánovač; potrebný cron na hostiteľovi |

## 18. Code Editor, Developer Mode a extensions

Tieto nástroje patria medzi vysoko rizikové capability. Používaj ich na stagingu, s časovo obmedzeným unlockom a zálohou. Save v Code Editore automaticky neznamená build, reload, aktiváciu pluginu ani deploy.

- [Code Editor](CODE_EDITOR.md)
- [Developer Mode](DEVELOPER_MODE.md)
- [Pluginy](PLUGINS.md)
- [Témy](THEMES.md)

## 19. Údržba, privacy a analytika

Maintenance/Coming Soon režim musí mať staff bypass iba pre autorizované účty a nesmie nechtiac sprístupniť draft. Newsletter a kontaktné dáta podliehajú privacy a unsubscribe pravidlám.

Analytika je odvodená prevádzková vrstva. Vypnutie alebo strata analytických dát nesmie poškodiť obsah. Cookie consent má rešpektovať kategórie a návštevníkovi umožniť zmenu voľby.

## 20. Rutinný checklist

**Denne:** kritické logy, firewall jaily, failed jobs, storage kapacita.  
**Týždenne:** backup report, neaktívne účty, pending komentáre, scheduler health.  
**Pred release:** backup + restore test, gate, changelog, config diff, smoke test.  
**Po incidente:** zachovať dôkazy, rotovať kompromitované secrets, zdokumentovať timeline a overiť recovery.

## 21. Súvisiace dokumenty

- [Prvé kroky](FIRST_STEPS.md)
- [Editor obsahu](CONTENT_EDITOR.md)
- [Oprávnenia](ACCESS_CONTROL.md)
- [API kontrakt](../architecture/API_CONTRACT.md)
- [Core hardening](../architecture/CORE_HARDENING.md)
