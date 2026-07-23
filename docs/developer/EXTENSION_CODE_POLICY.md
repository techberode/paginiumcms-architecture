# PaginiumCMS – Extension Code Policy

> **Verzia:** 1.0.0 (Wave **5d** / It.15 doplnenie)  
> **Platí pre:** externé **pluginy**, **témy** (budúce), **moduly** tretích strán, ZIP import, CodeEditor scaffold.

Tento dokument je **záväzná politika** pre kód mimo jadra CMS. Doplňuje
[`CODING_STANDARDS.md`](CODING_STANDARDS.md) §2–4 a [`../architecture/PLUGINS.md`](../architecture/PLUGINS.md).

Automatické vynucovanie: `CodePolicyEngine` + `PluginPolicyScanner` + `ExtensionManifestValidator`.

---

## 1. Kde smie extension žiť (ZÁKON umiestnenia)

| Typ | Backend | Routes | Frontend | Register |
|-----|---------|--------|----------|----------|
| **Plugin** | `backend/app/Http/Extensions/{id}/` | `backend/app/Http/Routes/extensions/{id}.php` | `frontend/src/extensions/{id}/` | `data/plugins.json` |
| **Téma** (plánované) | `backend/app/Http/Themes/{id}/` | voliteľné | `frontend/src/themes/{id}/` | `data/themes.json` |
| **Modul** (plánované) | `backend/app/Http/Modules/{id}/` | `Http/Routes/modules/{id}.php` | `frontend/src/modules/{id}/` | `data/modules.json` |

**Zakázané:** `backend/app/Core/`, `backend/bootstrap/`, `backend/vendor/`, priamy zápis do `data/` mimo schváleného registra.

Referenčný plugin v repozitári: **`hello-widget`** (Wave 5d).

---

## 2. Identita a manifest (`plugin.json`)

Povinné polia:

| Pole | Pravidlo |
|------|----------|
| `id` | kebab-case `[a-z0-9]+(-[a-z0-9]+)*`, musí zodpovedať názvu priečinka |
| `name` | ľudsky čitateľný názov |
| `version` | semver string |
| `minCmsVersion` | voliteľné; ak je vyššie než `PaginiumCMS\Support\AppVersion::VERSION`, import/enable zlyhá |

Voliteľné: `description`, `author`, `routes` (bool), `frontend` (bool), `hooks` (mapa).

Príklad (referenčný):

```json
{
  "id": "hello-widget",
  "name": "Hello Widget",
  "version": "1.0.0",
  "minCmsVersion": "2.0.38",
  "hooks": {
    "extension.boot": "PaginiumCMS\\Http\\Extensions\\HelloWidget\\Hooks::onBoot",
    "content.after_save": "PaginiumCMS\\Http\\Extensions\\HelloWidget\\Hooks::onContentAfterSave"
  },
  "routes": true,
  "frontend": true
}
```

---

## 3. Hooky – len z katalógu

Core emituje udalosti cez `HookEmitter`. Extensions sa **len prihlasujú** v manifeste.

Povolené hooky (`HookCatalog`):

| Hook | Kedy | Payload |
|------|------|---------|
| `content.before_save` | Pred uložením obsahu | `type`, `slug`, `status`, `action`, `userId` |
| `content.after_save` | Po úspešnom uložení | rovnaké |
| `content.after_delete` | Po zmazaní | `type`, `slug`, `userId` |
| `content.after_status_change` | Po zmene stavu | + `previousStatus` |
| `content.after_scheduled_publish` | Cron auto-publish | `type`, `slug`, `scheduledAt` |
| `extension.boot` | Boot enabled extension | `id`, `manifest` |
| `extension.enabled` | Admin enable | `id`, `manifest` |
| `extension.disabled` | Admin disable | `id` |

**Pravidlá:**

- Handler musí byť `callable` string (`Class::method`) alebo statická metóda v načítanej triede.
- Signatúra handlera: `function (array $context): void` (návratová hodnota sa ignoruje).
- Neznámy hook v manifeste → **RuntimeException** pri import/enable.
- Extensions **nesmú** volať `HookManager::add()` priamo – len cez `plugin.json`.

---

## 4. PHP kód extension

| Pravidlo | Detail |
|----------|--------|
| `strict_types` | Povinné v každom `.php` |
| Namespace | `PaginiumCMS\Http\Extensions\{PascalCaseId}\…` |
| DI | Extensions **nepoužívajú** vlastný DI kontajner; statické hook handlery alebo factory v `src/` |
| Core import | Povolené **readonly** služby cez dokumentované hook payloady; **zakázané** priame volanie interných Core tried mimo public API/hookov |
| Bezpečnosť | Zakázané: `eval`, `exec`, `shell_exec`, `passthru`, `system`, `proc_open`, `popen`, `assert` s reťazcom |
| Súbory | Povolené: `.php`, `.json`, `.md`, `.txt`, assety (`.png`, `.svg`, …) v manifeste deklarované |

Syntax + security scan: `PluginPolicyScanner` pred inštaláciou ZIP.

---

## 5. Routes extension

- Súbor: `routes.php` v koreni pluginu → pri importe skopírovaný do `Http/Routes/extensions/{id}.php`.
- Načítava sa **len ak** je plugin v `data/plugins.json` s `enabled: true`.
- Musí vracať `function (Slim\App $app): void`.
- Autentifikácia: verejné routy explicitne bez middleware; admin routy musia použiť rovnaké middleware ako core admin API (`AuthMiddleware`, `RoleMiddleware`, …).
- **ZÁKON API↔FE:** nový endpoint = záznam v `docs/architecture/API.md` + FE klient v `frontend/src/extensions/{id}/`.

Referencia: `GET /api/extensions/hello-widget/ping` (hello-widget enabled).

---

## 6. Frontend extension

- Entry: `frontend/src/extensions/{id}/index.ts` (bundled cez `import.meta.glob` v `loader.ts`).
- TypeScript strict, žiadne `any` v exportovanom API.
- API volania len cez `apiClient` alebo lokálny `api.ts` v extension priečinku.
- Extension **nesmie** patchovať core bundle ani prepisovať globálne CSS bez prefixu `{id}-`.

Témy (budúce): layout sloty + CSS variables; rovnaká policy scan pipeline.

---

## 7. Import a lifecycle

```
ZIP upload → extract (Zip-Slip check) → ExtensionManifestValidator
    → PluginPolicyScanner → copy files → plugins.json (enabled: false)
Enable → validate manifest → load PHP → register hooks → extension.enabled + extension.boot
Disable → extension.disabled → unregister hooks
Uninstall → remove files + registry entry
```

Chyba validácie → HTTP **422** (`CodePolicyViolationException`) alebo **400** (manifest).

---

## 8. Testovanie extension

Minimálny balík pred publikovaním:

1. `plugin.json` prechádza `ExtensionManifestValidatorTest` pattern
2. Hook handlery unit test v `tests/` extension alebo integračný test s dočasným adresárom
3. Ak má routes – smoke test endpointu
4. Ak má FE – Vitest pre exportované funkcie/komponenty

**PHPUnit:** test extension v temp adresári **nesmie** zdieľať PHP namespace s bundled referenčným pluginom v repozitári (pozri **ISS-075** — `ping-demo` vs `hello-widget`).

Referencia: `backend/tests/Http/Extensions/HelloWidgetReferencePluginTest.php`.

---

## 9. Súvisiace súbory

| Súbor | Úloha |
|-------|--------|
| `backend/app/Core/Hook/HookCatalog.php` | Kanonické názvy hookov |
| `backend/app/Core/Hook/Services/HookEmitter.php` | Core emitter |
| `backend/app/Http/Extensions/Services/ExtensionManifestValidator.php` | Manifest policy |
| `backend/app/Http/Extensions/Services/PluginPolicyScanner.php` | Sken kódu |
| `backend/app/Support/AppVersion.php` | CMS semver pre `minCmsVersion` |
| `docs/architecture/PLUGINS.md` | Architektúra a workflow |

---

## 10. Checklist pre autora extension

- [ ] `id` kebab-case, priečinky zodpovedajú manifestu
- [ ] Len hooky z `HookCatalog`
- [ ] `declare(strict_types=1);` vo všetkých PHP súboroch
- [ ] Žiadne zakázané PHP funkcie
- [ ] Routes + FE ak deklarované (`routes` / `frontend`)
- [ ] `minCmsVersion` nastavené realisticky
- [ ] Dokumentácia / README v extension ZIP (odporúčané)
