# Iterácia 69 — jednotná cache a HTTP podmienené požiadavky

> **Stav:** ⏳ plánované  
> **Priorita:** 🔴  
> **Vlna:** [Hybrid Engine HE-2](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Závisí od:** [It.68](ITERATION_68.md)  
> **Absorbuje:** It.45 Redis infrastructure a It.49 Unified Cache

## Cieľ

Dokončiť jednotnú **read-through cache vrstvu** pre memory/file/Redis a pridať štandardné HTTP validators (`ETag`, `Last-Modified`) na bezpečné verejné GET odpovede. Súbory zostávajú zdrojom pravdy; úplné vymazanie cache nesmie stratiť ani zmeniť obsah.

---

## Architektonický kontrakt

```text
request → cache key/fingerprint → hit
                         ↘ miss → read SSOT → validate → cache → response
write → atomic SSOT write → index update → tag invalidation → event/audit
```

| Oblasť | Rozhodnutie |
|--------|-------------|
| Driver | `auto | memory | file | redis`; `file` je bezpečný Classic default |
| Redis | voliteľná capability; nie zdroj pravdy |
| Fallback | `auto` môže prejsť na file iba pri zdokumentovanom health zlyhaní |
| Invalidácia | po úspešnom primárnom zápise; tagy podľa resource typu/slug/locale |
| HTTP cache | verejné čítanie; admin mutácie a citlivé odpovede `no-store` |
| Browser SPA | spolieha sa na štandardnú browser/CDN cache, nie na ručné spracovanie tela `304` |

---

## Backend

| Komponent | Zodpovednosť |
|-----------|--------------|
| `CacheDriverInterface` | `get`, `set`, `delete`, `invalidateTags`, `health` |
| `MemoryDriver` / existujúci memory cache | request-local alebo podporovaná APCu vrstva |
| `FileDriver` | atomické súborové cache položky a garbage collection |
| `RedisDriver` | namespaced keys, TTL, serializácia, timeouts a health |
| `CacheDriverFactory` | allow-list a bezpečný resolver `engine.cache.driver` |
| `CacheCapabilityProbe` | latencia, read/write/delete test bez uloženia tajomstiev |
| `ResourceFingerprint` | stabilný fingerprint z canonical representation, nie z náhodného JSON poradia |
| HTTP middleware/helper | `ETag`, `Last-Modified`, `If-None-Match`, `If-Modified-Since`, `304` |
| invalidation hooks | napojené na content/settings/media publish udalosti podľa rozsahu |

### Prvý endpointový rez

- `GET /api/settings/public`
- published listy `/api/pages` a `/api/articles`
- published detail `/api/pages/{slug}` a `/api/articles/{slug}`

Pred pridaním endpointu sa overí, že odpoveď neobsahuje session-specific alebo permission-specific dáta. `Vary` sa nastaví pre jazyk alebo iný reálny variant odpovede.

---

## Nastavenia

```yaml
engine:
  cache:
    driver: file          # auto | memory | file | redis
    defaultTtlSeconds: 300
    redis:
      connection: default # referencia na šifrovanú konfiguráciu, nie heslo
    httpValidatorsEnabled: true
```

- Tajomstvá Redis nie sú v public settings slice.
- `auto` sa rozhoduje podľa capability probe a uchováva dôvod vo health reporte.
- Ručný `redis` režim pri nedostupnom Redis vráti jasnú diagnostiku; fallback politika musí byť explicitná.

---

## Frontend a prevádzka

Settings → Engine → Cache:

- driver a stav capability,
- test spojenia,
- cache purge/rebuild s oprávnením a potvrdením,
- hit/miss a fallback stav bez zobrazenia credentialov.

Voliteľný Docker Compose profil `cache` sa dokumentuje v `LOCAL_SETUP`/deploy iterácii s pripnutou verziou image. Classic profil nevyžaduje Redis kontajner.

---

## Konzistencia a chybové scenáre

- Cache write failure po úspešnom SSOT write nesmie vrátiť obsah do starej verzie.
- Zlyhanie invalidácie označí cache namespace/tag ako stale a zapíše incident.
- Redis timeouty sú krátke; aplikačný request nesmie čakať desiatky sekúnd.
- Cache stampede sa obmedzí lockom/single-flight pre drahé rebuildy.
- Kľúče obsahujú verziu schémy, tenant/site identitu, resource a locale.
- Deserializácia nepoužíva PHP `unserialize` na nedôveryhodné payloady.

---

## Mimo rozsahu

- Redis ako primárny content store,
- caching admin mutácií,
- CDN vendor lock-in,
- automatické prepínanie deployment mode,
- APM/self-heal logika It.71.

---

## Testy

- každý driver prejde spoločným contract test suite,
- Redis nedostupný v `auto` → file fallback bez `500`,
- manuálny Redis režim → predvídateľná diagnostická chyba podľa politiky,
- publish invaliduje list, detail aj locale variant,
- `ETag` je stabilný pre nezmenenú canonical odpoveď a zmení sa po zápise,
- zhodný `If-None-Match` → `304` bez tela,
- admin/citlivé odpovede majú `Cache-Control: no-store`,
- úplné vymazanie cache → správny rebuild zo súborov,
- CI nemá povinný externý Redis; voliteľný integračný job môže použiť service container.

---

## Definition of Done

- [ ] It.45 a It.49 sú označené ako absorbované It.69.
- [ ] Memory/file/Redis implementujú jeden contract a bezpečnú serializáciu.
- [ ] Aspoň tri verejné GET resource typy vracajú korektný `ETag`.
- [ ] Invalidácia je deterministická pre write/publish/delete.
- [ ] Classic funguje iba s file/memory vrstvou.
- [ ] Redis outage, stale cache a rebuild majú runbook.
- [ ] Metriky pre It.71 sú dostupné bez ukladania content payloadov.
- [ ] SK/EN dokumentácia a gate sú zelené.

## Nadväzuje

[It.71 Performance Guard](ITERATION_71.md) · [deployment modes](architecture/DEPLOYMENT_MODES.md)
