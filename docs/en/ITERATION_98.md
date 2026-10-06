# Iteration 98 — Multi-site / workspace + CLI content operations

> **Status:** ⏳ planned (separate from `beta.95` gallery/lightbox and Experience Phase B1 motion)  
> **Priority:** strategic — largest item from the “growth proposals” list  
> **SSOT:** flat files remain authoritative; no SQL layout engine  
> **Related:** [NOSQL_MANDATE.md](architecture/NOSQL_MANDATE.md) · [CONTENT_EDITORIAL_WORKFLOW.md](architecture/CONTENT_EDITORIAL_WORKFLOW.md) · existing `content:import` / `content:export`

---

## 1. Problem statement

Operators who run **several client sites** (or staging + production trees) need:

1. A clear **workspace / site** boundary in data and config (not one accidental merge of `data/` trees).
2. **CLI content lifecycle** similar to WordPress WP-CLI — create/update/publish from scripts, CI, or SSH — **without** bypassing auth, audit, or validation on the web API.

This iteration is **not** “one React admin UI for unlimited remote CMS instances over the internet” in v1. It is **foundations**: tenancy model, path isolation, and safe CLI mutators that call the same services as HTTP controllers.

---

## 2. Terminology

| Term | Meaning in Paginium |
|------|---------------------|
| **Instance** | One deployed CMS (one `APP_ROOT`, one `data/`, one Docker stack). |
| **Workspace (98 target)** | Named slice **inside one instance** — separate content roots or prefixed trees (e.g. `data/workspaces/{id}/…`) with scoped settings. |
| **Multi-instance hub (out of 98 v1)** | Single admin SPA controlling **multiple** deployed instances via API — requires federation, secrets, and SSO; defer to 98+ or separate product line. |

Default product stays **one instance = one agency site** until workspace v1 proves stable.

---

## 3. Goals (98 v1)

| ID | Deliverable |
|----|-------------|
| **98a** | **Workspace model** — config key `general.activeWorkspace` (default `default`); content + media paths resolved under workspace base; migration tool copies legacy `data/` → `data/workspaces/default/`. |
| **98b** | **CLI read** — `content:list`, `content:show --type=page|article --slug=…` (JSON to stdout, no secrets). |
| **98c** | **CLI write (guarded)** — `content:create`, `content:update`, `content:publish` with `--dry-run` default; uses `ContentRepository` + same validators as API; requires env `CLI_ALLOW_MUTATIONS=1` or `--run` + POSIX owner check on `data/`. |
| **98d** | **Audit** — every CLI mutation logs via `SecurityAuditStore` / access log with `actor=cli`, script name, workspace id. |

---

## 4. Non-goals (98)

- Cross-instance search, unified billing, or remote “site switcher” in admin SPA (hub UI).
- Replacing the visual editor — CLI complements admin, does not duplicate Tiptap.
- Arbitrary shell access to `data/` (no `content:eval`, no raw PHP in CLI).
- Multi-site **shared** media CDN across unrelated clients without explicit export/import.

---

## 5. CLI design (WP-inspired, Paginium-safe)

### Principles

1. **Same brain as the API** — CLI commands inject `ContentController` services / repositories, not direct file writes (except import/export pipelines that already exist).
2. **Dry-run first** — mirror `content:import` (`--run` to persist).
3. **Fail closed** — mutating commands refuse when:
   - not running in `APP_ENV=development|production` with writable `data/` under expected uid,
   - `general.installed !== true`,
   - optional `CLI_ALLOW_MUTATIONS` unset in production (document in operator guide).
4. **No web session** — CLI uses **system actor** `cli` with permission profile documented in [ACCESS_CONTROL.md](user/ACCESS_CONTROL.md); optional `--as-user=uuid` for attribution (SUPER_ADMIN only, audit logged).
5. **UTF-8** — all writes via existing JSON helpers (`JSON_UNESCAPED_UNICODE`).

### Command sketch

```bash
# Read
php bin/console content:list --type=article --status=published --workspace=client-a
php bin/console content:show --type=page --slug=about --format=json

# Write (dry-run)
php bin/console content:create --type=page --slug=landing --title="Landing" --body-file=./draft.md
php bin/console content:update --type=article --slug=news/hello --body-file=./rev.md --dry-run

# Write (persist)
CLI_ALLOW_MUTATIONS=1 php bin/console content:update ... --run
php bin/console content:publish --type=article --slug=news/hello --run
```

### Phase 2 (98e+, after 98c stable)

- `content:patch` — YAML front matter field updates without full body replace.
- `workspace:create`, `workspace:switch` (CLI + settings).
- `media:attach` — link DAM path into article body via `:::video` / image markdown helpers.
- CI recipe in `docs/en/developer/CLI_CONTENT.md`.

---

## 6. Workspace data layout (proposal)

```text
data/
  workspaces/
    default/
      content/          # pages, articles (existing layout)
      settings/         # or symlink to shared settings with workspace overrides JSON
    client-b/
      content/
  workspace-registry.json   # id, label, createdAt, status
```

**Settings:** global keys (SMTP, APP) vs workspace-scoped (`general.siteName`, theme, gallery). Document merge order in `SettingsRepository`.

**Public routing:** v1 keeps **one public site per instance** — active workspace only. Multi-workspace **public** host mapping (domain → workspace) is **98f** (nginx + `general.workspaceHostMap`).

---

## 7. Security checklist (mandatory before ship)

- [ ] Path traversal — workspace id `[a-z0-9-]{1,32}` only; `realpath` under `data/workspaces`.
- [ ] CLI mutators — no bypass of `ContentEditorialReview`, draft locks, or Path ACL (respect or explicit `--force` with audit).
- [ ] Secrets — CLI output must not print settings password fields or `APP_KEY`.
- [ ] Import/export — workspace-aware paths; Zip-Slip unchanged.
- [ ] Regression tests — PHPUnit for workspace resolver + CLI dry-run; one integration test create→publish.

---

## 8. Relation to other proposals

See [PRODUCT_GROWTH_PROPOSALS.md](architecture/PRODUCT_GROWTH_PROPOSALS.md) for A/B tests, related articles, reading time, webhooks cross-post, and visual-effect extensions. **98 does not block those**; they can ship in parallel on `main` with smaller scopes.

---

## 9. Suggested order

1. **98a** workspace registry + `default` migration (read-only public behaviour unchanged).  
2. **98b** list/show CLI.  
3. **98c** create/update/publish with dry-run + audit.  
4. **98d** docs SK/EN + operator runbook.  
5. **98e** domain → workspace (optional).

---

## 10. Exit criteria

- Operator can maintain a **second workspace** on one instance and switch active workspace via settings/CLI.
- `content:update --dry-run` shows the same validation errors as admin save.
- `./scripts/iteration-gate.sh` green; CHANGELOG + CONTINUATION updated on tag.
