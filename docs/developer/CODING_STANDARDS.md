# PaginiumCMS – Coding Standards & Politika validácie kódu

> **Verzia:** 2.0.0 (Iterácia 14 – plánovaná implementácia `CodePolicyEngine`)  
> **Platí pre:** jadro CMS, moduly, externé doplnky, témy, kód upravovaný cez CodeEditor.

Tento dokument je **jediný zdroj pravdy** pre ľudské aj automatické kontroly kvality kódu.
Keď sa v Iterácii 14 implementuje `CodePolicyEngine`, tieto pravidlá sa vynucujú
automaticky pred každým zápisom súboru (CodeEditor, import doplnku, scaffold).

**Extensions (pluginy/témy/moduly):** detailná politika v [`EXTENSION_CODE_POLICY.md`](EXTENSION_CODE_POLICY.md).

Súvisiace dokumenty: `docs/architecture/PLUGINS.md`, `docs/CONTINUATION.md` (ZÁKONY §2),
`docs/developer/CONTRIBUTING.md`, `docs/architecture/SETTINGS.md` (skupina `codePolicy`).

---

## 1. Všeobecné štandardy (jadro CMS)

### 1.1 PHP (backend)

| Pravidlo | Požiadavka |
|---|---|
| Verzia | PHP 8.5+ (projekt cieli 8.5), `declare(strict_types=1);` v každom súbore |
| Typovanie | Plné type hints, PHPStan **level 8**, 0 chýb v CI |
| Namespaces | `PaginiumCMS\{Core\|Http\|Modules\|…}\…` – absolútne cesty k súborom v kóde |
| Štruktúra | Jadro v `backend/app/Core/`, HTTP v `backend/app/Http/`, doména v `backend/app/Modules/` |
| DI | Služby registrované v `Http/Config/services.php` alebo modulovom `Config/services.php` |
| Routy | Auto-discovery z `backend/app/Http/Routes/*.php` – **nie** natvrdo v `bootstrap/app.php` |
| Flat-file | Žiadna SQL DB; stav v JSON/Markdown/asset súboroch, súbežnosť cez `flock(LOCK_EX)` |
| Výnimky | `ValidationException` → 422; ostatné cez `ApiErrorHandler` |
| Testy | PHPUnit ku každému novému modulu; izolované temp adresáre pre `flock` služby |

**Povinná hlavička súboru (nový kód):**

```php
<?php

declare(strict_types=1);

namespace PaginiumCMS\…;
```

### 1.2 TypeScript / React (frontend)

| Pravidlo | Požiadavka |
|---|---|
| Verzia | React funkcionálne komponenty, striktný TypeScript |
| API klient | `frontend/src/api/{modul}.ts` – typované volania cez `apiClient` |
| Stavy | Loading / Success / Error pri každom async volaní |
| Validácia | Zdieľané pravidlá cez `utils/validation.ts` (zrkadlo backend `Validator`) |
| Testy | Vitest + Testing Library pre algoritmy a komponenty |
| Backend = SSOT | Frontend nikdy nepredpokladá stav, ktorý backend nepotvrdil |

### 1.3 Dokumentácia

- Každá iterácia / modul → aktualizácia `docs/ROADMAP.md`, `docs/CONTINUATION.md`, príslušného `architecture/*.md`.
- Nový endpoint → riadok v `docs/architecture/API.md` (mapovanie endpoint ↔ FE).
- Radikálna architektonická zmena → najprv návrh a schválenie.

---

## 2. Architektonické ZÁKONY

Tieto tri pravidlá majú **vyššiu prioritu** než lokálne konvencie modulu.

### ZÁKON 1 – Externé doplnky mimo Jadra Backendu

Všetok kód doplnkov vytvorených mimo jadra CMS patrí **výhradne do HTTP vrstvy**:

```text
backend/app/Http/Extensions/{plugin-id}/
backend/app/Http/Routes/extensions/{plugin-id}.php
frontend/src/extensions/{plugin-id}/
data/plugins.json                    # flat-file register
```

**Nikdy nepísať doplnky do:** `backend/app/Core/`, `backend/bootstrap/`, `backend/vendor/`.

### ZÁKON 2 – API endpoint = kompatibilný FE súbor

Pri vytvorení nového API endpointu **súčasne** vznikne:

1. Route v `Http/Routes/` (alebo `Http/Routes/extensions/`)
2. Controller (ak treba)
3. `frontend/src/api/{modul}.ts` – typovaný klient
4. Komponent alebo route v `components/` alebo `extensions/`
5. Záznam v `docs/architecture/API.md`

Endpoint bez FE klienta = **neúplná implementácia** (CI lint v It. 17).

### ZÁKON 3 – Politika kódu pred zápisom

Žiadny vlastný kód (modul, téma, doplnok, konfigurácia upravená v CodeEditore) sa
**neuloží na disk**, kým neprejde `CodePolicyEngine`. Zlyhanie → HTTP **422** s mapou chýb.

---

## 3. Politika validácie a kompatibility kódu (`CodePolicyEngine`)

> **Stav implementácie:** Iterácia 14 (plánovaná).  
> **Dnes:** len `SyntaxChecker` (PHP lint, JSON, YAML) + allow/deny cesty v `CodeEditorManager`.  
> **Po It. 14:** plný trojstupňový pipeline nižšie.

### 3.1 Pipeline (poradie kontrol)

```
Vstup (path + content)
    │
    ▼
[1] PATH POLICY     – povolená cesta? (allow/deny, path traversal)
    │
    ▼
[2] SYNTAX          – SyntaxChecker (php -l, json_decode, Yaml::parse, …)
    │
    ▼
[3] SECURITY        – zakázané konštrukty, nebezpečné funkcie
    │
    ▼
[4] COMPATIBILITY   – namespaces, importy, manifest doplnku, verzia CMS
    │
    ▼
OK → zápis (+ FileBackup)   |   FAIL → 422, žiadny zápis
```

### 3.2 Povolené a zakázané cesty (CodeEditor)

| Typ | Cesta (relatívne od koreňa projektu) | Poznámka |
|---|---|---|
| ✅ Povolené | `backend/app/Modules/` | Doménové moduly (nie Core) |
| ✅ Povolené | `backend/app/Http/Extensions/` | **Cieľová cesta doplnkov (ZÁKON 1)** |
| ✅ Povolené | `backend/resources/views/themes/` | CMS témy vzhľadu |
| ✅ Povolené | `backend/config/` | Konfigurácia (obmedzená) |
| ⛔ Zakázané | `backend/app/Core/` | Jadro – len cez release, nie CodeEditor |
| ⛔ Zakázané | `backend/bootstrap/` | Bootstrap aplikácie |
| ⛔ Zakázané | `backend/vendor/` | Composer závislosti |
| ⛔ Zakázané | `..`, absolutné cesty, symlinky mimo root | Path traversal |

> **Migrácia:** stará cesta `backend/plugins/` sa nahradí `backend/app/Http/Extensions/`.

### 3.3 Syntax – podporované typy súborov

| Prípona | Kontrola (dnes) | Kontrola (It. 14+) |
|---|---|---|
| `.php` | `php -l` | `php -l` + security scan |
| `.json` | `json_decode` | + schema pre `plugin.json`, manifesty |
| `.yaml`, `.yml` | Symfony Yaml | + schema kde definované |
| `.js`, `.ts`, `.tsx` | vždy OK ⚠️ | ESLint / `tsc --noEmit` (pravidlá nižšie) |
| `.css` | vždy OK ⚠️ | Stylelint (základné pravidlá) |
| `.html`, `.htm` | vždy OK ⚠️ | základná well-formed kontrola |

### 3.4 Security policy – PHP (It. 14+)

Nasledujúce konštrukty a funkcie sú **zakázané** v kóde doplnkov a v súboroch
upravovaných cez CodeEditor (whitelist jadra môže byť širší – konfigurovateľné v `codePolicy`):

**Zakázané jazykové konštrukty:**

- `eval`, `assert` (s reťazcom), `create_function`
- `include` / `require` s dynamickou cestou mimo whitelistu doplnku
- `preg_replace` s modifikátorom `/e` (historické)
- `$$` variabilné premenné (voliteľne – prísnejší režim)

**Zakázané funkcie (výber – plný zoznam v `ValidationRules` / settings):**

```text
exec, shell_exec, system, passthru, popen, proc_open, pcntl_exec,
dl, putenv, ini_set (vybrané direktívy), mail (v doplnkoch – použiť NotificationService),
file_get_contents s URL, curl_exec priamo (použiť schválený HTTP klient),
unserialize (bez allowed_classes), extract, parse_str bez druhého argumentu
```

**Povolené výnimky:** explicitný whitelist v `plugin.json` → `"securityExceptions": []`
(schvaľuje SUPER_ADMIN, loguje sa do audit trail).

### 3.5 Kompatibilita – PHP doplnky (It. 14+)

| Pravidlo | Detail |
|---|---|
| Namespace | Musí začínať `PaginiumCMS\Http\Extensions\{PluginId}\` |
| Autoload | PSR-4 v rámci priečinka doplnku; **žiadny** `require` do Core |
| Hooks | Registrácia len cez manifest + `HookManager` – nie priame volanie Core private API |
| Verzia CMS | `plugin.json` → `"minCmsVersion"` musí byť ≤ aktuálna verzia |
| Závislosti | Len Composer balíky už prítomné v root `composer.json` doplnku (It. 15+) |
| Veľkosť súboru | Max. 512 KB na súbor (konfigurovateľné v `codePolicy.maxFileSizeKb`) |

### 3.6 Kompatibilita – Frontend doplnky (It. 14+)

| Pravidlo | Detail |
|---|---|
| Umiestnenie | `frontend/src/extensions/{plugin-id}/` |
| Importy | Povolené: React, existujúce `api/*`, `hooks/*`, `utils/*`. **Zakázané:** priamy import z `Core` backend ciest |
| API | Vlastné volania len cez `api.ts` doplnku; base URL z `apiClient` |
| Typy | Striktné TS; žiadne `any` v exportovanom API doplnku |
| Side effects | Žiadny zápis do `localStorage` okrem namespaced kľúča `{plugin-id}:*` |

### 3.7 Konfigurácia politiky (`settings.json` – skupina `codePolicy`, It. 14+)

Plánované polia (schéma sa pridá do `SettingsSchema`):

```json
{
  "codePolicy": {
    "enabled": true,
    "strictMode": true,
    "maxFileSizeKb": 512,
    "allowJsInExtensions": true,
    "forbiddenPhpFunctions": ["exec", "shell_exec", "system", "eval"],
    "requirePluginManifest": true,
    "scanOnImport": true
  }
}
```

---

## 4. Štandardy pre externé doplnky

### 4.1 Manifest `plugin.json` (povinný)

```json
{
  "id": "my-plugin",
  "name": "My Plugin",
  "version": "1.0.0",
  "author": "Autor",
  "description": "Krátky popis",
  "minCmsVersion": "2.0.0",
  "hooks": {},
  "routes": true,
  "frontend": true
}
```

Validácia manifestu: JSON schema + `CodePolicyEngine` pred enable/import.

### 4.2 Pomenovanie

| Prvok | Konvencia | Príklad |
|---|---|---|
| Plugin ID | `kebab-case`, len `a-z0-9-` | `weather-widget` |
| PHP namespace | `PaginiumCMS\Http\Extensions\WeatherWidget` | PascalCase posledný segment |
| FE priečinok | `frontend/src/extensions/weather-widget/` | = plugin ID |
| Route súbor | `Http/Routes/extensions/weather-widget.php` | = plugin ID |

### 4.3 Import ZIP (It. 15)

1. Upload → dočasný adresár  
2. `CodePolicyEngine` skenuje **všetky** súbory  
3. PASS → presun do `Http/Extensions/{id}/`, zápis do `data/plugins.json`  
4. FAIL → odmietnutie, žiadny súbor sa nepresunie, report chýb v odpovedi 422  

---

## 5. CodeEditor – pravidlá pre vývojárov

### 5.1 Prístup

- Role: **ADMIN** / **SUPER_ADMIN**
- **Developer Mode** musí byť odomknutý (`POST /api/admin/developer/unlock` – TOTP alebo dev-token, TTL 8 h)
- Každý read/write logovaný (`CodeEditorLogger` – zapojenie v It. 14)

### 5.2 Workflow uloženia

1. FE: voliteľná predvalidácia (syntax hint)  
2. `PUT /api/admin/code-editor/save`  
3. Backend: PATH → SYNTAX → SECURITY → COMPATIBILITY  
4. OK: `FileBackup` → zápis → audit log  
5. FAIL: 422 `{ success: false, error, errors: { policy: [...], line: [...] } }`  

### 5.3 Čo **nie je** dovolené robiť v CodeEditore

- Upravovať súbory v `Core/`, `bootstrap/`, `vendor/`
- Obchádzať politiku (priamy zápis na disk mimo API)
- Vkladať credentials, API kľúče, `.env` hodnoty do sledovaných súborov
- Commitovať do repozitára bez PHPUnit + PHPStan + Vitest (pre zmeny jadra)

---

## 6. Mapovanie nástrojov

| Nástroj | Úroveň | Kedy |
|---|---|---|
| **PHPStan L8** | Jadro, Modules | CI, každý commit do Core/Http/Modules |
| **PHPUnit** | Jadro, Modules, Extensions | CI; povinné pre nové služby |
| **Vitest** | Frontend | CI; algoritmy + komponenty |
| **SyntaxChecker** | CodeEditor save | Dnes – PHP/JSON/YAML |
| **CodePolicyEngine** | CodeEditor, import doplnkov | It. 14+ – plný pipeline |
| **Validator / ValidationRules** | HTTP formuláre (obsah, user, settings) | Hotové (It. 4) |
| **ESLint / tsc** | FE doplnky, extensions | It. 14+ |

---

## 7. Checklist pred merge (CONTRIBUTING)

**Jadro / modul v repozitári:**

- [ ] `declare(strict_types=1);`, PHPStan L8 = 0 chýb  
- [ ] PHPUnit testy pre novú logiku  
- [ ] Route v `Http/Routes/` (nie duplicita v bootstrap)  
- [ ] DI v `Http/Config/services.php`  
- [ ] FE: `api/*.ts` + komponent ak existuje endpoint (**ZÁKON 2**)  
- [ ] Dokumentácia aktualizovaná  

**Externý doplnok:**

- [ ] Kód len v `Http/Extensions/{id}/` (**ZÁKON 1**)  
- [ ] `plugin.json` validný  
- [ ] Prešiel `CodePolicyEngine` (import aj save)  
- [ ] FE v `frontend/src/extensions/{id}/`  
- [ ] Hook registrácia cez manifest, nie hardcoded  

---

## 8. Chybové kódy politiky (It. 14+)

| Kód | HTTP | Význam |
|---|---|---|
| `PATH_FORBIDDEN` | 403 | Cesta mimo allowlist alebo v denylist |
| `SYNTAX_ERROR` | 422 | PHP/JSON/YAML/JS syntax |
| `SECURITY_VIOLATION` | 422 | Zakázaná konštrukcia alebo funkcia |
| `COMPATIBILITY_ERROR` | 422 | Namespace, manifest, verzia CMS |
| `FILE_TOO_LARGE` | 422 | Prekročený `maxFileSizeKb` |

Odpoveď vždy v tvare jednotného Error Handlera:

```json
{
  "success": false,
  "error": "Politika kódu: súbor neprešiel kontrolou.",
  "errors": {
    "security": ["Zakázaná funkcia: exec()"],
    "line": ["Riadok 42: volanie exec()"]
  }
}
```

---

## 9. Plán implementácie (referencia)

| Iterácia | Čo sa z tohto dokumentu implementuje |
|---|---|
| **It. 14** | `CodePolicyEngine`, security scan, rozšírený `SyntaxChecker`, FE policy errors, `codePolicy` v settings |
| **It. 15** | Validácia pri importe doplnku, `PluginManager` |
| **It. 16** | Monaco + policy gate v UI, editácia modulov/tém |
| **It. 17** | Scaffold vynucujúci ZÁKON 2, CI lint endpoint↔FE | ✅ MVP Wave 5e — `lint:api-barrel` + [CONTRIBUTING.md](CONTRIBUTING.md) |

---

*Posledná aktualizácia: po Iterácii 4, pred implementáciou CodePolicyEngine (It. 14).  
Pri implementácii It. 14 aktualizovať §3 stĺpec „dnes“ → „hotové“ a doplniť presné cesty tried.*
