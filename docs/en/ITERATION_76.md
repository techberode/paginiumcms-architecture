# Iteration 76 — assisted translation through a self-hosted provider

> **Status:** ⏳ planned  
> **Priority:** 🔵  
> **Wave:** [Hybrid Engine HE-6](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Depends on:** [It.73](ITERATION_73.md)  
> **Provider path:** LibreTranslate-compatible / self-hosted / OSS

## Goal

Add **assisted translation** of missing locale variants through a self-hosted HTTP provider. The result is a review proposal; the system never publishes it automatically.

It.76 creates the shared `TranslationProviderInterface`, service, quota, and editor workflow that It.77 extends with cloud drivers without creating a second UI.

---

## Workflow

```text
editor selects source locale + target locales + fields
→ server loads the authorized resource
→ job chunks/protects structure
→ provider returns translation
→ server validates proposal
→ editor reviews diff
→ Apply stores a draft after revision check
→ Publish is a separate human action
```

A translation request must not bypass It.73 lock/OCC. If the source changes while a job runs, Apply returns a conflict rather than overwriting a newer version.

---

## Backend

| Component | Responsibility |
|-----------|----------------|
| `TranslationProviderInterface` | typed request/result, provider capabilities, and health |
| `LibreTranslateDriver` | compatible REST client with timeout and retry policy |
| `TranslationService` | field selection, chunking, placeholder protection, result validation |
| `TranslationProposalStore` | short-lived flat-file proposal bound to actor/resource/revision |
| `TranslationQuotaStore` | bounded daily character/use counters; derived operational state |
| `TranslationProviderRegistry` | allow-listed providers; `none | libretranslate` |
| `content.translate` job | It.29 worker for longer requests |
| Admin API | create job, status, result, apply/discard |

### API concept

```http
POST /api/admin/content/{type}/{slug}/translations
GET  /api/admin/translations/{jobId}
POST /api/admin/translations/{jobId}/apply
DELETE /api/admin/translations/{jobId}
```

The create body includes `sourceLocale`, `targetLocales`, `fields`, and `sourceRevision`. Apply requires the same revision or an explicitly resolved conflict.

---

## Structure protection

TranslationService distinguishes plain text, Markdown, HTML, and Tiptap JSON:

- code blocks, URLs, media IDs, shortcode tokens, and placeholders are protected,
- HTML is not sent/reassembled as an unchecked raw string without a parser,
- Tiptap JSON translates only allow-listed text nodes,
- provider output passes schema and content-sanitizer validation,
- invalid structure is rejected rather than “fixed” with a risky regex.

---

## Settings

```yaml
translation:
  enabled: false
  provider: none              # none | libretranslate
  baseUrl: null
  apiKey: null
  dailyCharLimit: 0           # 0 = administrator-defined unlimited policy
  overwriteExisting: false
  timeoutSeconds: 15
```

- The API key is encrypted and write-only in frontend responses.
- `baseUrl` passes `OutboundUrlGuard`.
- A provider on a LAN/private range requires explicit administrator approval in the outbound allow-list; general private-IP access is prohibited.
- Deployment documentation uses a pinned image version/digest rather than an uncontrolled `latest` tag.

---

## Frontend

The multi-locale editor provides:

- **Translate missing** or selection of specific fields/locales,
- clear source-locale identification,
- job progress and provider-offline state,
- side-by-side diff per locale,
- Apply selected, Discard, and manual editing,
- existing locales are not overwritten by default,
- publishing remains a separate button.

Settings → Translation provides URL, write-only credential field, connection test, quota, and a privacy warning.

---

## Security and privacy

- the provider receives only selected fields, not the entire admin resource or secrets,
- content text is not logged; logs contain provider, character count, locale, job ID, and result,
- the request runs as the invoking user and requires `content:read` + `content:write`,
- endpoint rate limits and quotas apply,
- a proposal has TTL and ACL; another user cannot read it without permission,
- provider output is untrusted input,
- disabled means zero outbound traffic.

---

## Failure scenarios

- provider offline/timeout → retryable job without content mutation,
- quota exceeded → `429`/clear state; proposal is not applied,
- partial target failure → per-locale result with retry for failed locales only,
- source revision changed → Apply `409`,
- invalid provider response → rejected proposal + sanitized incident,
- disabled provider → `503` or capability error without an outbound request.

---

## Out of scope

- cloud provider drivers (It.77),
- autonomous publishing,
- translation of secrets, logs, or system configuration,
- model training on customer content,
- unrestricted outbound endpoints,
- general AI agent (It.75).

---

## Tests

- mocked provider returns an SK→EN proposal,
- Markdown/HTML/Tiptap placeholders are preserved,
- private SSRF target without allow-list is blocked,
- explicitly allowed LAN provider works,
- quota/rate limit and concurrent counter writes,
- source-revision conflict during Apply,
- invalid schema response is rejected,
- no content text/API key in logs,
- disabled/offline → zero mutation and clear state,
- CI uses no live network.

---

## Definition of Done

- [ ] A self-hosted provider creates an SK→EN draft proposal in the editor.
- [ ] Apply writes through It.73 schema/OCC and audits `content.translated`.
- [ ] Publish is not part of Apply.
- [ ] Provider URL, secrets, quota, and logging have security tests.
- [ ] The shared provider interface is ready for It.77.
- [ ] Classic/disabled has no outbound traffic or mandatory service.
- [ ] SK/EN user, security, and deployment documentation is updated.

## Follow-ups

[It.77 cloud providers](ITERATION_77.md) · [It.75 AI agent](ITERATION_75.md)
