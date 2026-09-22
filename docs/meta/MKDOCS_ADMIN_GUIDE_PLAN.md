# MkDocs — planned administration guide (settings + screenshots)

> **Status:** planned · not wired in CI yet  
> **Goal:** operator-facing documentation matching the live Settings schema and admin UI, in SK and EN.

---

## Why this exists

Iteration specs ([ITERATION_75.md](../en/ITERATION_75.md), [SETTINGS.md](../en/architecture/SETTINGS.md)) and runbooks describe **contracts**. Operators also need a **visual** guide: each Settings group, field helpers, test buttons, and failure modes. Today:

- Runbooks: [AGENT_OPERATIONS.md](../en/runbooks/AGENT_OPERATIONS.md), [CACHE_OPERATIONS.md](../en/runbooks/CACHE_OPERATIONS.md)
- User text: [ADMIN_GUIDE.md](../en/user/ADMIN_GUIDE.md)
- Context help in UI: `frontend/src/i18n/modules/settings/{en,sk}.ts` (`help`, `tooltip`, `tooltipDetail`, `docLink`)

MkDocs will unify these with screenshots taken from a **sanitized demo instance** (no real secrets, blurred hostnames if needed).

---

## Proposed MkDocs structure

```text
docs/site/                    # future mkdocs.yml root (TBD)
  en/
    admin/
      index.md
      settings/
        general.md
        engine.md
        agent.md              # CMS AI assistant + nginx proxy diagram
        translation.md
        connectors.md
        ...
      platform/
        scheduler.md
        backups.md
  sk/
    admin/                    # mirror structure, Slovak prose
  assets/
    screenshots/admin/        # WebP/PNG, versioned by release tag
```

Material for MkDocs theme; `nav` generated from the same group order as `SettingsSchema.php`.

---

## Screenshot checklist (per release tag)

| Area | Min. screenshots |
|------|------------------|
| Settings sidebar + group list | 1 |
| CMS AI assistant group + test connection | 2 |
| Translation group + test connection | 2 |
| Engine / cache probe (It.69) | 1 |
| Scheduler + backup automation | 2 |
| Editor: agent proposal diff + Apply | 2 |

Store under `docs/assets/screenshots/<tag>/` when the pipeline exists; until then, capture locally and attach to release notes.

---

## Wiring (later)

1. Add `mkdocs.yml` at repo root with `docs_dir: docs/site` or symlinked EN/SK trees.
2. CI job: `mkdocs build --strict` on doc changes (no secrets in built HTML).
3. Link from admin UI `docLink` keys to published URLs (stable `/admin/settings/agent#baseUrl` anchors).

---

## Immediate interim (shipped now)

- Runbooks and nginx examples in `docs/deploy/` and `docs/en/runbooks/`.
- Richer `help` / `tooltipDetail` / `docLink` in settings i18n pointing at GitHub-rendered markdown until MkDocs is live.
