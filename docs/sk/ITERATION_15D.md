---
title: Iterácia 15d – Hook emitters a extension policy
description: Historický záznam typed hook katalógu, manifest validácie a referenčného pluginu
icon: material/history
---

# Iterácia 15d – Hook emitters a extension policy

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dodané |
| Release / obdobie | 2.0.54 |
| Typ záznamu | historická doplnková vlna 5d |

## Cieľ

Dokončiť plugin runtime tak, aby Core explicitne emitovalo kanonické hooky a extensions sa k nim prihlasovali deklaratívne cez validovaný manifest.

## Dodaný rozsah

| Trieda / prvok | Úloha |
|---|---|
| `HookCatalog` | kanonické názvy a metadata hookov |
| `HookEmitter` | typovaný wrapper nad `HookManager` |
| `ExtensionManifestValidator` | `id`, hooky, `minCmsVersion` |
| `AppVersion` | semver CMS |
| Core emitters | save/delete/status/scheduled publish + extension lifecycle |
| `hello-widget` | referenčný PHP route a frontend extension |

## Hooky

Pôvodná sada zahŕňala `content.before_save`, `content.after_save`, `content.after_delete`, `content.after_status_change`, `content.after_scheduled_publish`, `extension.boot`, `extension.enabled` a `extension.disabled`. Hook callback nesmie obísť autorizáciu, SSOT transakčný model ani audit.

## Testy a incident

Pribudli testy emitera, manifestu a referenčného pluginu. [ISS-075](ISSUES.md#iss-075) odstránil duplicate-class fatal tým, že test fixture prestal kolidovať s referenčným `hello-widget`.

## Smoke

Enable `hello-widget`, zavolať `/api/extensions/hello-widget/ping` a uložiť obsah s overením `content.after_save` contextu.

