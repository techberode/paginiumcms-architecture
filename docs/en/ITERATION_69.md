# Iteration 69 — unified cache and HTTP conditional requests

> **Status:** ⏳ planned  
> **Priority:** 🔴  
> **Wave:** [Hybrid Engine HE-2](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Depends on:** [It.68](ITERATION_68.md)  
> **Absorbs:** It.45 Redis infrastructure and It.49 Unified Cache

## Goal

Complete a unified **read-through cache layer** for memory/file/Redis and add standard HTTP validators (`ETag`, `Last-Modified`) to safe public GET responses. Files remain the source of truth; deleting the entire cache must not lose or change content.

---

## Architecture contract

```text
request → cache key/fingerprint → hit
                         ↘ miss → read SSOT → validate → cache → response
write → atomic SSOT write → index update → tag invalidation → event/audit
```

| Area | Decision |
|------|----------|
| Driver | `auto | memory | file | redis`; `file` is the safe Classic default |
| Redis | optional capability; never the source of truth |
| Fallback | `auto` may move to file only after a documented health failure |
| Invalidation | after the primary write succeeds; tags by resource type/slug/locale |
| HTTP cache | public reads; admin mutations and sensitive responses use `no-store` |
| Browser SPA | relies on standard browser/CDN behavior rather than manually parsing a `304` body |

---

## Backend

| Component | Responsibility |
|-----------|----------------|
| `CacheDriverInterface` | `get`, `set`, `delete`, `invalidateTags`, `health` |
| `MemoryDriver` / existing memory cache | request-local or supported APCu layer |
| `FileDriver` | atomic file cache entries and garbage collection |
| `RedisDriver` | namespaced keys, TTL, serialization, timeouts, and health |
| `CacheDriverFactory` | allow-list and safe resolver for `engine.cache.driver` |
| `CacheCapabilityProbe` | latency and read/write/delete test without storing secrets |
| `ResourceFingerprint` | stable fingerprint from a canonical representation, not arbitrary JSON order |
| HTTP middleware/helper | `ETag`, `Last-Modified`, `If-None-Match`, `If-Modified-Since`, `304` |
| invalidation hooks | content/settings/media publish events within the iteration scope |

### First endpoint slice

- `GET /api/settings/public`
- published lists `/api/pages` and `/api/articles`
- published details `/api/pages/{slug}` and `/api/articles/{slug}`

Before adding an endpoint, verify that the response does not contain session-specific or permission-specific data. Set `Vary` for language or another real response variant.

---

## Settings

```yaml
engine:
  cache:
    driver: file          # auto | memory | file | redis
    defaultTtlSeconds: 300
    redis:
      connection: default # reference to encrypted configuration, not a password
    httpValidatorsEnabled: true
```

- Redis secrets are excluded from the public settings slice.
- `auto` uses the capability probe and records its reason in the health report.
- Explicit `redis` mode with an unavailable service returns clear diagnostics; the fallback policy must be explicit.

---

## Frontend and operations

Settings → Engine → Cache provides:

- driver and capability state,
- connection test,
- permission-protected cache purge/rebuild with confirmation,
- hit/miss and fallback state without exposing credentials.

An optional Docker Compose `cache` profile is documented in `LOCAL_SETUP`/deployment work with a pinned image version. The Classic profile does not require a Redis container.

---

## Consistency and failure scenarios

- A cache write failure after a successful SSOT write must not restore old content.
- Failed invalidation marks the namespace/tag stale and records an incident.
- Redis timeouts are short; an application request must not block for tens of seconds.
- A lock/single-flight mechanism limits cache stampedes during expensive rebuilds.
- Keys include schema version, tenant/site identity, resource, and locale.
- Deserialization does not use PHP `unserialize` for untrusted payloads.

---

## Out of scope

- Redis as the primary content store,
- caching admin mutations,
- CDN vendor lock-in,
- automatic deployment-mode switching,
- It.71 APM/self-heal logic.

---

## Tests

- every driver passes a shared contract test suite,
- Redis unavailable in `auto` → file fallback without `500`,
- explicit Redis mode → predictable diagnostic failure according to policy,
- publish invalidates list, detail, and locale variants,
- `ETag` is stable for an unchanged canonical response and changes after a write,
- matching `If-None-Match` → bodyless `304`,
- admin/sensitive responses use `Cache-Control: no-store`,
- deleting all cache data → correct rebuild from files,
- CI does not require external Redis; an optional integration job may use a service container.

---

## Definition of Done

- [ ] It.45 and It.49 are marked as absorbed by It.69.
- [ ] Memory/file/Redis implement one contract and safe serialization.
- [ ] At least three public GET resource types return a valid `ETag`.
- [ ] Invalidation is deterministic for write/publish/delete.
- [ ] Classic runs with file/memory only.
- [ ] Redis outage, stale cache, and rebuild have a runbook.
- [ ] Metrics needed by It.71 are available without storing content payloads.
- [ ] SK/EN documentation and the gate are green.

## Follow-up

[It.71 Performance Guard](ITERATION_71.md) · [deployment modes](architecture/DEPLOYMENT_MODES.md)
