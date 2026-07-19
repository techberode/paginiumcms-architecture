# Iteration 45 – Redis (voliteľná infra vrstva)

**Status:** ⏳ Planned — **implementácia v [Iteration 49](ITERATION_49.md)**  
**Version target:** TBD  
**Priority:** 🔵 — až pri škálovaní alebo viacerých PHP workeroch

> Tento dokument popisuje **Redis driver a integračné body**. Produktová vrstva (admin prepínač, auto-detekcia hostingu, fallback) je v **[ITERATION_49.md](ITERATION_49.md)**.

## Prečo (a kedy) Redis

PaginiumCMS zostáva **flat-file first** — Redis nenahrádza `.json`/`.md` obsah. Slúži ako **rýchla zdieľaná vrstva** medzi procesmi.

| Scenár | Flat-file / memory dnes | Redis pomôže? |
|--------|-------------------------|---------------|
| Jeden server, Docker, 1 PHP-FPM worker | ✅ stačí `MemoryDriver` + `FileDriver` | ❌ zatiaľ nie nutné |
| Viac PHP-FPM workerov / replík API | cache a rate-limit per proces | ✅ áno |
| Vysoká návštevnosť, analytics burst | disk I/O na cache | ✅ áno |
| Job queue (`scheduler:run` + viac workerov) | `data/jobs/queue.json` | ✅ áno — atomické fronty |
| Session sticky na load balanceri | PHP session files | ✅ voliteľne |
| Jeden malý VPS, nízky traffic | — | ⏸️ odložiť |

**Odporúčanie pre mail.webland.fun (32 GB RAM, Docker, typický CMS traffic):**  
Redis **nie je blocker** — flat-file + file cache je v poriadku. Plánuj **It.45 až keď**:
- pôjdeš na **2+ PHP kontajnerov** alebo horizontálne škálovanie,
- uvidíš **contention** na `data/jobs/queue.json` / locks / rate-limit,
- alebo cache miss latency na disku merateľne bolí.

## Ciele It.45

1. **Voliteľný** — `redis.enabled=false` → súčasné správanie bez zmeny
2. **Driver pattern** — rozšírenie existujúceho `ChainedDriver`
3. **Žiadna SQL migrácia** — obsah ostáva na disku

## Backend (návrh)

### Nové komponenty

| Súbor | Úloha |
|-------|--------|
| `Core/Cache/Drivers/RedisDriver.php` | PSR-kompatibilný driver cez `ext-redis` alebo Predis |
| `Core/Redis/RedisConnectionFactory.php` | Host, port, prefix, TLS z Settings |
| `Core/Scheduler/Services/JobQueueStore` | Implementácia `RedisJobQueueStore` (alternatíva k flat-file) |
| `Core/Locking/Services/LockManager` | Voliteľný Redis lock (TTL) pre multi-worker |
| Settings skupina `redis` | `enabled`, `host`, `port`, `password`, `prefix`, `database` |

### Reťazec cache (bootstrap)

```
Request → MemoryDriver → RedisDriver (ak enabled) → FileDriver
```

### Job queue

- Feature flag: `scheduler.queueDriver` = `flatfile` \| `redis`
- CLI `worker:process` funguje rovnako; mení sa len úložisko

### Rate limit / session (fáza 2 v rámci It.45)

- `RateLimitMiddleware` — shared counter v Redis
- Session handler adapter (voliteľné, až pri LB)

## Frontend

- Settings → **Redis** — test pripojenia (`GET /api/admin/health/redis` alebo ping v HealthChecker)
- `/scheduler` — badge „Queue: flat-file / Redis“

## Docker / deploy

```yaml
# docker-compose.yml (voliteľný service)
redis:
  image: redis:7-alpine
  ports: ["6379:6379"]
```

Env: `REDIS_URL=redis://redis:6379/0`

## Testy

- Unit: `RedisDriver` s mock / Redis testcontainer (voliteľné CI)
- Integrácia: fallback keď Redis down → FileDriver

## Súvisiace

- [ITERATION_29.md](ITERATION_29.md) — job queue (dnes flat-file)
- [ITERATION_49.md](ITERATION_49.md) — unified cache layer (admin + auto mode)
- [ITERATION_46.md](ITERATION_46.md) — host metrics (nezávislé od Redis)

## Out of scope

- Nahradenie flat-file content store SQL/Redis
- Redis ako primárny analytics store (It.33 môže zvážiť neskôr)
