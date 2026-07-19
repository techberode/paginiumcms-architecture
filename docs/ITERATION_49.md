# Iteration 49 – Unified cache layer (file + Redis, hosting-aware)

**Status:** ⏳ Planned  
**Version target:** TBD  
**Priority:** 🟡 — pred horizontálnym škálovaním; voliteľné pre single-node

> Rozširuje návrh z [ITERATION_45.md](ITERATION_45.md). It.45 = Redis driver detail; **It.49 = produktová vrstva** (prepínač, auto-detekcia, admin UX).

## Ciele (tri piliere PaginiumCMS)

| Pilier | Ako cache pomáha |
|--------|------------------|
| **Rýchlosť** | Memory L1 → Redis L2 → File L3; content cache invalidácia pri zápise |
| **Bezpečnosť** | Redis TLS/password; žiadne secrets v cache keys; fallback bez výpadku |
| **Spoľahlivosť** | Ak Redis padne → automatic downgrade na file; health check v reporte |

Flat-file **ostáva source of truth** — cache nikdy nenahrádza `.json`/`.md`.

## Režimy (`settings.cache.driver`)

| Hodnota | Popis | Kedy |
|---------|-------|------|
| `auto` | Detekcia: Redis ak `ext-redis` + ping OK + `redis.enabled`, inak `file` | **Default odporúčaný** |
| `file` | `MemoryDriver` → `FileDriver` (súčasné správanie) | single VPS, Docker 1 worker |
| `redis` | `MemoryDriver` → `RedisDriver` → `FileDriver` fallback | multi-worker, LB |
| `memory` | Len request-scope (dev/test) | CI, lokál |

### System parameters (auto mode)

`CacheCapabilityProbe` pri štarte / health check:

- `PHP_SAPI`, počet FPM workerov (env `PAGINIUM_FPM_WORKERS` alebo heuristic)
- `extension_loaded('redis')` alebo Predis available
- `redis.host` reachable (timeout 500ms)
- Available RAM / disk (voliteľné varovanie v admin)

Výsledok → `data/cache-capability.json` + Settings hint „Odporúčame Redis“.

## Backend

### Existujúce (rozšíriť)

| Komponent | Úloha |
|-----------|--------|
| `Core/Cache/Drivers/ChainedDriver.php` | ✅ — doplniť Redis slot |
| `Core/Cache/Drivers/RedisDriver.php` | **Nový** — prefix, TTL, serialize |
| `Core/Cache/ContentCacheService.php` | ✅ — tag invalidácia pri content write |
| `Core/Redis/RedisConnectionFactory.php` | **Nový** — settings + env `REDIS_URL` |

### Nové

| Komponent | Úloha |
|-----------|--------|
| `Core/Cache/Services/CacheDriverFactory.php` | Skladá reťazec podľa `settings.cache.driver` |
| `Core/Cache/Services/CacheCapabilityProbe.php` | Auto mode rozhodnutie |
| `Core/Health/Services/Checkers/CacheChecker.php` | Rozšírenie: driver name, hit rate, Redis ping |
| Settings skupina `cache` | `driver`, `defaultTtl`, `prefix`, `redis.*` |

### Rozšírenia mimo content cache

| Modul | Redis benefit |
|-------|---------------|
| Job queue (It.29) | `scheduler.queueDriver` = `flatfile` \| `redis` |
| Rate limit | shared counter multi-worker |
| Session (voliteľné) | až pri load balanceri |
| Lock manager | Redis TTL lock pre multi-worker edit |

## Frontend

- **Nastavenia → Cache**
  - dropdown driver (`auto` / `file` / `redis` / `memory`)
  - read-only panel: „Detected: file“, „Redis: unavailable (ext-redis missing)“
  - tlačidlo **Test cache** + **Clear cache** (existujúce admin API rozšíriť)
- **System overview (It.34)** — widget: driver, hit/miss, Redis latency
- **Health page** — CacheChecker detail

## Docker / hosting

```yaml
# voliteľný service — single-node môže ignorovať
redis:
  image: redis:7-alpine
```

| Profil hostiteľa | Odporúčaný driver |
|------------------|-------------------|
| 1× VPS, 1 FPM worker | `file` alebo `auto` → file |
| Docker Compose, 2+ API repliky | `auto` → redis |
| Shared hosting bez Redis | `file` (explicit) |

## Testy

- PHPUnit: `CacheDriverFactoryTest` — auto/file/redis reťazce
- PHPUnit: Redis down → fallback na FileDriver bez exception
- PHPUnit: content write → tag flush
- Integrácia (voliteľné CI): Redis testcontainer

## Migrácia z It.45

Implementácia **It.49 obsahuje celý rozsah It.45**. Po dokončení It.49 označiť It.45 ako „absorbed“ v backlogu.

## Súvisiace

- [ITERATION_45.md](ITERATION_45.md) — pôvodný Redis návrh
- [ITERATION_29.md](ITERATION_29.md) — queue backend
- [ITERATION_46.md](ITERATION_46.md) — host metrics (nezávislé)
- [architecture/STORAGE.md](architecture/STORAGE.md) — flat-file princípy

## Out of scope

- Redis ako primary content store
- Memcached driver (môže byť fáza 2 ak dopyt)
- CDN edge cache (Cloudflare) — deploy docs only
