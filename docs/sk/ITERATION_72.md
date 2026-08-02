# Iterácia 72 — ovládače médiového úložiska

> **Stav:** ⏳ plánované  
> **Priorita:** 🟡  
> **Vlna:** [Hybrid Engine HE-5](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Závisí od:** [It.68](ITERATION_68.md)  
> **Nadväzuje na:** It.24 DAM

## Cieľ

Oddeliť binárne médiá od konkrétneho filesystemu cez **Flysystem alebo ekvivalentný kontrakt**. Default zostáva lokálny disk; voliteľný S3-compatible driver umožní object storage a CDN bez zmeny Media API.

Dôležité rozlíšenie:

- `registry.json`, alt texty, priečinky, väzby a workflow metadata zostávajú flat-file SSOT,
- pri `local` je autoritatívny binárny objekt lokálny súbor,
- pri `s3` je autoritatívny binárny objekt v nakonfigurovanom object storage; nejde však o SQL databázu ani o náhradu content dokumentov.

---

## Kontrakt

| Komponent | Zodpovednosť |
|-----------|--------------|
| `MediaStorageDriverInterface` | `put`, `readStream`, `delete`, `exists`, `checksum`, `publicUrl` |
| `LocalMediaStorageDriver` | parity s existujúcim `MediaRepository` |
| `S3MediaStorageDriver` | S3-compatible adapter s podporovaným SDK/Flysystem driverom |
| `MediaStorageFactory` | allow-list `local | s3`; bezpečný default local |
| `MediaUrlResolver` | stabilný API/public URL kontrakt bez úniku interného bucket key |
| `MediaMigrationService` | copy → checksum verify → registry update → optional source cleanup |
| CLI | `media:storage:probe`, `media:migrate`, `media:migrate:verify`, `media:migrate:rollback` |

Logical object key vzniká server-side z media identity. Klient neposiela raw filesystem cestu ani ľubovoľný S3 key.

---

## Nastavenia

```yaml
media:
  storageDriver: local
  s3:
    endpoint: null
    region: null
    bucket: null
    keyId: null
    secret: null
    pathStyle: false
    publicBaseUrl: null
    visibility: private
```

- `secret` a credentialy používa `EncryptionService` a password field.
- Custom endpoint prechádza `OutboundUrlGuard`; private/LAN endpoint je povolený iba explicitnou admin allow-list politikou.
- Bucket, region a endpoint sa validujú server-side.
- `publicBaseUrl` nesmie umožniť `javascript:` alebo neplatný scheme.
- Capability probe nevypisuje secret ani podpísanú URL do logu.

---

## URL a prístupový model

| Režim | Odporúčanie |
|-------|-------------|
| Verejné médium | stabilná CDN/public URL alebo API redirect podľa policy |
| Súkromné médium | krátko žijúca signed URL generovaná po ACL kontrole |
| Admin náhľad | session autorizácia; signed URL nesmie byť dlhodobo cacheovaná |
| Presun drivera | content dokumenty používajú media ID, nie hardcoded bucket URL |

Tým sa migrácia medzi local a S3 nezačne hromadným prepisom všetkých článkov. Resolver mapuje stabilné media ID na aktuálnu URL.

---

## Upload a bezpečnosť

Existujúci `UploadSecurityValidator` zostáva pred driverom. Driver nesmie oslabiť:

- MIME/content sniffing a extension policy,
- limit veľkosti a kvóty,
- image decode/re-encode pravidlá, ak sú zapnuté,
- zákaz path traversal a executable upload,
- malware scanning hook,
- audit a permission `media:write`.

S3 metadata a user-defined headers sa allow-listujú. Server nepreberá ľubovoľné `Content-Disposition` alebo cache headers od klienta.

---

## Migrácia

Bezpečný migračný postup:

1. probe cieľa a write/read/delete test s dočasným objektom,
2. read-only inventár a odhad objemu,
3. copy po dávkach bez zmeny aktívneho drivera,
4. checksum/size verifikácia,
5. uloženie migračného journalu,
6. preklopenie drivera po potvrdení,
7. smoke test URL a práv,
8. lokálny zdroj sa maže až po samostatnom retention okne.

Rollback používa journal a zachované lokálne súbory. Partial migrácia nesmie vytvoriť nečitateľné media ID; resolver môže počas prechodu používať explicitný dual-read režim iba v migračnom nástroji, nie ako trvalú nejasnú konfiguráciu.

---

## Frontend

Settings → Media → Storage:

- driver a capability stav,
- connection test,
- vysvetlenie private/public URL policy,
- migračný dry-run a progress,
- povinné potvrdenie pred cutover,
- zrozumiteľný rollback stav.

Media picker a content editor používajú rovnaké media ID bez ohľadu na driver.

---

## Mimo rozsahu

- presun content JSON/Markdown do S3,
- SQL asset registry,
- multi-region replikácia,
- video transcoding pipeline,
- automatické zmazanie lokálnych originálov bez retention,
- prijímanie arbitrary bucket key z API klienta.

---

## Testy

- shared driver contract nad local a memory/mock S3 adapterom,
- local parity s aktuálnym media suite,
- traversal a malicious key sú odmietnuté,
- private media vyžaduje ACL a signed URL,
- public URL resolver pre local/S3,
- migration copy + checksum + resume po prerušení,
- rollback z journalu,
- secret redaction v API/logoch,
- S3 outage nepoškodí registry ani existujúce lokálne dáta,
- Classic/local bez S3 dependency.

---

## Definition of Done

- [ ] `local` je default a správa sa ako pred It.72.
- [ ] S3-compatible staging upload/read/delete prejde contract testami.
- [ ] Metadata registry zostáva flat-file SSOT.
- [ ] Media ID je nezávislé od fyzickej URL.
- [ ] Migrácia má dry-run, journal, checksum, resume a rollback.
- [ ] Private/public policy, SSRF a secret handling sú otestované.
- [ ] SK/EN user, architecture a deploy dokumentácia je aktualizovaná.

## Súvisiace

[It.24 DAM](ITERATION_24.md) · [deployment modes](architecture/DEPLOYMENT_MODES.md)
