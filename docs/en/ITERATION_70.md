# Iteration 70 — Git publish modes

> **Status:** ✅ Shipped (foundation)  
> **Priority:** 🟡  
> **Wave:** [Hybrid Engine HE-3](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Release:** `v2.1.0-beta.27`  
> **Depends on:** [It.68](ITERATION_68.md)  
> **Extends:** the existing `GitHubService`; coordinates [It.48](ITERATION_48.md)

## Goal

Add Git as a **distribution layer** for headless/Jamstack workflows without changing the source of truth. The editor always stores a validated document on disk first. A commit and optional push happen only after that step, according to the selected strategy.

Supported modes:

- **`immediate`** — a successful publish creates an individual commit and optional push,
- **`queued`** — changes are recorded locally and an authorized user starts one batch release commit,
- **`disabled`** — the Classic default; content writes do not invoke Git services.

---

## Storage versus distribution boundary

The API and UI must distinguish these states:

| State | Meaning |
|-------|---------|
| `stored` | the document is safely stored in SSOT |
| `pending_publish` | the change is in an idempotent publish queue |
| `committed` | a local commit was created |
| `pushed` | the remote confirmed the push |
| `publish_failed` | SSOT is stored; distribution requires retry |

A Git failure must not imply that the stored document was lost. The user receives an accurate partial result and a retry path.

---

## Backend

| Component | Responsibility |
|-----------|----------------|
| `Core/Git/Contracts/GitPublisherInterface.php` | `status`, `stage`, `commit`, `push`; typed result |
| `Core/Git/LocalGitPublisher.php` | safe wrapper around the `git` binary with allow-listed arguments |
| `Core/Git/GitHubApiPublisher.php` | optional API-only adapter over the existing `GitHubService` |
| `Core/Git/PublishQueueStore.php` | atomic flat-file queue/journal under `data/git/` |
| `Core/Git/PublishPlanner.php` | change deduplication, diff summary, and commit plan |
| `git.publish` job | It.29 scheduler/worker; idempotent retry |
| Admin API | status, preview, publish, retry; `git:publish` permission |

### Draft endpoints

```http
GET  /api/admin/git/status
GET  /api/admin/git/publish/preview
POST /api/admin/git/publish
POST /api/admin/git/publish/{jobId}/retry
```

Mutations require session + CSRF or an explicitly allowed scoped Bearer client after It.74. The first release is admin-session only.

---

## Settings

```yaml
engine:
  git:
    enabled: false
    publishStrategy: disabled   # disabled | immediate | queued
    publisher: local           # local | github_api
    repositoryPath: null       # server-side validated path/ref
    remote: origin
    branch: main
    pushEnabled: false
    commitMessageTemplate: "content: publish {count} change(s)"
```

Credentials are encrypted and never returned in a frontend payload. `repositoryPath`, remote, and branch are allow-listed/validated; users cannot provide a free-form shell fragment.

---

## Security

- the process uses an argument array rather than a constructed shell string,
- the binary, working tree, and allowed remote/branch are explicitly configured,
- `OutboundUrlGuard` and egress policy apply to an API publisher or webhook,
- commits exclude secrets, internal cache, backups, and runtime logs,
- the queue cannot stage paths outside the content/export allow-list,
- audit records actor, strategy, commit hash, change count, and result—not credentials,
- force push, arbitrary tag deletion, and custom Git commands are out of scope.

---

## Queue and idempotency

Each item has a stable ID, resource identity, revision/fingerprint, and requested action. A repeated job:

- does not commit the same fingerprint twice,
- can collapse several changes to one document into the latest version in queued mode,
- preserves audit history for superseded queue items,
- uses a lock so two workers cannot create parallel release commits.

Remote push and static build are separate steps. It.48 may trigger a build only after successful push confirmation or an explicit local export.

---

## Frontend

- Settings → Engine → Git capability, strategy, and configuration test.
- The content list displays `pending_publish` without changing the content's own publish state.
- The **Publish release** modal shows a path/resource summary and commit message, not raw sensitive diffs.
- A failure offers retry and a diagnostics link.
- The UI clearly distinguishes “stored in CMS” from “sent to Git.”

---

## Migration and rollback

- the feature defaults to `disabled`, preserving existing installations,
- the capability probe verifies the binary, repository, supported state, and write permissions,
- the first test uses a temporary local repository without a remote,
- disabling Git publish does not change content or require conversion,
- the queue can be exported/repaired and is not silently discarded during rollback.

---

## Tests

- temporary Git repository: stage/commit and stable commit metadata contract,
- 3 queued changes → 1 release commit,
- repeated job → no duplicate commit,
- immediate draft behavior follows an explicit rule,
- command injection payloads in branch/remote/path are rejected,
- Classic/disabled → publisher is never invoked,
- remote failure → document remains `stored`, queue remains retryable,
- parallel workers → one commit,
- secrets and excluded directories never enter staging.

---

## Definition of Done

- [x] The local publisher and queue work without a remote service.
- [x] Immediate and queued strategies have PHPUnit coverage (`GitPublishServiceTest`).
- [x] Admin API distinguishes stored / pending_publish / committed / publish_failed states.
- [ ] Full content-list UI and **Publish release** modal (API client shipped; admin UI deferred).
- [x] Retry endpoint reuses idempotent queued release publish.
- [x] Command/path/remote security validation (`GitPathValidator` + regression tests).
- [ ] It.48 uses the same publish contract rather than a parallel pipeline (deferred).
- [x] The Classic default does not invoke Git (`gitEnabled=false`).
- [x] EN backlog, CHANGELOG, and engine probe documentation updated; SK detail catch-up deferred.

## Shipped scope (foundation)

| Area | Delivered |
|------|-----------|
| Core | `GitPublishService`, `LocalGitPublisher`, `PublishQueueStore`, `PublishPlanner`, `GitPublishDispatcher` |
| Scheduler | `git.publish` handler via `GitPublishHandler` |
| Admin API | `GET /api/admin/git/status`, preview, publish, retry (`git:publish`) |
| Settings | `engine.git*` keys with encrypted-at-rest credentials pattern for future API publisher |
| FE | `frontend/src/api/git.ts`, engine settings `gitProbe` in `EngineSettingsPanel` |
| Tests | `GitPublishServiceTest`, `GitPublishTestHelper`, wiring in scheduler/content tests |

**Deferred:** `github_api` publisher, remote push e2e in CI, content-list publish badges, It.48 static render hook.

## Related

[It.29 job runner](ITERATION_29.md) · [It.48 static render](ITERATION_48.md) · [deployment modes](architecture/DEPLOYMENT_MODES.md)
