---
title: Iterácia 15 – Externé pluginy a runtime
description: Historický záznam ZIP importu, plugin registry, hook runtime, extension routes a admin UI
icon: material/history
---

# Iterácia 15 – Externé pluginy a runtime

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.38 |
| Typ záznamu | historická extension iterácia |

## Architektonický zákon

Plugin kód nesmie byť súčasťou `Core/`:

```text
backend/app/Http/Extensions/{id}/      PHP extension kód
backend/app/Http/Routes/extensions/    routy aktívnych extensions
frontend/src/extensions/{id}/          frontend zdroj zahrnutý do build procesu
```

Toto oddelenie chráni platformové primitives pred priamym vlastníctvom externého kódu.

## Dodaný rozsah

| Oblasť | Komponent |
|---|---|
| Registry | `data/plugins.json`, `PluginRegistry`, `flock` |
| Import | `PluginImporter`, policy scanner, `CodePolicyEngine` |
| Lifecycle | list/import/enable/disable/uninstall |
| Runtime | `PluginManager`, `HookManager`, enabled extension routes |
| API/UI | `/api/admin/extensions*`, `ExtensionsManager`, sidebar |
| Frontend discovery | `frontend/src/extensions/loader.ts` cez `import.meta.glob` |

## Dôležitá hranica frontend runtime

`import.meta.glob` je **build-time discovery**. Import PHP pluginu môže byť runtime operácia, ale nový React/TypeScript bundle sa bez build/redeploy kroku automaticky neobjaví. Aktuálny kontrakt je v [PLUGINS.md](architecture/PLUGINS.md).

## Import a bezpečnosť

ZIP import vyžaduje manifest, path canonicalization, Zip-Slip/symlink ochranu, limity a staged scan. Plugin zostáva po importe vypnutý a aktivácia je samostatná autorizovaná operácia.

## Testy

Registry, manager, importer, controller a frontend loader mali samostatné testy. Iteračný gate spájal relevantné backend/frontend kontroly.

