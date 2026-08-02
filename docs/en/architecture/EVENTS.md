---
title: Events and hooks
description: Internal events, plugin hooks, payloads, and failure policy
icon: material/flash-outline
---

# Events and Hooks in PaginiumCMS

> **Current foundation:** `Core/Event/EventDispatcher`, `Core/Hook/HookManager`, `HookEmitter`, `HookCatalog`  
> **Rule:** an event announces a fact; a hook is a controlled extension point.

This document fills the previously empty architecture contract. It separates internal communication between trusted components from public hooks for extensions. Without that distinction, a plugin API easily turns into undocumented access to Core internals.

---

## 1. Event versus hook

| Property | Internal event | Plugin hook |
|----------|----------------|-------------|
| Audience | Core and internal modules | enabled extensions |
| Stability | internal but typed | public/versioned contract |
| Payload | domain event object/DTO | serializable allow-listed context |
| Handler trust | trusted project code | optional controlled code |
| Mutation | generally immutable | only explicit filter/result contract |
| Failure | by transaction phase | isolated and audited; cannot damage Core |

HookManager is not a general service locator. An extension does not receive the DI container or filesystem root simply because it subscribes to a hook.

---

## 2. Naming

Canonical public names use lowercase domain and action:

```text
content.before_save
content.after_save
content.after_delete
content.after_status_change
content.after_scheduled_publish
extension.boot
extension.enabled
extension.disabled
```

Internal event class names may be `ContentSaved`, `BackupCompleted`, or `UserRegistered`, while public hook names are registered in `HookCatalog` with a version and payload schema.

A new hook requires:

- owner,
- phase (`before`, `after`, async),
- payload schema,
- failure policy,
- security classification,
- version/deprecation rule,
- emitter test and at least one listener scenario.

---

## 3. Transaction phases

### Before event/hook

Runs after authentication, authorization, and base validation but before the SSOT write. It may:

- enrich a proposal through an explicit typed result,
- perform additional validation,
- reject an operation with a documented exception.

It may not:

- skip permission or revision checks,
- write directly to the target content file,
- perform an irreversible external side effect without idempotency/compensation,
- mutate arbitrary request context.

### After event/hook

Runs after a successful SSOT write. It suits audit enrichment, cache/index follow-up, notification, or queue enqueue. If it fails, the primary document remains stored; the system records handler failure and retries according to policy.

### Async event/job

Used for slow or external operations such as Git push, build hook, report, translation, or AI provider. Payload contains IDs/revisions, not a full sensitive session or credentials.

---

## 4. Payload contract

Example of a safe content context:

```json
{
  "eventId": "evt_...",
  "name": "content.after_save",
  "occurredAt": "2026-08-02T10:00:00Z",
  "actor": {
    "id": "user_...",
    "type": "user"
  },
  "resource": {
    "type": "page",
    "id": "about-us",
    "revision": "..."
  },
  "change": {
    "action": "update",
    "status": "draft"
  },
  "schemaVersion": 1
}
```

The default payload excludes:

- session cookie, CSRF, Bearer/API key,
- TOTP/reset token,
- SMTP/provider credentials,
- internal absolute path,
- full content body when only an ID is required,
- an unrestricted mutable service object.

A handler may load an authorized document through a narrow read service according to its capability.

---

## 5. Dispatch semantics

A basic in-process dispatcher may call listeners synchronously. It must still define:

- deterministic order only when contractual; otherwise handlers cannot rely on order,
- duplicate registration handling,
- listener removal when an extension is disabled,
- maximum recursion/depth,
- exception handling by phase,
- correlation/request/event ID,
- duration and outcome metrics.

“Exactly once” delivery cannot be promised without a transactional broker. Queue consumers/listeners must therefore be **at-least-once safe** and idempotent.

---

## 6. Current hook emitters

The documented shipped foundation includes:

- `content.before_save`
- `content.after_save`
- `content.after_delete`
- `content.after_status_change`
- `content.after_scheduled_publish`
- `extension.boot`
- `extension.enabled`
- `extension.disabled`

This is a catalog snapshot, not permission to emit arbitrary strings. Implementation and `HookCatalog` remain the source of truth, and hook changes update both SK and EN documentation.

---

## 7. Extension listener registration

An extension declares hooks in `plugin.json`:

```json
{
  "id": "my-widget",
  "version": "1.0.0",
  "hooks": {
    "content.after_save": "MyWidget\\Hooks::afterSave"
  },
  "minCmsVersion": "2.1.0"
}
```

The manifest validator checks ID, supported hook, handler form, minimum CMS version, and code policy. Enabled state lives in the flat-file registry. Disable removes registration for the next request/bootstrap; a handler must not survive as a ghost listener in a persistent worker.

---

## 8. Failure policy

| Phase | Failure | Result |
|-------|---------|--------|
| `before_*` Core validation | validated error | no write; 4xx by type |
| `before_*` extension exception | deny or fail-open by explicit hook contract; mutation default is safely fail-closed | incident + no partial write |
| `after_*` listener | primary write already completed | log/incident, retry if idempotent, response distinguishes follow-up state |
| async job | external failure | bounded retry, backoff, dead-letter/failed state |
| notification-only handler | failure | content remains stored; admin diagnostics |

Failure policy must not be left to an invisible “catch Throwable and continue.”

---

## 9. Security

- listeners run with minimum capability,
- permission is not inferred from the hook name,
- payload and handler output are schema-validated,
- outbound handlers pass SSRF policy,
- log payload is redacted and sanitized,
- extension code passes CodePolicyEngine,
- recursion and event storms are bounded,
- user-generated text is never used as a class/method name,
- an AI tool call is not an event listener; it uses a separate allow-listed tool registry.

---

## 10. Events and cache/index

Index/cache maintenance has explicit ownership. Critical consistency must not depend only on a third-party hook. Recommended model:

- after SSOT write, the application service performs mandatory index/cache work or records a recovery state,
- it then emits an event for optional side effects,
- rebuild remains available even if an event is lost.

---

## 11. Versioning and compatibility

A public hook contract uses a schema version. A compatible change adds an optional field. A breaking change requires a new name or major schema version, a deprecation period, and a migration guide.

An extension manifest with an unsupported hook or incompatible CMS version is not activated silently. The admin UI shows the reason and a safe disabled state.

---

## 12. Tests

- listener registration and disable,
- manifest rejects unknown hook/handler,
- payload schema and secret exclusion,
- before veto without partial write,
- after failure preserves SSOT and creates an incident,
- duplicate delivery/idempotency,
- recursion limit,
- handler timeout for async/outbound flow,
- persistent-worker re-bootstrap without ghost listeners,
- SK/EN hook-catalog parity.

---

## Related documents

- [CORE.md](./CORE.md)
- [MODULES.md](./MODULES.md)
- [PLUGINS.md](./PLUGINS.md)
- [CORE_HARDENING.md](./CORE_HARDENING.md)
- [../ITERATION_15D.md](../ITERATION_15D.md)
