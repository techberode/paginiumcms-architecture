---
title: Events a hooks
description: Interné udalosti, plugin hooky, payloady a failure policy
icon: material/flash-outline
---

# Events a hooks v PaginiumCMS

> **Aktuálny základ:** `Core/Event/EventDispatcher`, `Core/Hook/HookManager`, `HookEmitter`, `HookCatalog`  
> **Pravidlo:** event oznamuje fakt; hook je kontrolovaný extension point.

Tento dokument vypĺňa pôvodne prázdny architektonický kontrakt. Oddeľuje internú komunikáciu dôveryhodných komponentov od verejných hookov pre extensions. Bez tohto rozdielu sa plugin API ľahko zmení na nezdokumentovaný prístup k interným objektom Core.

---

## 1. Event verzus hook

| Vlastnosť | Interný event | Plugin hook |
|-----------|---------------|-------------|
| Publikum | Core a interné moduly | enabled extensions |
| Stabilita | interná, ale typovaná | verejný/versioned kontrakt |
| Payload | doménový event objekt/DTO | serializovateľný allow-listed context |
| Dôvera handlera | dôveryhodný projektový kód | voliteľný kontrolovaný kód |
| Mutácia | spravidla immutable | iba explicitný filter/result contract |
| Failure | podľa transaction phase | izolovaná, auditovaná; nesmie poškodiť Core |

HookManager nie je všeobecný service locator. Extension nedostáva DI kontajner ani filesystem root iba preto, že odoberá hook.

---

## 2. Názvoslovie

Kanonický názov používa lowercase doménu a akciu:

```text
content.before_save
content.after_save
content.after_delete
content.after_status_change
content.after_scheduled_publish
extension.boot
extension.enabled
extension.disabled
```

Interné event class names môžu byť `ContentSaved`, `BackupCompleted`, `UserRegistered`, ale verejný hook názov je evidovaný v `HookCatalog` s verziou a payload schémou.

Nový hook potrebuje:

- ownera,
- phase (`before`, `after`, async),
- payload schema,
- failure policy,
- security classification,
- version/deprecation pravidlo,
- test emittera a aspoň jeden listener scenario.

---

## 3. Transaction phases

### Before event/hook

Beží po autentifikácii, autorizácii a základnej validácii, ale pred SSOT zápisom. Môže:

- doplniť návrh cez explicitný typed result,
- vykonať ďalšiu validáciu,
- odmietnuť operáciu dokumentovanou výnimkou.

Nesmie:

- preskočiť permission alebo revision check,
- zapisovať priamo do cieľového content súboru,
- vykonať nevratný external side effect bez idempotency/compensation,
- meniť arbitrary request context.

### After event/hook

Beží po úspešnom SSOT zápise. Je vhodný pre audit enrichment, cache/index follow-up, notification alebo queue enqueue. Ak zlyhá, primárny dokument zostáva uložený; systém uloží handler failure a podľa policy retry.

### Async event/job

Pre pomalé alebo externé operácie: Git push, build hook, report, translation, AI provider. Payload obsahuje ID/revision, nie celú citlivú session alebo credentials.

---

## 4. Payload contract

Príklad bezpečného content contextu:

```json
{
  "eventId": "evt_...",
  "name": "content.after_save",
  "occurredAt": "2026-08-02T10:00:00Z",
  "actor": {
    "id": "user_...",
    "type": "user"
  },
  "resource": {
    "type": "page",
    "id": "o-nas",
    "revision": "..."
  },
  "change": {
    "action": "update",
    "status": "draft"
  },
  "schemaVersion": 1
}
```

Default payload neobsahuje:

- session cookie, CSRF, Bearer/API key,
- TOTP/reset token,
- SMTP/provider credentials,
- absolútnu internú cestu,
- celý content body, ak handler potrebuje iba ID,
- neobmedzený mutable service object.

Handler si môže autorizovane načítať potrebný dokument cez úzky read service podľa capability.

---

## 5. Dispatch semantics

Základný in-process dispatcher môže volať listenery synchronne. Musí však definovať:

- deterministické poradie iba ak je kontraktom; inak sa handler nesmie spoliehať na poradie,
- duplicitnú registráciu,
- odstránenie listenera pri disable extension,
- maximum recursion/depth,
- exception handling per phase,
- correlation/request/event ID,
- metrics duration a outcome.

Event „exactly once“ nie je možné sľúbiť bez transakčného brokeru. Queue/listenery preto majú byť **at-least-once safe** a idempotentné.

---

## 6. Aktuálne hook emitters

Dokumentovaný shipped základ obsahuje:

- `content.before_save`
- `content.after_save`
- `content.after_delete`
- `content.after_status_change`
- `content.after_scheduled_publish`
- `extension.boot`
- `extension.enabled`
- `extension.disabled`

Tento zoznam je katalogizačný snapshot, nie povolenie dynamicky emitovať ľubovoľný string. Implementácia a `HookCatalog` musia zostať zdrojom pravdy a release zmena hooku aktualizuje SK/EN docs.

---

## 7. Registrácia extension listenera

Extension deklaruje hook v `plugin.json`:

```json
{
  "id": "my-widget",
  "version": "1.0.0",
  "hooks": {
    "content.after_save": "MyWidget\\Hooks::afterSave"
  },
  "minCmsVersion": "2.1.0"
}
```

Manifest validator overí ID, supported hook, handler formu, min CMS version a code policy. Enabled state je vo flat-file registry. Disable odstráni registráciu pre ďalší request/bootstrap; handler nesmie zostať „ghost listener“ v persistent worker procese.

---

## 8. Failure policy

| Fáza | Failure | Výsledok |
|------|---------|----------|
| `before_*` core validation | validovaná chyba | operácia sa neuloží, 4xx podľa typu |
| `before_*` extension exception | deny alebo fail-open podľa konkrétneho hook kontraktu; default bezpečne fail-closed pri mutácii | incident + žiadny partial write |
| `after_*` listener | primárny write už hotový | log/incident, retry ak idempotentný, response rozlíši follow-up stav |
| async job | external failure | bounded retry, backoff, dead-letter/failed state |
| notification-only handler | failure | content ostáva uložený; admin diagnostics |

Failure policy sa nesmie nechávať na náhodné „catch Throwable a pokračuj“ bez viditeľnosti.

---

## 9. Security

- listenery bežia s minimálnou capability,
- permission sa neodvodzuje z názvu hooku,
- payload a handler output sa schema-validujú,
- outbound handler prechádza SSRF policy,
- log payload je redigovaný a sanitizovaný,
- extension code prešiel CodePolicyEngine,
- recursion a event storm majú limit,
- user-generated text sa nepoužíva ako class/method name,
- AI tool call nie je event listener; používa oddelený allow-listed tool registry.

---

## 10. Events a cache/index

Index/cache maintenance má jasné vlastníctvo. Kritická konzistencia nemá závisieť iba od third-party hooku. Odporúčaný model:

- application service po SSOT write vykoná povinnú index/cache operáciu alebo vytvorí interný recovery stav,
- následne emituje event pre voliteľné side effects,
- rebuild zostáva dostupný aj pri strate eventu.

---

## 11. Versioning a kompatibilita

Verejný hook contract používa schema version. Kompatibilná zmena pridá optional field. Breaking zmena potrebuje nový názov alebo major schema version, deprecation obdobie a migration guide.

Extension manifest s nepodporovaným hookom alebo min/max CMS version sa neaktivuje potichu. Admin UI ukáže dôvod a bezpečný stav disabled.

---

## 12. Testy

- registrácia a disable listenera,
- manifest odmietne neznámy hook/handler,
- payload schema a secret exclusion,
- before veto bez partial write,
- after failure zachová SSOT a vytvorí incident,
- duplicate delivery/idempotency,
- recursion limit,
- handler timeout pre async/outbound flow,
- persistent worker re-bootstrap bez ghost listenerov,
- SK/EN hook catalog parity.

---

## Súvisiace dokumenty

- [CORE.md](./CORE.md)
- [MODULES.md](./MODULES.md)
- [PLUGINS.md](./PLUGINS.md)
- [CORE_HARDENING.md](./CORE_HARDENING.md)
- [../ITERATION_15D.md](../ITERATION_15D.md)
