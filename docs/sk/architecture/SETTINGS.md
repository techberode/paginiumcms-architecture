---
title: Nastavenia
description: Schema-driven flat-file settings, precedence, visibility a tajomstvá
icon: material/tune-variant
---

# Nastavenia PaginiumCMS

> **Autorita:** `SettingsSchema` defaults + flat-file overrides  
> **Aktuálny súbor:** `backend/storage/app/content/data/settings.json`  
> **Test izolácia:** `settings.testing.json`

Settings engine poskytuje typované, validované a auditovateľné nastavenia bez databázy. Ukladá iba odchýlky od predvolených hodnôt, takže nové bezpečné defaulty sa môžu zaviesť bez prepisovania celého súboru. Citlivé hodnoty sa nesmú dostať do public API, logov ani plaintext backup reportov.

---

## 1. Vrstvy a precedence

Kanonický effective value model:

```text
schema default
→ persisted instance override
→ environment/secret binding pre explicitne povolené polia
→ runtime request context, ktorý sa neukladá
```

Environment nemá ľubovoľne prepisovať každé UI nastavenie. Schéma pri každom poli určuje, či je možné:

- meniť ho cez admin API,
- bindovať ho z environmentu,
- publikovať ho v public slice,
- šifrovať ho,
- meniť ho bez reštartu,
- delegovať ownership modulu.

Pri konflikte musí diagnostika ukázať zdroj effective hodnoty bez zobrazenia tajomstva.

---

## 2. Storage model

`settings.json` obsahuje iba overrides:

```json
{
  "general": {
    "siteName": "Moja stránka",
    "language": "sk"
  },
  "content": {
    "autoSaveInterval": 120
  }
}
```

Pravidlá zápisu:

1. načítať schému a aktuálne overrides pod lockom,
2. overiť group/field ownership a oprávnenie,
3. validovať typ, rozsah, enum, URL a cross-field invariants,
4. citlivé polia zašifrovať,
5. odstrániť hodnoty rovné defaultu, ak to nemení semantics,
6. atomicky zapísať JSON,
7. reloadnúť iba dotknuté runtime služby,
8. vytvoriť audit/event bez tajnej hodnoty.

Neplatný alebo poškodený settings súbor sa nesmie automaticky nahradiť defaultom bez incidentu a zálohy pôvodného súboru.

---

## 3. Klasifikácia polí

| Trieda | Príklad | API správanie |
|--------|---------|---------------|
| **Public** | site name, logo, locale, maintenance copy | môže byť v `/api/settings/public` |
| **Admin** | pagination, editor, retention, alert policy | iba autorizovaný admin read/write |
| **Restricted** | access control, path ACL, developer policy | SUPER_ADMIN alebo explicitná permission |
| **Secret** | SMTP password, API token, webhook secret | write-only/redacted; šifrované at rest |
| **Environment-owned** | `APP_KEY`, runtime paths, trusted proxies | nie je editovateľné cez bežné settings UI |
| **Derived** | capability status, source of effective value | vypočítané; neukladá sa ako user override |

Public endpoint môže zverejniť iba allow-list polí. Nesmie serializovať celú group a následne „odstrániť známe secrets“, pretože nové tajné pole by sa mohlo nechtiac publikovať.

---

## 4. Skupiny nastavení

### Aktuálne skupiny

| Group | Úloha | Typický editor |
|-------|-------|----------------|
| `general` | site identity, locale, timezone, registration | ADMIN+ |
| `branding`, `appearance` | logo, favicon, public/login vzhľad | ADMIN+ |
| `accessControl` | role permissions, path ACL | SUPER_ADMIN |
| `content` | pagination, default status, autosave, lock TTL | ADMIN+ |
| `maintenance` | coming soon/maintenance režimy | ADMIN+ |
| `editor` | editor, spellcheck, tab size | ADMIN+ |
| `smtp` | mail transport | ADMIN+, secret fields |
| `notifications` | toast a UI behavior | ADMIN+; public iba bezpečný slice |
| `connectors` | email, ntfy, Discord, Telegram, webhook | ADMIN+, credentials secret |
| `monitoring` | incidents a scheduled reports | ADMIN+ |
| `security` | password/2FA policy | ADMIN+ alebo restricted field |
| `firewall` | WAF rules a thresholds | ADMIN+ |
| `logging` | severity, retention, request logging | ADMIN+ |
| `marketing` | demo footer link a URL | ADMIN+ |

### Plánované Hybrid Engine skupiny

It.68–77 majú používať spoločnú hierarchiu, napríklad:

- `engine.deploymentMode`
- `engine.storage.driver`
- `engine.cache.driver`
- `engine.git.enabled`
- `engine.git.publishStrategy`
- `engine.performanceGuard.enabled`
- `media.storage.driver`
- `localization.*`
- `translation.providers.*`
- `ai.providers.*`
- `apiAuth.*`

Presný názov sa uzamkne v schéme. Chýbajúce kľúče musia znamenať bezpečné **Classic** správanie.

---

## 5. Schema contract

Každé pole má definovať minimálne:

```json
{
  "type": "string",
  "default": "file",
  "enum": ["file", "redis"],
  "visibility": "admin",
  "secret": false,
  "restartRequired": false,
  "owner": "core.cache",
  "since": "2.1.0"
}
```

Schéma môže navyše obsahovať label key, help key, validation bounds, capability dependency, deprecation a migration callback. UI je schema-driven, ale backend schéma je autorita; frontend validácia je iba UX pomoc.

Cross-field príklady:

- `engine.cache.driver=redis` vyžaduje platný Redis config a úspešný capability probe,
- Git publish nemožno aktivovať bez repository/branch/credential policy,
- cloud translation provider vyžaduje šifrovaný credential a outbound allow-list,
- `pathAclEnabled=true` vyžaduje validné rules JSON.

---

## 6. API

| Method | Endpoint | Prístup | Úloha |
|--------|----------|---------|-------|
| `GET` | `/api/settings/public` | anonymous alebo session | allow-listed public effective values |
| `GET` | `/api/admin/settings` | ADMIN | schema + redacted effective values |
| `GET` | `/api/admin/settings/{group}` | podľa ownera | jedna group |
| `PUT` | `/api/admin/settings/{group}` | podľa ownera | validovať a uložiť patch/replace podľa kontraktu |
| `DELETE` | `/api/admin/settings` | ADMIN | reset podporovaných overrides; restricted groups podľa policy |
| `GET` | `/api/settings/public-demo` | iba `DEMO_MODE=true` | demo login copy |

API odpoveď má rozlišovať:

- `value` alebo redacted marker,
- `source` (`default`, `file`, `env`),
- `editable`,
- `restartRequired`,
- validation errors per field.

Secret endpoint nikdy nevracia plaintext. Nezmenený password input sa reprezentuje samostatným „keep existing“ stavom, nie maskou `********` odoslanou späť ako nové heslo.

---

## 7. Tajomstvá a šifrovanie

Citlivé polia používajú `EncryptionService` a aplikačný key material. Povinné pravidlá:

- ciphertext je verzovaný formát s algoritmom/key version metadata,
- `APP_KEY` alebo master key sa neukladá v settings súbore,
- rotate flow podporuje dry-run, backup a rollback,
- decrypt failure nevynuluje credential a neuloží default,
- logy obsahujú iba názov poľa/provider a incident ID,
- test fixtures nepoužívajú produkčný key ani produkčné secrets,
- export/backup jasne uvádza, že obnova vyžaduje správny key material.

---

## 8. Runtime reload

Nie každé nastavenie sa má aplikovať rovnako:

| Typ | Správanie |
|-----|-----------|
| UI/public copy | okamžitý refresh contextu/cache |
| RBAC/path ACL | atomický save + synchronizácia policy store + audit |
| logging/notification policy | reload factory/service pri ďalšom requeste |
| cache driver | capability test, controlled switch, fallback |
| storage/media driver | migration workflow; nie okamžitý toggle |
| trusted proxies/session cookie/key material | environment/deploy zmena, zvyčajne reštart |

UI musí pri `restartRequired` alebo migration-required nastavení zobraziť reálny stav, nie úspech iba preto, že JSON bol uložený.

---

## 9. Ownership modulov

Core schema registry agreguje skupiny, ale owner modulu definuje fields a validáciu svojej domény. Modul nesmie meniť cudziu group pomocou nezdokumentovaného array merge.

Pri odinštalovaní/disable extension sa jej settings:

- nevymažú automaticky bez potvrdenia,
- nepublikujú v public API,
- označia ako orphaned/disabled,
- môžu exportovať alebo purge podľa policy.

---

## 10. Migrácie a deprecation

Zmena názvu alebo typu poľa potrebuje:

1. schema version,
2. idempotentnú migráciu,
3. dry-run/report,
4. backup pôvodných overrides,
5. compatibility read počas deprecation okna,
6. audit bez secret value,
7. rollback alebo restore postup.

Schéma nemá navždy niesť tri synonymá rovnakého poľa. Po deprecation okne sa legacy key odstráni kontrolovanou migráciou.

---

## 11. Validácia a chyby

Backend používa spoločný validator a mapuje chyby na 422 s field detailom. 403 znamená chýbajúce oprávnenie, 409 konflikt settings revision alebo prebiehajúcu migráciu a 503 nedostupnú capability.

Settings write má používať revision/ETag alebo lock tak, aby dva admin formuláre potichu neprepísali zmeny. Toto je cieľový hardening aj v prípade, že aktuálne repository chráni iba filesystem race cez `flock`.

---

## 12. Testy

- default + override merge,
- izolácia `settings.testing.json`,
- public allow-list a secret non-disclosure,
- encryption round-trip a wrong-key failure,
- permission per group/field,
- cross-field validation,
- concurrent update/conflict,
- runtime reload a restart-required marker,
- capability probe failure zachová predchádzajúci driver,
- schema migration dry-run/rollback,
- SK/EN label/help catalog parity.

---

## Súvisiace dokumenty

- [CORE.md](./CORE.md)
- [STORAGE.md](./STORAGE.md)
- [CORE_HARDENING.md](./CORE_HARDENING.md)
- [ADMIN_DEEP_LINKS.md](./ADMIN_DEEP_LINKS.md)
- [ITERATION_68](../ITERATION_68.md)
