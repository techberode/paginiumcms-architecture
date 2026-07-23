# PaginiumCMS – Doplnky a rozšírenia (Extensions)

> Plánovaná architektúra doplnkov. Implementácia: Iterácie 14–17 (`docs/CONTINUATION.md`).

---

## Architektonické ZÁKONY

### 1. Externé doplnky mimo Jadra Backendu

Všetky doplnky vytvorené mimo jadra CMS sa ukladajú a importujú **výhradne do HTTP vrstvy**:

```text
backend/app/Http/Extensions/{plugin-id}/
├── plugin.json          # manifest (názov, verzia, hooks, routes)
├── src/                 # PHP triedy doplnku
└── ...

backend/app/Http/Routes/extensions/{plugin-id}.php   # auto-discovered routes

frontend/src/extensions/{plugin-id}/
├── api.ts               # typovaný klient
├── components/          # React komponenty
└── index.ts             # entry point
```

**Zakázané umiestnenia:** `backend/app/Core/`, `backend/bootstrap/`, `backend/vendor/`

### 2. ZÁKON API↔FE

Každý nový API endpoint musí mať zodpovedajúci frontendový súbor:

| Backend | Frontend |
|---|---|
| `Http/Routes/extensions/foo.php` | `frontend/src/extensions/foo/api.ts` |
| Controller metóda | Komponent alebo hook |
| — | Záznam v `docs/architecture/API.md` |

### 3. Politika kódu (CodePolicyEngine)

Žiadny vlastný kód (modul, téma, doplnok) sa neuloží bez validácie:

1. **Syntax** – PHP `php -l`, JSON, YAML (existujúci `SyntaxChecker`)
2. **Security** – zakázané konštrukty (`eval`, `exec`, `system`, `passthru`, `shell_exec`, …)
3. **Kompatibilita** – whitelist namespaces, povolené súborové typy

Zlyhanie → HTTP 422, žiadny zápis na disk.

---

## Súčasný stav (audit)

| Oblasť | Stav |
|---|---|
| `HookManager` | ✅ V DI, boot pri enabled extensions |
| `PluginManager` | ✅ `Http/Extensions/Services/PluginManager` |
| `backend/app/Http/Extensions/` | ✅ Runtime + imported `{id}/` |
| `data/plugins.json` registry | ✅ Flock-protected flat-file |
| Import ZIP + policy | ✅ `POST /api/admin/extensions/import` |
| Extension routes | ✅ Auto-load pre enabled (`Http/Routes/extensions/{id}.php`) |
| Admin UI | ✅ `/extensions` — `ExtensionsManager` |
| Hook emitters v Core | ✅ `HookEmitter` + `HookCatalog` (Wave 5d) |
| Referenčný plugin | ✅ `hello-widget` v repozitári |
| Extension code policy | ✅ `docs/developer/EXTENSION_CODE_POLICY.md` |
| CMS témy | **Chýba** (len admin dark/light v `ThemeContext`) |

---

## Plánovaný workflow

### Vytvorenie doplnku cez CodeEditor (It. 17)

1. Developer Mode unlock (TOTP / dev-token)
2. Wizard vygeneruje štruktúru podľa ZÁKONU
3. Kód sa píše v CodeEditori (Monaco)
4. Pred save → `CodePolicyEngine` validácia
5. Po schválení → zápis do `Http/Extensions/{id}/`

### Import existujúceho doplnku (It. 15)

1. Upload ZIP cez `POST /api/admin/extensions/import`
2. Rozbalenie do dočasného adresára
3. `CodePolicyEngine` skenuje všetky súbory
4. Prejde → presun do `Http/Extensions/{id}/`, registrácia v `data/plugins.json`
5. Zlyhá → odmietnutie + report chýb

### Inštalácia / aktivácia

- `PUT /api/admin/extensions/{id}/enable` – načíta hooks, zaregistruje routes
- `PUT /api/admin/extensions/{id}/disable` – odpojí hooks
- `DELETE /api/admin/extensions/{id}` – odinštaluje (s potvrdením)

---

## Manifest `plugin.json` (návrh)

```json
{
  "id": "my-widget",
  "name": "My Widget",
  "version": "1.0.0",
  "author": "Developer",
  "description": "Príklad doplnku",
  "hooks": {
    "content.after_save": "MyWidget\\Hooks::afterSave"
  },
  "routes": true,
  "frontend": true,
  "minCmsVersion": "2.0.0"
}
```

---

*Podrobný fázový plán: `docs/CONTINUATION.md` §3, Iterácie 14–17.*  
*Coding standards a politika kódu: `docs/developer/CODING_STANDARDS.md`.*
