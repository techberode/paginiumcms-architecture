# Admin load simulation (single instance, multi-user)

> **Iteration:** [ITERATION_100](../ITERATION_100.md)  
> **Use case:** You cannot easily put ten colleagues on one CMS — this probe approximates **N concurrent admin readers** on **one production or staging instance** without mutating content.

---

## What it does

Script: [`scripts/admin-concurrency-probe.sh`](../../scripts/admin-concurrency-probe.sh)

- Spawns **N workers** (default 5), each with its **own session** (separate login).
- Runs a **read-only** route mix (dashboard overview + P2 endpoints).
- Prints per-request duration and a simple summary (min / max / avg).
- Optional: write one line to stderr for cron + log aggregation.

It uses the same HTTP stack as real admins (cookies + CSRF for login only; GETs need no CSRF).

---

## Safety on production

| Rule | Why |
|------|-----|
| **Read-only profile** (default) | Only GET routes from an allow-list — no POST save/upload |
| Cap **`-n`** (max 12) and **`-r`** rounds | Avoid accidental load test |
| Dedicated user **`loadprobe@…`** with minimal role | Blast radius |
| Run **off-peak** first | Compare PG p95 before/after |
| Stop if **5xx** or widespread **429/503** | Instance already saturated |

Do **not** point the probe at production without `-y` confirm when `BASE_URL` matches your prod host pattern (script warns).

---

## Bootstrap sessions (once)

1. Create an admin user (or role with `metrics:read` + dashboard access only if you split permissions later).
2. Export credentials **only in env**, never commit:

```bash
export PAGINIUM_PROBE_BASE_URL="https://cms.example.sk"
export PAGINIUM_PROBE_USER="loadprobe@example.sk"
export PAGINIUM_PROBE_PASS="…"
```

**On the app server (Docker nginx on loopback):** prefer hitting the container port and set `Host` to the site name (same pattern as manual `curl` diagnostics):

```bash
export PAGINIUM_PROBE_BASE_URL="http://127.0.0.1:8089"
export PAGINIUM_PROBE_HOST="paginiumcms.com"
```

The probe sends a dedicated `User-Agent` (not `curl/…`) so WAF **block scraper tools** does not reject logins. If CSRF still fails, check firewall jail for your IP and that `https://…` is reachable from where you run the script (hairpin TLS/DNS).

3. Run probe (5 virtual users, 2 rounds):

```bash
./scripts/admin-concurrency-probe.sh -n 5 -r 2
```

Each worker logs in → stores cookies in a temp dir → runs the route set in parallel with others.

**Production login cap:** `LoginRateLimitMiddleware` allows **5** `POST /api/auth/login` per **5 minutes** per email + IP. For **8+ parallel readers** without extra accounts, use **shared session** (one login, many GET workers — models one editor opening many dashboard tabs):

```bash
./scripts/admin-concurrency-probe.sh -n 8 -r 2 -s
```

### Shared session (`-s`) and PHP session lock

With **one** `PHPSESSID`, parallel GETs used to **queue on the session file** (wall-clock ≈ sum of handler times). Global `SessionReleaseMiddleware` runs before auth opens the session; **`AuthMiddleware`** now calls `SessionManager::releaseWriteLock()` on authenticated **GET/HEAD/OPTIONS** after validation so dashboard `Promise.all` can run concurrently.

| Signal | Likely cause |
|--------|----------------|
| `-s` max latency **≈ N × single-route** (e.g. 10–14 s) | Session lock or FPM saturation — redeploy session release fix; check `pm.max_children` |
| `-s` max **≈ slowest single route** (hundreds of ms–few s) | Lock released; remaining time is cache miss / flat-file / jobs payload |
| Without `-s`, high max on round 1 only | Cold P2 — cron `cache:warm-admin` + second round |

Mutating methods keep the write lock through the request (POST save/upload unchanged).

---

## Interpreting results

- **First round** often slow (cold cache) — **second round** should drop if `AdminOverviewCacheService` / Redis is active.
- With **cron warm-up**, cold misses should be rare even on round 1 (admin should not pay flat-file aggregation on first dashboard open).
- Compare with **Admin → Performance Guard** and **`http_access`** WARNING rate during the same window.
- If P0 routes (not in default probe) degrade while probe runs, confirm **100b** load hint is `busy` (Performance Guard enabled) — dashboard should defer P2 GETs automatically.

---

## P2 cache warm-up (cron)

Pre-compute derived admin overview caches so HTTP handlers serve Redis/file envelopes immediately (Iteration 100c):

```bash
* * * * * cd /path/to/paginiumcms && php backend/bin/console cache:warm-admin >/dev/null 2>&1
```

Run on the **same instance** that serves admin traffic (shared cache driver / Redis). Safe to run every minute; work is idempotent refresh, not content mutation.

**Bare-metal tuning (optional):** ensure PHP-FPM has enough workers for concurrent admins (`pm.max_children` ~15–20 on a 32 GB host) and nginx **gzip** for `application/json` (large `/api/admin/jobs` payloads). Docker reference: `docker/nginx/default.conf`.

---

## Extending the route set

Edit `ROUTES` in the script. Classify each path in [ITERATION_100](../ITERATION_100.md) §3 (P1/P2). Keep mutating methods out of the default profile.

---

## Limitations

- Default mode simulates **N independent sessions**. Use **`-s`** to model one admin with many parallel dashboard GETs (same lock behaviour as one tab’s burst XHR).
- Does not model **large uploads** or **editor save** contention — add a separate **P0 stress** profile later with explicit `-profile write` and staging-only guard.
- TLS / WAF may rate-limit the probe host; tune `-n` and delay between rounds.
