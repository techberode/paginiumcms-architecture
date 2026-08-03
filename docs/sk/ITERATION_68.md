# Iterácia 68 — základ Hybrid Engineu

> **Stav:** ✅ hotové (Hybrid Engine foundation — It.68)  
> **Priorita:** 🔴 kritická cesta  
> **Vlna:** [Hybrid Engine HE-1](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Pravidlá:** [No-SQL mandát](architecture/NOSQL_MANDATE.md) · [Hybrid Engine](architecture/HYBRID_ENGINE.md)

## Cieľ

Zaviesť **abstrakciu úložiska, registry dokumentových schém a nastavenia enginu** tak, aby ďalšie režimy a ovládače nevynucovali prepis controllerov. Existujúce flat-file správanie zostáva defaultom a referenčným výsledkom.

It.68 nie je migrácia na nový storage produkt. Je to kontrolovaný architektonický šev nad existujúcimi repozitármi.

---

## Rozhodnutia

| Téma | Rozhodnutie |
|------|-------------|
| Zdroj pravdy | lokálne JSON/Markdown/YAML dokumenty zostávajú autoritatívne |
| Default | `deploymentMode=classic`, `storageDriver=local` |
| Migrácia | inkrementálna po vertikálnych rezoch, bez „big bang“ prepisu |
| Schémy | JSON Schema pre adminom zapisované dokumenty; fail closed pri neplatnom zápise |
| Kompatibilita | chýbajúca skupina `engine` sa interpretuje ako Classic/local |
| Secrets | `engine` verejný settings slice neobsahuje tajomstvá ani interné cesty |

---

## Rozsah backendu

| Komponent | Kontrakt |
|-----------|----------|
| `Core/Storage/Contracts/StorageInterface.php` | `read`, `write`, `exists`, `delete`, `list`; logické cesty, nie voľné systémové cesty |
| `Core/Storage/Drivers/LocalFlatFileStorage.php` | deleguje na existujúce bezpečné reader/writer/repository služby |
| `Core/Storage/StorageFactory.php` | resolver allow-listovaných driverov; neznámy driver → bezpečná chyba, nie dynamická class name |
| `Core/Validation/DocumentSchemaRegistry.php` | registrácia schémy podľa typu dokumentu a verzie |
| `Core/Validation/DocumentValidator.php` | jednotný preklad validačných chýb na stabilný API kontrakt |
| DI wiring | najprv settings a jeden content write slice; rozširovanie až po parity testoch |
| diagnostika | capability report, driver status a rebuild odporúčania bez úniku citlivých ciest |

### Minimálny storage kontrakt

Implementácia musí zachovať:

- normalizáciu a allow-list koreňov,
- zákaz `..`, symlink escape a null byte ciest,
- atomický zápis temp súbor → `fsync` podľa možností → rename,
- `flock`/existujúci lock model,
- stabilné doménové výnimky namiesto raw filesystem chýb,
- rovnaký výsledný JSON a metadata ako pred abstrakciou.

Rozhranie nesmie predstierať distribuovanú transakciu. Operácie indexu, cache a publish zostávajú samostatné kroky doménovej služby.

---

## Nastavenia

Návrh schémy:

```yaml
engine:
  deploymentMode: classic        # classic | hybrid | git_headless
  storageDriver: local           # v It.68 iba local
  schemaValidationEnabled: true
  capabilityProbeEnabled: true
```

Pravidlá:

- UI v It.68 umožní aktívne iba `classic`/`local`.
- Ostatné hodnoty môžu byť zobrazené ako „nie je nainštalované“, nie ako funkčné prepínače.
- Neplatná hodnota nezapne experimentálny driver; aplikácia použije explicitnú diagnostickú chybu alebo zdokumentovaný Classic fallback podľa fázy bootstrapu.
- Zmena nastavenia sa audituje.

---

## Frontend

Settings → **Engine** obsahuje:

1. aktuálny deployment mode a storage driver,
2. capability probe s vysvetlením dostupnosti,
3. uzamknuté budúce profily bez falošného prísľubu,
4. odkaz na diagnostiku a dokumentáciu,
5. SK/EN i18n modul `engine` podľa existujúceho vzoru.

Frontend nesmie posielať internú class name ani cestu drivera.

---

## Schéma a migrácia

Prvý podporovaný typ schémy má byť dokument, ktorý už admin upravuje ako JSON, napríklad settings alebo plugin manifest. Rollout:

1. zaregistrovať schému a jej `schemaVersion`,
2. validovať read-only v report režime nad existujúcimi dátami,
3. opraviť alebo explicitne grandfatherovať legacy odchýlky,
4. zapnúť fail-closed validáciu pre nové zápisy,
5. až potom migrovať ďalší typ dokumentu.

Migrácia storage vrstvy:

1. `LocalFlatFileStorage` deleguje na existujúci kód,
2. settings read/write prejde cez rozhranie,
3. parity test porovná starú a novú cestu,
4. content write sa presunie až po potvrdení parity,
5. rollback je návrat DI bindingu na pôvodný repository flow bez konverzie dát.

---

## Mimo rozsahu

- Redis ako storage SSOT,
- Git ako primárne úložisko,
- S3 pre metadata obsahu,
- API keys alebo JWT,
- hromadný prepis všetkých repozitárov,
- runtime načítanie ľubovoľného drivera z používateľského vstupu.

---

## Testy

- `StorageFactoryTest`: default local, neznámy driver, bezpečný bootstrap.
- `LocalFlatFileStorageTest`: parity, atomický zápis, flock, traversal, symlink escape.
- `DocumentSchemaRegistryTest`: známa/neznáma schéma, version mismatch.
- API test: neplatný admin JSON → `422` so stabilnými field errors.
- Regression: settings a content output pred/po abstrakcii sú ekvivalentné.
- Recovery: prerušený temp zápis nepoškodí poslednú platnú verziu.
- Full gate bez zmeny správania Classic inštalácie.

---

## Definition of Done

- [x] `StorageInterface` a local driver sú v produkčnej ceste pre settings a jeden content write slice.
- [x] Driver factory používa allow-list a bezpečné defaulty.
- [x] Aspoň jeden admin dokument má verziovanú JSON Schema validáciu (`settings.overrides@1`).
- [x] Capability probe odlišuje dostupné, nedostupné a chybné capability.
- [x] Chýbajúce `engine.*` zachová správanie `beta.23`.
- [x] Migration dry-run, rollback a incident scenár sú zdokumentované.
- [x] SK/EN architektúra, API/settings dokumentácia a changelog sú aktualizované.
- [x] `iteration-gate.sh` a Classic smoke test sú zelené.

## Nadväzuje

[It.69 cache](ITERATION_69.md) · [It.70 Git publish](ITERATION_70.md) · [It.72 media drivers](ITERATION_72.md) · [It.73 locale model](ITERATION_73.md) · [It.74 auth](ITERATION_74.md)
