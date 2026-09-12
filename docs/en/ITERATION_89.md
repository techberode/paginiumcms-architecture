# Iteration 89 — Plugin capability model (3-layer extension security)

> **Status:** ⏳ planned  
> **Priority:** 🟡 · closes RCE gap for server-side plugins  
> **Wave:** Extension security (post It.67 / It.78)  
> **Depends on:** [It.67](ITERATION_67.md) untrusted surfaces · [It.78](ITERATION_78.md) upload policy · [EXTENSION_CODE_POLICY.md](developer/EXTENSION_CODE_POLICY.md)  
> **Blocks:** safe third-party plugin ecosystem, external IDE workflow

## Goal

Move plugin security from **import-time denylist only** to a **3-layer model**: scan → capability manifest → runtime guardrails. Themes and shortcodes stay on the existing untrusted-surface track; **plugins (PHP on server)** get a capability broker so code cannot access undeclared platform APIs even if static scan misses an indirect call.

This iteration does **not** promise OS-level sandboxing (no Docker-per-plugin). It implements architectural enforcement aligned with Chrome-extension / mobile-OS permission models.

---

## Trust tiers (already implicit in product)

| Tier | Examples | Runtime | Current state |
|------|----------|---------|---------------|
| Snippets / shortcodes | layout JSON, HTML fragments | DOM allowlist sanitizer | ✅ shipped |
| Themes | HTML/CSS/JS in browser | no server PHP | ✅ Theme Studio + import scan |
| Plugins | PHP in `Http/Extensions/{id}/` | server-side hooks/routes | ⚠️ scan only |

**Rule:** ZIP from external IDE and ZIP from CMS editor use the **same** import + manifest + enable pipeline (already true after It.78 upload policy).

---

## Three layers

### Layer 1 — Import-time scan (extend existing)

**Existing:** `CodePolicyEngine`, `UntrustedPolicyScanner`, `SecurityScanner` (token-based), `PluginImporter`.

**It.89d additions:**

| Check | Why |
|-------|-----|
| Indirect call patterns | `$fn()`, `call_user_func('exec', …)`, `array_map('system', …)` — denylist limit for string-only `T_STRING` scans |
| `$$`, `extract()` | dynamic variable / symbol injection |
| Manifest consistency (heuristic) | declared capabilities vs obvious API usage (static grep/AST pass) |

`call_user_func*` is already in `UNTRUSTED_FORBIDDEN`; extend **detection**, not just listing.

### Layer 2 — Capability manifest + broker (new — core work)

Every plugin `plugin.json` gains:

```json
{
  "id": "seo-analyzer",
  "manifestVersion": 1,
  "capabilities": [
    "content:read",
    "content:write:own",
    "media:read",
    "admin-ui:sidebar-widget",
    "network:outbound:api.example.com"
  ]
}
```

| Component | Path (planned) | Role |
|-----------|----------------|------|
| `PluginCapabilityCatalog` | `Http/Extensions/Capabilities/` | allow-list of capability strings |
| `ExtensionManifestSchema` | same | JSON schema validation at import |
| `ExtensionManifestValidator` | extend existing | require `capabilities[]`, reject unknown |
| `PluginCapabilityBroker` | same | build scoped SDK from manifest |
| `PluginRuntimeContext` | same | only declared facades (content, media, settings, outbound) |

Hook handlers evolve to receive scoped context (backward-compatible deprecation for handlers without context).

**Invariant:** plugin code never receives raw DI container, `FlatFileStorage`, or unrestricted filesystem.

### Layer 3 — Runtime guardrails (new)

| Control | Implementation |
|---------|----------------|
| Error boundary | `SafeHookRunner` wraps `HookManager::run()` — `catch (Throwable)`, isolate failure |
| Per-hook quota | time + memory budget per invocation |
| Auto-disable | fatal/error threshold → `enabled=false` in registry + audit + admin notice |
| Capability audit | extend security audit: `plugin {id} used content:write` via `LogSanitizer` |

---

## Slices (implementation order)

| Slice | Deliverable | DoD |
|------:|-------------|-----|
| **89a** | Capability catalog + manifest schema + import validation | Unknown capability → import 422; `hello-widget` updated |
| **89b** | `PluginCapabilityBroker` + `PluginRuntimeContext` (content:read/write, media:read) | Hook invoke injects context; undeclared API unreachable |
| **89c** | `SafeHookRunner` + auto-disable + capability audit events | One failing plugin does not break request; tests |
| **89d** | Scanner indirection + manifest consistency pass | Regression pack in `security-regression.sh` |
| **89e** | CLI `paginium:plugin:create` + `paginium:plugin:scan` | Same engine as CMS import; scaffold includes manifest |

---

## Security baseline

- Import/enable remain separate (`enabled=false` after ZIP install).
- Mutating plugin admin routes: existing Auth + permission + CSRF.
- Outbound network only via `OutboundUrlGuard` + declared `network:outbound:*` capability.
- No secrets in manifest; settings secrets via `EncryptionService` / schema `password` type.
- PHPUnit + PHPStan L8 on all new services; hostile fixtures for bypass attempts.

---

## Out of scope

- Docker-per-plugin OS sandbox
- Malware SaaS scanning (hook point only)
- Weakening theme/shortcode policies
- Auto-granting capabilities without manifest declaration

---

## Definition of Done (full It.89)

- [ ] `plugin.json` requires validated `capabilities[]` for new imports
- [ ] `PluginCapabilityBroker` is the only runtime API surface for plugins
- [ ] All hook invocations pass through `SafeHookRunner`
- [ ] Layer 1 scanner covers indirection patterns used in regression tests
- [ ] `hello-widget` migrated as reference capability-compliant plugin
- [ ] CLI scan/create documented in `docs/developer/EXTENSION_CODE_POLICY.md`
- [ ] SK/EN i18n for new admin errors (import/enable blockers)

## Related

[EXTENSION_CODE_POLICY.md](developer/EXTENSION_CODE_POLICY.md) · [CODE_POLICY.md](architecture/CODE_POLICY.md) · [It.67](ITERATION_67.md) · [It.78](ITERATION_78.md)
