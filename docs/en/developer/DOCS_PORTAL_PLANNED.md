# In-CMS documentation portal (planned)

> **Status:** ⏳ proposal — not scheduled in `beta.96`  
> **Interim:** operator and developer docs live in the GitHub repo under `docs/en/` and `docs/sk/`; SK blog drafts in [docs/sk/marketing/](../../sk/marketing/README.md) link to those paths until this module ships.

---

## Goal

Give maintainers and agencies a **MkDocs-like** experience **inside PaginiumCMS**:

- Author pages in **Markdown** (same discipline as site content).
- Publish under a stable public prefix (e.g. `/docs/…`) with search and navigation.
- Apply **theme tokens** from Site Design (not a separate static site generator repo).

---

## Non-goals (v1)

- Replacing Git as the canonical source for core CMS architecture (keep `docs/en/` in repo for contributors).
- Arbitrary PHP/plugins in doc pages — same sanitization and CSP as public content.
- Multi-tenant doc hubs (see [ITERATION_98.md](../ITERATION_98.md) for workspace first).

---

## Suggested slices

| Slice | Deliverable |
|-------|-------------|
| **Docs.1** | Content type `doc-page` + flat-file storage + public route `/docs/{slug}` |
| **Docs.2** | Sidebar nav from folder tree or `nav.json`; optional full-text search via existing query index |
| **Docs.3** | Import/sync job from repo `docs/en/` (read-only mirror for self-hosters) |
| **Docs.4** | Custom layout shell — typography, code blocks, admonitions aligned with `pgLayout.css` |

---

## Security baseline

- Public read-only; mutating routes `content:edit` + CSRF as today.
- No raw HTML bypass; Mermaid/code blocks via existing trusted pipelines only.
- Outbound links unchanged — no new fetch surface.

---

## Related

- [PRODUCT_GROWTH_PROPOSALS.md](../architecture/PRODUCT_GROWTH_PROPOSALS.md)  
- [CONTINUATION.md](../CONTINUATION.md)
