# Iteration 77 — assisted translation through cloud providers

> **Status:** ⏳ planned  
> **Priority:** 🔵  
> **Wave:** [Hybrid Engine HE-6](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Depends on:** [It.76](ITERATION_76.md)

## Goal

Extend the shared `TranslationService` with cloud-provider drivers without creating a second service or editor workflow. The administrator selects a provider in settings; the content model, proposals, diff, Apply, audit, and publish rules remain those defined by It.76.

The documentation does not promise a particular free allowance. Pricing, regional availability, and provider terms are external and may change; administrators verify them during configuration.

---

## Provider contract

MVP registry:

| Value | Driver | Note |
|-------|--------|------|
| `none` | no outbound | manual workflow |
| `libretranslate` | It.76 | self-hosted / compatible |
| `deepl` | `DeepLTranslationDriver` | fixed vendor endpoints |
| `google` | `GoogleTranslationDriver` | minimally supported auth variant |

An additional provider is a new driver implementing the same interface and contract tests. Provider-specific failures map to stable domain codes such as `AUTH_FAILED`, `QUOTA_EXCEEDED`, `RATE_LIMITED`, `PROVIDER_UNAVAILABLE`, and `INVALID_RESPONSE`.

---

## Backend

| Component | Responsibility |
|-----------|----------------|
| `DeepLTranslationDriver` | vendor REST adapter, fixed-host allow-list, timeout/retry |
| `GoogleTranslationDriver` | supported REST adapter with minimal credential scope |
| `TranslationProviderRegistry` | resolves `translation.provider`; no dynamic class name |
| `TranslationCredentialResolver` | decrypts a credential only immediately before the request |
| `TranslationUsageMeter` | normalized character/request meter over It.76 quota store |
| `TranslationErrorMapper` | generic UI errors without leaking vendor payloads/secrets |
| failover policy | optional, explicit, and audited; never a silent semantic change |

Provider requests use the same structural protection, proposal store, OCC, and Apply flow as It.76.

---

## Settings

```yaml
translation:
  enabled: false
  provider: none                 # none | libretranslate | deepl | google
  fallbackProvider: none
  deepl:
    apiKey: null
  google:
    apiKey: null
    projectId: null
  dailyCharLimit: 0
```

- Credential fields are encrypted and write-only.
- Switching providers does not delete credentials without confirmation, but never returns them as plaintext.
- A cloud driver's vendor endpoint is fixed/allow-listed; the administrator does not provide an arbitrary URL.
- Service-account JSON, when supported, requires a separate schema, size limit, minimal scopes, and safe parser. The MVP may begin with an API-key variant.
- `fallbackProvider` defaults to `none`.

---

## Failover rules

Failover is safe only when:

1. the administrator explicitly enabled it,
2. the secondary provider passed a capability test,
3. the request contains no content prohibited by the secondary provider privacy policy,
4. quota/billing impact is shown,
5. the proposal identifies which provider translated each locale,
6. audit records the fallback reason.

An authentication failure does not fall through to another provider by default; it is treated as a configuration incident. Silent failover must not bypass an organization's chosen data-residency/privacy policy.

---

## Frontend

Settings → Translation provides:

- provider dropdown,
- dynamic credential fields per driver,
- Test connection / fixed test phrase without real content,
- usage meter based on internal quota state and available vendor metadata,
- clear warning about external content processing,
- failover configuration with privacy/cost confirmation.

The editor uses exactly the same Translate → Diff → Apply interface as It.76. Provider selection is an operational detail, not a second content workflow.

---

## Security and privacy

- secrets use `EncryptionService`; never in logs, exception messages, or frontend hydration,
- fixed HTTPS endpoints and TLS verification,
- request/response bodies are not logged,
- sanitized vendor request IDs may be retained for troubleshooting,
- provider output is untrusted and passes the same schema/content validation,
- admin documentation explains that selected text is sent to a third party,
- credential rotation requires no content migration,
- connection tests use a fixed non-sensitive phrase.

---

## Failure scenarios

- invalid credential → generic auth error; no secret/raw vendor response,
- `429` → quota/rate-limit state and optional explicit failover,
- partial provider result → proposal per locale/field with rejected parts,
- provider switched during a job → the job uses an immutable provider snapshot without credential dump,
- billing/account issue → configuration incident, not content loss,
- disabled translation → zero outbound traffic.

---

## Out of scope

- guaranteeing a free tier or price,
- automatically choosing the cheapest provider,
- sending the entire repository/unbounded batches,
- cloud LLM agent (It.75),
- provider-specific second editor,
- live vendor APIs in CI.

---

## Tests

- contract tests for mocked DeepL/Google adapters,
- provider switch uses the new driver for the next job,
- credential is never returned and is redacted in logs,
- fixed-host policy rejects an arbitrary endpoint,
- `429` and auth failures map to stable codes,
- failover only under explicit policy and with audit,
- a privacy-disabled provider receives no request,
- shared It.76 proposal/OCC/Apply tests run for every driver,
- CI has no live network.

---

## Definition of Done

- [ ] At least two cloud drivers pass the shared mocked contract suite.
- [ ] Provider changes through settings without deployment or a second UI.
- [ ] Credentials are encrypted, write-only, and rotatable.
- [ ] Error/failover/privacy policy is explicit and audited.
- [ ] The editor proposal workflow is identical to It.76.
- [ ] Classic/disabled performs no outbound request.
- [ ] SK/EN admin and security documentation contains no stale price/free-tier promise.

## Follow-up

[It.75 CMS-aware AI agent](ITERATION_75.md)
