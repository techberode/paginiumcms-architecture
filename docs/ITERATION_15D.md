# Iteration 15d – Hook emitters & extension policy (Wave 5d)

> **Release:** **2.0.54** (lokálne, C&P pending)  
> **Stav:** ✅ Shipped (kód)  
> **Naväzuje na:** It.15 (2.0.38) – PluginManager, import ZIP, HookManager bez emitterov

---

## Cieľ

Dokončiť plugin runtime: Core **emituje** hooky, extensions sa **prihlasujú** cez manifest.
Pridať referenčný plugin `hello-widget` a samostatnú **Extension Code Policy**.

---

## Backend

### Nové triedy

| Trieda | Úloha |
|--------|--------|
| `HookCatalog` | Kanonické názvy hookov + metadata |
| `HookEmitter` | Typed wrapper nad `HookManager` |
| `ExtensionManifestValidator` | Validácia `plugin.json` (id, hooky, minCmsVersion) |
| `AppVersion` | CMS semver (`2.0.54`) |

### Emittery v Core

- `ContentController` — `content.before_save`, `content.after_save`, `content.after_delete`, `content.after_status_change`
- `ContentScheduledPublishService` — `content.after_scheduled_publish`
- `PluginManager` — `extension.boot`, `extension.enabled`, `extension.disabled`

### Referenčný plugin

```
backend/app/Http/Extensions/hello-widget/
backend/app/Http/Routes/extensions/hello-widget.php
frontend/src/extensions/hello-widget/index.ts
```

Endpoint (enabled): `GET /api/extensions/hello-widget/ping`

---

## Dokumentácia

- [`docs/developer/EXTENSION_CODE_POLICY.md`](developer/EXTENSION_CODE_POLICY.md) — politika pre pluginy/témy/moduly
- Aktualizácia [`docs/architecture/PLUGINS.md`](architecture/PLUGINS.md)
- [`docs/CONTINUATION.md`](CONTINUATION.md) — wave 5d ✅

---

## Testy

- `HookEmitterTest`
- `ExtensionManifestValidatorTest`
- `HelloWidgetReferencePluginTest`
- Aktualizované `PluginManagerTest`, `PluginImporterTest`
- **ISS-075** — `PluginManagerTest` používa `ping-demo` namiesto kolízie s referenčným `hello-widget`

---

## Smoke test

1. Admin → Extensions → enable **hello-widget**
2. `curl /api/extensions/hello-widget/ping` → `{ "success": true, "message": "pong" }`
3. Uložiť stránku → hook `content.after_save` (HelloWidget `$lastContentContext` v PHPUnit)
