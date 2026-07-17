# Iteration 1 – Content Locking System

**Status:** Complete  
**Version:** 2.0.6 (core foundation)

## Summary

Prevents two users from editing the same document simultaneously. Flat-file lock registry with heartbeat on the client and TTL auto-release on the server.

## Backend

| Path | Role |
|------|------|
| `Core/Locking/Models/ContentLock.php` | Lock model (owner, token, heartbeat, expiry) |
| `Core/Locking/Contracts/LockManagerInterface.php` | Lock manager contract |
| `Core/Locking/Services/LockManager.php` | Flat-file manager over `data/locks.json`, `flock(LOCK_EX)` |
| `Core/Locking/Exception/LockConflictException.php` | HTTP 409 with `lock` context |
| `Http/Controllers/Locking/LockController.php` | `/api/locks/*` HTTP layer |
| `Http/Routes/locking.php` | Auto-discovered routes |

### Key parameters

| Parameter | Value |
|-----------|-------|
| Heartbeat interval (frontend) | 30 s |
| Auto-release TTL (backend) | 300 s (5 min) |
| Lock registry | `backend/storage/app/content/data/locks.json` |

## Frontend

| File | Role |
|------|------|
| `src/api/locks.ts` | Typed lock API |
| `src/hooks/useContentLock.ts` | Acquire on open, heartbeat every 30 s, release on leave |
| `src/components/locking/LockIndicator.tsx` | Visual badge showing who is editing |

## API

| Method | Route | Description |
|--------|-------|-------------|
| POST | `/api/locks/acquire` | Acquire lock for `{ type, slug }` |
| POST | `/api/locks/heartbeat` | Extend lock TTL |
| POST | `/api/locks/release` | Release lock |
| GET | `/api/locks` | List active locks (admin) |

Conflict response: HTTP **409** + `{ success: false, error, lock }` — see [API_CONTRACT.md](architecture/API_CONTRACT.md).

## Tests

- `LockManager` unit tests (if present in `Core/Locking`)
- `LockControllerTest` / dashboard `LocksPanel` integration

## Related docs

- [ROADMAP.md](ROADMAP.md) – Iteration 1
- [VERSIONING.md](architecture/VERSIONING.md) – works alongside optimistic locking (It. 2)

## Next

→ [Iteration 2](ITERATION_2.md) – auto-save drafts, revisions, versioning in editor
