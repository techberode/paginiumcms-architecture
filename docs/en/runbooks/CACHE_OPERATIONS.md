# Cache operations runbook

> **Scope:** Iteration 69 unified cache layer (memory + file + optional Redis).  
> **Source of truth:** flat files under `data/` — cache is always derived and safe to delete.

---

## Architecture summary

| Layer | Role |
|-------|------|
| **Memory** | Per-worker hot layer (zero disk I/O on repeat reads) |
| **File** | Persistent cache under `data/cache/` (Classic fallback) |
| **Redis** | Shared persistent layer when `REDIS_HOST` / `engine.redisHost` is reachable (production Docker stack) |

**Driver modes**

| `engine.cacheDriver` | Stack |
|----------------------|--------|
| `auto` (default) | memory + Redis if connect OK, else memory + file |
| `file` | file only |
| `memory` | memory only |
| `redis` | memory + Redis; falls back to memory + file if Redis is down |

Invalidation uses **generation counters** (lists/feeds) plus **tag registry** (`content:pages:list`, `content:page:{slug}`, …). Writes call `ContentCacheService::invalidatePage()` / `invalidateArticle()` after a successful SSOT save.

HTTP validators (`ETag`, `Last-Modified`, `304`) apply to anonymous public GET routes when `engine.httpValidatorsEnabled` is true:

- `GET /api/settings/public`
- `GET /api/pages`, `GET /api/pages/{slug}`
- `GET /api/articles`, `GET /api/articles/{slug}`

Authenticated admin reads receive `Cache-Control: private, no-store`.

---

## Production Redis (Docker)

Production merge file `docs/deploy/docker-compose.prod.yml` adds:

- **`redis`** service (`redis:7.4-alpine`, AOF, 256 MB LRU cap)
- **`php`** `depends_on` Redis healthcheck
- **`REDIS_HOST=redis`** / **`REDIS_PORT=6379`** on PHP (override password via `REDIS_PASSWORD` in `.env` if you enable `requirepass`)

After upgrading to a release with Redis support:

1. Copy updated `docker-compose.prod.yml` to **`$STACK_DIR`** (not only under `docs/` in git). It must include **`profiles: !reset []`** on the `redis` service.
2. Rebuild PHP (ext-redis): `"$STACK_DIR/stack.sh" build php`.
3. Recreate stack: `"$STACK_DIR/stack.sh" up -d --build` (starts **redis** + **php** + **nginx**).
4. Admin → Settings → Hybrid Engine → confirm cache probe shows **redisCache: available** and active driver **redis** or **auto**.
5. Leave `engine.cacheDriver` at **`auto`** unless you require Redis-only persistence (`redis`).

Local dev with Redis: `docker compose --profile cache up -d` (see root `docker-compose.yml`).

---

## Verify health

1. **Admin:** Settings → Hybrid Engine → Cache probe (driver, latency, HTTP validators state).
2. **API stats:** `GET /api/admin/cache/stats` (requires admin auth) — file entry count, list generations, hit/miss counters.
3. **Manual ETag check:**

```bash
ETAG=$(curl -sI http://localhost:8080/api/settings/public | awk -F': ' '/^ETag:/ {print $2}' | tr -d '\r')
curl -i -H "If-None-Match: $ETAG" http://localhost:8080/api/settings/public
# Expected: HTTP 304, empty body
```

Use the **exact** `ETag` value from the first response — not a placeholder.

---

## Redis unavailable

| Setting | Behaviour |
|---------|-----------|
| `cacheDriver: auto` | memory + file when Redis host unset or connection fails |
| `cacheDriver: redis` | same fallback to memory + file; probe status **fallback** |
| `cacheDriver: file` | file only |

Content is always read from flat files on cache miss. No data loss when Redis is stopped — only colder cache and higher disk I/O.

**Triage**

**Symptom:** *Redis extension loaded but connection to redis:6379 failed* and **`stack.sh ps` shows no redis container**.

- Merged compose still has `profiles: [cache]` on redis → prod override missing `profiles: !reset []`. Fix the stack copy, then `"$STACK_DIR/stack.sh" up -d`.
- Confirm: `"$STACK_DIR/stack.sh" config` → redis service has **no** `profiles`, and `"$STACK_DIR/stack.sh" ps` lists `redis` **running**.
- **`undefined volume redis-data`:** prod Redis uses **`./redis-data:/data`** under `STACK_DIR` (not a named volume). Ensure `STACK_DIR/docker-compose.prod.yml` matches the repo file; `stack.sh` creates `redis-data/` automatically.
- From PHP: `"$STACK_DIR/stack.sh" exec -T php php -r '$r=new Redis(); var_dump($r->connect(getenv("REDIS_HOST")?:\"redis\",(int)(getenv("REDIS_PORT")?:6379),1.5));'`

**Symptom:** Redis container **`Status: created`** / **`address already in use`** on **`0.0.0.0:6379`**.

- Merged compose still publishes **`6379:6379`** from base `docker-compose.yml`. Prod override needs **`ports: !reset []`** on `redis` (internal `redis:6379` only — no host bind).
- Check: `"$STACK_DIR/stack.sh" config" | grep -A6 'redis:'` — must **not** list host port 6379.
- Remove broken container: `docker rm -f "${COMPOSE_PROJECT_NAME}-redis-1"` then `"$STACK_DIR/stack.sh" up -d`.
- `ss -ltnp | grep 6379` — if a **host** Redis owns the port, stop/disable it or keep CMS Redis off the host port (preferred).

**Symptom:** redis running but probe still fails — check `REDIS_PASSWORD` matches `requirepass`, firewall between containers (same compose network), or wrong `COMPOSE_PROJECT_NAME` (stale container from another project). PHP must use hostname **`redis`**, not `127.0.0.1` (that is the PHP container itself).

1. `docker compose ps redis` — container healthy?
2. From PHP container: `php -r 'echo extension_loaded("redis")?"yes":"no";'`
3. Env: `REDIS_HOST` must match Docker service name (`redis`) on the compose network.
4. Optional password: `REDIS_PASSWORD` in `.env` must match Redis `requirepass` if configured.

---

## Stale cache suspected

Symptoms: published content not visible, old list after publish, feeds out of date.

1. Confirm the write succeeded (audit log, file mtime under `data/content/`).
2. **Admin purge:** Settings → Cache (or `POST /api/admin/cache/purge` with scope `content`).
3. **CLI:** `php backend/bin/console cache:purge-content` (if available in deployment).
4. Re-test public GET; ETag must change after content change.

Generation bump on publish is automatic — manual purge is only needed after incidents or cache bugs.

---

## Full cache delete / rebuild

Safe on Classic: deleting `data/cache/*.cache` does **not** lose content. Redis keys use prefix `engine.redisKeyPrefix` (default `paginium:`) — safe to `FLUSHDB` only on a dedicated DB index.

1. Stop traffic or accept brief miss storm (stampede protection: `rememberLocked` + flock).
2. Delete cache files or run admin purge scope `all`; optionally restart Redis after `FLUSHDB` on the CMS database index only.
3. Warm critical routes (`/api/settings/public`, `/api/pages`, `/api/articles`).
4. Monitor hit/miss metrics in admin stats.

---

## Failure scenarios

| Scenario | Expected behaviour |
|----------|-------------------|
| Cache write fails after SSOT write | Old cache may serve until TTL/invalidation; SSOT remains correct |
| Invalidation fails | Generation bump + tag delete are best-effort; purge content scope |
| Redis timeout | ~1.5s connect timeout; `auto`/`redis` fall back to file chain |
| Corrupt cache file | Miss → rebuild from SSOT; delete offending `.cache` file |

---

## Media upload compression (disk, not HTTP cache)

Raster uploads (JPEG/PNG/WebP) can be **re-encoded on upload** via PHP GD when **Settings → Media / DAM → Auto-compress images on upload** is enabled (default on). The server keeps the original file only when GD cannot produce a smaller binary. Manual **Optimize** in Media Library uses the same engine.

Optional **max edge (px)** downscales oversized photos before storage. Tune **JPEG/WebP quality** (60–95) for space vs. visual fidelity.

For **HTTP transfer** compression (JSON/API, static assets), enable **gzip/brotli on the reverse proxy** (nginx/Caddy) — PHP flat-file responses do not replace proxy-level compression.

---

## Related

- [ITERATION_69.md](../ITERATION_69.md) — Definition of Done
- [HYBRID_ENGINE.md](../architecture/HYBRID_ENGINE.md) — HE-2 wave
- [DEPLOY.md](../../deploy/DEPLOY.md) — stack rebuild with Redis
