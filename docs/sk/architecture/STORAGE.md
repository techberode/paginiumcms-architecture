---
title: Storage
description: Flat-file SSOT, fyzické rozloženie a konzistenčný kontrakt
icon: material/database-off-outline
---

# Flat-file úložisko PaginiumCMS

> **Povinné:** [No-SQL mandát](./NOSQL_MANDATE.md)  
> **Aktuálny lokálny koreň:** `backend/storage/app/content/`  
> **Cieľ It.68:** storage abstraction s lokálnym driverom ako baseline

Storage vrstva uchováva autoritatívne dokumenty a súborový prevádzkový stav. **SQLite, MySQL, PostgreSQL ani externá document DB nie sú plánované ako alternatívny CMS source of truth.** Voliteľné služby smú zrýchliť čítanie alebo distribuovať výstup, nie prevziať vlastníctvo primárnych dát.

---

## 1. Vrstvy dát

| Vrstva | Príklad | Autorita | Obnova |
|--------|---------|----------|--------|
| Primárne dokumenty | pages, articles, users, settings | ✅ áno | zo zálohy/Git podľa policy |
| Prevádzkový súborový stav | versions, drafts, locks, conflicts, jobs | podľa typu kritický | recovery/retention policy |
| Index | `data/index/content.json` | ❌ nie | full rebuild zo SSOT |
| Cache | memory, file, plán Redis | ❌ nie | zahodiť a znovu naplniť |
| Distribučná kópia | Git remote/statický build | ❌ nie | opakovaný publish |
| Binárne médiá | local alebo plánovaný driver | objekt je primárny binárny asset; metadata zostávajú súborové | driver-specific backup |

---

## 2. Fyzické rozloženie

```text
backend/storage/
├── app/content/
│   ├── pages/*.md
│   ├── blog/*.md
│   ├── media/
│   │   └── registry.json
│   ├── trash/
│   └── data/
│       ├── users/user_*.json
│       ├── settings.json
│       ├── settings.testing.json
│       ├── index/content.json
│       ├── versions/
│       ├── drafts/{page|article}/
│       ├── locks.json
│       ├── conflicts.json
│       ├── plugins.json
│       └── security/
├── cache/
├── logs/
├── backups/
├── firewall/
└── dev/
```

Presný strom sa môže rozširovať. Každý nový súbor však potrebuje:

- jednoznačného ownera,
- schema/version pravidlo,
- permission a backup policy,
- bezpečný writer,
- rozhodnutie, či je autoritatívny alebo odvodený.

---

## 3. Formát content dokumentu

Stránky a články používajú Markdown s YAML front matter. Kanonizácia pre revision a index nesmie meniť význam dokumentu iba pre poradie kľúčov. Minimálny príklad:

```markdown
---
title: O nás
slug: o-nas
status: published
updatedAt: 2026-08-02T10:00:00Z
---

# O nás
```

Schema registry z It.68 má validovať známe dokumentové typy. Neznáme pole sa spracuje podľa verzie schémy a compatibility policy, nie náhodným zahodením.

---

## 4. Bezpečná cesta

Všetky lokálne operácie prechádzajú cez `FileValidator`, `FileReader`, `FileWriter` alebo ich cieľový storage kontrakt. Povinné pravidlá:

- cesta sa vyhodnotí voči explicitnému base root,
- `..`, NUL, nečakané symlinky a nepovolené extension sa odmietnu,
- slug alebo ID sa nikdy neprilepí priamo do absolútnej cesty bez normalizácie,
- allow-list rootov má prednosť pred blacklistom,
- web server nesmie priamo servovať `data/`, backups, logs, dev ani secrets,
- mediálne SVG/HTML sa servujú s bezpečnou content policy alebo ako attachment.

It.68 driver nesmie oslabiť tieto kontroly. Abstraction je miesto na centralizáciu ochrany, nie obchádzka.

---

## 5. Atomický zápis

Odporúčaný lokálny protokol:

1. validovať path, input a schema,
2. získať `flock(LOCK_EX)` alebo doménový lock,
3. načítať aktuálny stav pod rovnakým lockom, ak ide o read-modify-write,
4. zapísať nový obsah do temp súboru v rovnakom filesysteme,
5. flush/close a nastaviť bezpečné permissions,
6. atomicky nahradiť cieľový súbor,
7. uvoľniť lock,
8. až potom udržiavať index/cache/eventy.

Pri zápise viacerých súborov je potrebný migration journal alebo idempotentná repair operácia. Rename cez odlišné filesystemy sa nesmie považovať za atomický.

---

## 6. Súbežnosť a editácia

### Pesimistický zámok

`data/locks.json` uchováva resource ID, ownera, token, heartbeat a expiry. Celý read-modify-write cyklus registra je pod `flock`. Token sa nezobrazuje iným klientom a porovnáva sa bezpečne.

### Optimistická revízia

Klient dostane `revision` a pri mutácii pošle `baseRevision`. Nezhoda znamená HTTP 409, nie automatické prepísanie. Dnešný SHA-1 odtlačok je **concurrency fingerprint, nie kryptografický dôkaz integrity**. Nesmie sa používať na podpis alebo bezpečnostné rozhodnutie; budúca zmena algoritmu musí zachovať API semantics.

### Draft a konflikt

Drafty sú oddelené od publikovaného dokumentu. Konflikty sú auditované v ohraničenom flat-file logu. Detail: [VERSIONING.md](./VERSIONING.md).

---

## 7. Settings a tajomstvá

`data/settings.json` ukladá iba overrides voči `SettingsSchema`. Citlivé polia sa pred zápisom šifrujú cez aplikačný encryption service; do public settings slice sa nikdy neposiela ciphertext ani credential metadata, ktoré by uľahčili útok.

Testy používajú izolovaný `settings.testing.json`. Produkčné tajomstvá sa nesmú čítať ani meniť počas PHPUnit runu.

---

## 8. Index

`data/index/content.json` je odvodená projekcia pre zoznamy, filtre a search metadata. Povinný kontrakt:

- dá sa kompletne zmazať a znovu vytvoriť zo zdrojových dokumentov,
- má schema/version marker,
- write je atomický,
- rebuild poskytne report poškodených alebo neplatných dokumentov,
- stale/missing index má definovaný fallback alebo jasnú servisnú chybu,
- index neobsahuje jediné kópie tajomstiev ani plného obsahu, ak to nie je nevyhnutné.

---

## 9. Cache

Memory/file cache je už implementovaná; Redis je plánovaný ako voliteľný driver v It.69. Cache key musí zahŕňať typ, identitu a relevantnú locale/revision/generation informáciu. Invalidácia nasleduje po úspešnom SSOT zápise.

Výpadok cache:

- nesmie spôsobiť stratu dát,
- nesmie automaticky zapnúť inú externú službu bez capability testu,
- má degradovať na podporovaný driver alebo priamy read,
- musí byť viditeľný v health/incident reportoch.

---

## 10. Médiá

Aktuálne sú binárne súbory lokálne a metadata v registri. It.72 pridá `MediaStorageDriverInterface` pre local/S3-compatible storage.

Nemenné pravidlá:

- media metadata a väzba na content zostávajú flat-file SSOT,
- driver nesmie vytvoriť public URL pre private asset bez policy,
- upload validuje MIME, extension, veľkosť a názov,
- delete/presun má idempotentnú recovery cestu,
- signed URL expiry a CDN config sú deployment capability, nie doménové dáta,
- migrácia medzi drivermi má dry-run, checksum a resumable journal.

---

## 11. Trash, verzie a backup

Soft delete presúva dokument do `trash/` a ukladá sidecar s pôvodnou cestou a časom. Restore znovu validuje cieľ, konflikt názvu a permissions a následne obnoví index/cache.

Verzie a drafty nemusia mať rovnakú retention ako živý obsah. Backup policy musí explicitne určiť zahrnutie:

- primárnych dokumentov,
- settings a šifrovaných secrets,
- user/ACL dát,
- media objektov alebo driver manifestu,
- verzií/draftov podľa retention,
- plugin registry a potrebných extension súborov,
- migration journals.

Cache, dočasné súbory a rebuildovateľný index sa do backupu nemusia zahrnúť, ak restore proces index obnoví.

---

## 12. Konzistencia a recovery

| Incident | Očakávaná reakcia |
|----------|--------------------|
| neplatný JSON/front matter | dokument označiť v diagnostike; neprepísať ho defaultom |
| chýbajúci index | rebuild alebo bezpečný fallback |
| stale cache | invalidovať podľa revision/generation |
| disk full/read-only FS | write zlyhá pred úspešnou odpoveďou; incident ID |
| zlyhanie indexu po SSOT write | obsah zostáva uložený, index marked stale + retry |
| zlyhanie Git push | local state `stored`, publish `failed/pending`; retry |
| partial migration | journal určí resume/rollback; žiadny tichý mixed mode |
| poškodený lock registry | bezpečná recovery bez vydania cudzích lock tokenov |

---

## 13. Cieľ `StorageInterface` v It.68

Kontrakt má podporiť minimálne:

- read document + metadata/revision,
- create/replace s atomic semantics,
- delete/move/exists/list podľa capability,
- path alebo document key bez úniku absolútnych interných ciest,
- typované chyby,
- capability probe,
- contract tests,
- lokálny driver s behavior parity.

`StorageInterface` nie je generický shell/filesystem API pre pluginy alebo AI. Doménové služby majú dostať iba operácie potrebné pre svoj typ dokumentu.

---

## 14. Prevádzkový checklist

- storage roots nie sú dostupné cez nginx okrem kontrolovanej media route,
- vlastník a mód súborov sú konzistentné s PHP-FPM používateľom,
- backup restore sa skúša, nie iba vytvára,
- index rebuild a cache clear sú dostupné cez bezpečný CLI/admin workflow,
- orphan temp/journal súbory sa kontrolujú,
- health check rozlišuje writable SSOT, cache a voliteľné capabilities,
- žiadna dokumentácia nenavrhuje SQL fallback.

---

## Súvisiace dokumenty

- [ARCHITECTURE.md](./ARCHITECTURE.md)
- [HYBRID_ENGINE.md](./HYBRID_ENGINE.md)
- [VERSIONING.md](./VERSIONING.md)
- [SETTINGS.md](./SETTINGS.md)
- [ITERATION_68](../ITERATION_68.md)
- [ITERATION_69](../ITERATION_69.md)
- [ITERATION_72](../ITERATION_72.md)
