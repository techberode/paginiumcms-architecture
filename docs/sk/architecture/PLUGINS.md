---
title: Pluginy a externé rozšírenia
description: Bezpečnostné a runtime hranice extension systému PaginiumCMS
icon: material/puzzle-outline
---

# Architektúra pluginov a externých rozšírení

> **Stav v `v2.1.0-beta.23`:** backendový plugin runtime, ZIP import, flat-file register, lifecycle a hook emitters sú implementované.  
> **Dôležité obmedzenie:** frontendový extension kód bundlovaný cez Vite nie je automaticky načítateľný z ľubovoľného ZIP-u bez build/redeploy kroku.

Plugin rozširuje PaginiumCMS bez zmeny platformového Core. Beží však v rovnakom PHP procese ako CMS, takže „plugin“ neznamená bezpečný sandbox. Architektúra preto používa obmedzené umiestnenie, manifest, fail-closed import, allow-list hookov, explicitné route middleware a auditovaný lifecycle.

---

## 1. Terminológia a hranice

| Pojem | Význam |
|------|--------|
| **Core** | platformové primitives a invarianty; externý kód sem nesmie zapisovať |
| **Interný modul** | dôveryhodná doménová funkcionalita dodaná spolu s CMS |
| **Plugin / extension** | voliteľný externý balík importovaný a spravovaný cez extension lifecycle |
| **Theme package** | budúci prezentačný balík; nie to isté ako implementovaná farebná schéma |
| **Hook** | allow-listovaný extension bod emitovaný platformou |
| **Extension route** | Slim route patriaca pluginu a načítaná iba pri jeho aktivácii |

Externý plugin sa nestáva interným modulom tým, že má viac súborov. Nemá nárok na interné Core namespaces, bootstrap, vendor, generický filesystem ani neobmedzený DI kontajner.

---

## 2. Kanonické umiestnenie

```text
backend/app/Http/Extensions/{plugin-id}/
├── plugin.json
├── src/
├── routes.php             # voliteľné; importované do centrálnej route vrstvy
├── assets/                # voliteľné statické assety
├── frontend/              # zdroj FE balíka; vyžaduje podporovaný build/deploy flow
├── README.md
└── tests/                 # odporúčané

backend/app/Http/Routes/extensions/{plugin-id}.php
frontend/src/extensions/{plugin-id}/
data/plugins.json
```

Zakázané ciele:

```text
backend/app/Core/
backend/bootstrap/
backend/vendor/
data/ mimo PluginRegistry alebo dokumentovaného namespaced storage kontraktu
ľubovoľná cesta mimo project root
```

Presun alebo symlink nesmie obísť canonical-path kontrolu. ZIP entry s absolútnou cestou, `..`, nulovým bajtom alebo symlinkovým únikom sa odmietne.

---

## 3. Aktuálne implementované komponenty

| Komponent | Úloha | Stav |
|-----------|-------|------|
| `PluginRegistry` | flock-chránený flat-file register `data/plugins.json` | ✅ |
| `PluginImporter` | dočasné rozbalenie, validácia a atomické premiestnenie | ✅ |
| `PluginPolicyScanner` | sken všetkých importovaných súborov | ✅ |
| `ExtensionManifestValidator` | identita, verzia, hooky a kompatibilita | ✅ |
| `PluginManager` | list/import/enable/disable/uninstall/boot | ✅ |
| `HookManager` + `HookEmitter` | registrácia a emitovanie katalógových hookov | ✅ |
| extension routes bootstrap | načítanie rout enabled pluginov | ✅ |
| `/api/admin/extensions/*` | admin lifecycle API | ✅ |
| `ExtensionsManager` | admin používateľské rozhranie | ✅ |
| `hello-widget` | referenčný plugin a testovací kontrakt | ✅ |
| runtime FE loader pre ľubovoľný ZIP | bezpečné načítanie už zostaveného externého UI | ⏳ otvorený kontrakt |

Dokumentácia nesmie zamieňať `frontend/src/extensions/loader.ts` s plnohodnotným runtime marketplace loaderom. `import.meta.glob` je vyhodnotený pri builde; nový zdrojový priečinok v produkcii sa do existujúceho JS bundle nepridá sám.

---

## 4. Manifest `plugin.json`

Minimálny manifest:

```json
{
  "id": "hello-widget",
  "name": "Hello Widget",
  "version": "1.0.0",
  "minCmsVersion": "2.0.54",
  "description": "Reference extension",
  "author": "PaginiumCMS",
  "hooks": {
    "content.after_save": "PaginiumCMS\\Http\\Extensions\\HelloWidget\\Hooks::onContentAfterSave"
  },
  "routes": true,
  "frontend": false
}
```

Povinné pravidlá:

- `id` je kebab-case a zodpovedá názvu adresára,
- `version` je validná SemVer hodnota,
- `minCmsVersion` sa porovná s aplikačnou verziou,
- hooky musia existovať v `HookCatalog`,
- deklarované capability musia mať zodpovedajúce súbory,
- neznáme povinné polia alebo neplatný typ znamenajú odmietnutie,
- manifest neobsahuje tajomstvá, access tokeny ani absolútne lokálne cesty.

Budúce rozšírenie manifestu má používať verziovanú schému, napríklad `manifestVersion`, nie tiché zmeny významu existujúcich polí.

---

## 5. Import pipeline

```text
upload ZIP
  → limity veľkosti/MIME
  → temp adresár
  → Zip-Slip/symlink kontrola
  → manifest schema a identita
  → syntax + security + code policy scan
  → kompatibilita a kolízie
  → atomický presun
  → registry zápis enabled=false
  → audit
```

Zásady:

1. Importovaný plugin je po úspešnom importe **vypnutý**.
2. Validácia je fail-closed; čiastočne skopírovaný plugin nesmie zostať aktívny.
3. Register sa zapisuje pod lockom a cez temp+rename postup.
4. Existujúce ID sa neprepíše bez explicitného upgrade flow.
5. Import a aktivácia sú dve oddelené bezpečnostné udalosti.
6. Chyby politiky vracajú štruktúrované `422`; poškodený manifest typicky `400/422` podľa API kontraktu.
7. Report nesmie vracať secret obsah ani host filesystem cesty.

---

## 6. Lifecycle a stavový model

Odporúčané stavy:

```text
absent → imported_disabled → enabled
                   ↘ incompatible
                   ↘ failed_validation

enabled → disabled → enabled
disabled → upgrading → disabled|failed_upgrade
disabled → uninstalling → absent
```

Aktuálny register môže používať jednoduchší `enabled` boolean; aplikačná služba však musí rozlišovať aspoň import, enable, disable a uninstall failure. Admin UI nemá ukázať úspech, ak sa registry zapísala, ale PHP boot alebo route load zlyhal.

### Enable

1. znovu validovať manifest a kompatibilitu,
2. načítať plugin triedy deterministicky,
3. registrovať iba deklarované hooky,
4. sprístupniť deklarované routes,
5. emitovať `extension.enabled` a pri boote `extension.boot`,
6. uložiť auditný záznam.

### Disable

Disable odstráni runtime registrácie pri ďalšom bezpečnom boot/reload bode. Pluginové dáta sa nemažú. Verejná route alebo hook nesmie zostať aktívny iba kvôli cache starého registra.

### Uninstall

Uninstall je oddelený od disable a vyžaduje potvrdenie. Pred odstránením sa má overiť, či plugin vlastní dáta, export alebo cleanup handler. CMS nesmie spúšťať nedôveryhodný uninstall PHP skript s neobmedzenou autoritou.

---

## 7. Hook kontrakt

Plugin sa prihlasuje iba cez manifest. Priame `HookManager::add()` z náhodného bootstrap súboru je zakázané.

Implementovaný katalóg zahŕňa napríklad:

| Hook | Význam |
|------|--------|
| `content.before_save` | validovaný pokus pred uložením obsahu |
| `content.after_save` | úspešne uložený obsah |
| `content.after_delete` | úspešné zmazanie/soft-delete podľa workflow |
| `content.after_status_change` | zmena lifecycle statusu |
| `content.after_scheduled_publish` | úspešné plánované publikovanie |
| `extension.boot` | boot enabled pluginu |
| `extension.enabled` | úspešná aktivácia |
| `extension.disabled` | úspešná deaktivácia |

Handler má očakávať minimálny versionable payload a nesmie meniť Core objekt cez zdieľanú mutable referenciu. `before_*` hook potrebuje explicitne dokumentovanú failure policy; `after_*` hook nesmie spätne predstierať, že autoritatívny zápis neprebehol.

Pre interné udalosti a public hooky platia odlišné stability pravidlá. Detail: [EVENTS.md](./EVENTS.md).

---

## 8. Routes a autorizácia

Extension route musí používať rovnaké bezpečnostné primitives ako Core API:

- validovaný route input,
- explicitnú public/admin klasifikáciu,
- session + CSRF pri cookie mutácii,
- RBAC/path ACL alebo budúci explicitný It.74 scope,
- rate limit pri verejnom alebo nákladnom endpoint-e,
- štandardný API error contract,
- žiadny implicitný prístup len preto, že route patrí enabled pluginu.

Route súbor musí vracať podporovaný registrar contract a nesmie meniť globálny middleware stack, exception handler alebo DI definície mimo povoleného extension API.

**ZÁKON API↔FE:** endpoint → typovaný klient → používateľský consumer → dokumentácia/contract test. Plugin route bez frontendového consumera môže byť legitímne headless, ale musí to manifest alebo README výslovne deklarovať.

---

## 9. Frontendové rozšírenia

Súčasný zdrojový model:

```text
frontend/src/extensions/{id}/
├── index.ts
├── api.ts
├── components/
└── tests/
```

Pravidlá:

- TypeScript strict a centralizovaný API client,
- žiadne tajomstvá v bundle,
- CSS namespacing alebo schválené design tokeny,
- žiadne patchovanie Core source súborov,
- route/menu/slot registrácia iba cez dokumentovaný FE extension contract,
- sanitizácia HTML a URL rovnaká ako v Core UI.

### Dnešná build hranica

Vite source discovery je build-time. Preto sú bezpečné iba tieto modely:

1. **bundled extension:** plugin je súčasťou repozitára a release buildu,
2. **self-host rebuild:** import administrátorom označí potrebu kontrolovaného rebuild/redeploy,
3. **budúci signed runtime bundle:** samostatný manifest, integrity hash, CSP-compatible loader a kompatibilné ABI.

Model 3 nie je v Public Beta deklarovaný ako implementovaný. Dynamické `eval`, inline script injection alebo načítanie neovereného vzdialeného JS je zakázané.

---

## 10. Dáta a storage

Plugin nesmie zapisovať priamo do náhodného `data/` súboru. Cieľový kontrakt je namespaced repository/storage key, napríklad:

```text
extensions/{plugin-id}/settings
extensions/{plugin-id}/content/{document-id}
extensions/{plugin-id}/state/{key}
```

Autoritatívne pluginové dáta musia byť zahrnuté do backup/exportu. Cache, index alebo odvodený build artifact musí byť označený ako rebuildovateľný. Plugin settings používajú schema registráciu; secrety zostávajú encrypted/write-only a nevystavujú sa vo verejnom settings slice.

---

## 11. Bezpečnostný model

Code policy znižuje riziko, ale nevytvára PHP sandbox. Preto:

- pluginy inštaluje iba privilegovaný administrátor,
- import je nedôveryhodný aj pri lokálnom ZIP-e,
- zakázané funkcie a tokeny sú minimum, nie dôkaz bezpečnosti,
- žiadny generic shell, subprocess, `eval`, dynamický include, neobmedzený outbound alebo filesystem,
- outbound request ide cez SSRF policy a allow-listovaný provider contract,
- audit zaznamená actor, plugin ID/verziu, akciu a výsledok,
- aktivácia môže vyžadovať nedávnu 2FA/OTP podľa security policy,
- produkcia má preferovať podpísané vydania a kontrolované zdroje.

Ak sa časom vyžaduje silná izolácia, potrebuje proces/container boundary alebo capability RPC; samotný namespace a regex scanner nestačí.

---

## 12. Upgrade a kompatibilita

Upgrade musí byť samostatný workflow:

1. validovať nový balík,
2. porovnať ID, verziu a `minCmsVersion`,
3. vytvoriť zálohu starého balíka a pluginových dát,
4. disable alebo maintenance gate podľa typu pluginu,
5. atomicky vymeniť súbory,
6. spustiť iba deklarovanú, obmedzenú data migration,
7. smoke-test boot/routes/hooks,
8. commitnúť registry verziu alebo rollbacknúť.

Downgrade nie je automaticky bezpečný. Breaking zmeny hook payloadu, route kontraktu alebo theme/FE slotu vyžadujú verziovanie a deprecation obdobie.

---

## 13. Testovací kontrakt

Minimálne testy pluginu:

- manifest schema a neplatné fixtures,
- policy scan a Zip-Slip fixtures,
- import/enable/disable/uninstall lifecycle,
- unikátny PHP namespace v testoch,
- hook payload a failure behavior,
- route auth/CSRF/permission matrix,
- frontend API/component test, ak má UI,
- kompatibilita s min/max podporovanou CMS verziou,
- rollback po zlyhanom upgrade alebo boote.

Incident s duplicitnou triedou je zdokumentovaný v [ISS-075](../ISSUES.md#iss-075). Testovací plugin nesmie používať namespace bundled referenčného pluginu.

---

## 14. Prevádzkové odporúčania

- pred importom vytvoriť overenú zálohu,
- plugin najprv skúšať v dev/staging profile,
- po enable spustiť health, route a content save smoke test,
- sledovať PHP logy, audit a latency,
- po zmene FE zdrojov vykonať podporovaný build/redeploy,
- vypnúť plugin pred upgrade alebo diagnostikou,
- neignorovať `incompatible` alebo policy warning ručným presunom súborov.

---

## 15. Mimo aktuálneho kontraktu

Nie je garantované:

- bezpečný marketplace,
- kryptografické podpisovanie všetkých pluginov,
- PHP sandbox,
- autonómny runtime install React bundle bez rebuildu,
- ľubovoľné pluginové DB migrácie,
- prístup k interným Core triedam,
- pluginom riadené auto-publish alebo AI superuser oprávnenie.

---

## Súvisiace dokumenty

- [MODULES.md](./MODULES.md)
- [EVENTS.md](./EVENTS.md)
- [THEMES.md](./THEMES.md)
- [API.md](./API.md)
- [Používateľská príručka pluginov](../user/PLUGINS.md)
- [Extension Code Policy](../developer/EXTENSION_CODE_POLICY.md)
- [Code Editor](../user/CODE_EDITOR.md)
