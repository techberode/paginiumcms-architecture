# Iteration 95 — Component playground & private design-system registry

> **Status:** ✅ shipped in tree (`v2.1.0-beta.90` changelog) — **95a/c/b/d** complete. **Rollout** on a live instance is operator-driven (Settings + SUPER_ADMIN).
> **Priority:** 🟡 **P1** for developers/theme authors · 🔵 **P2** for optional admin widgets from private repos  
> **Depends on:** [It.88](ITERATION_88.md) Theme Studio + sandbox preview · [It.89](ITERATION_89.md) manifest/capabilities · [It.90](ITERATION_90.md) Editor Tool SDK · [Code Policy](architecture/CODE_POLICY.md) · `OutboundUrlGuard`  
> **Does not replace:** It.58f page outline (amateur blocks), full MDX/React-in-article (out of scope)

## Goal

Offer a **CodeSandbox-like experience** inside PaginiumCMS **without** weakening security: experiment with UI components in a **browser sandbox**, optionally **attach** approved output to themes, shortcodes, or editor tools — and let operators **enable/disable** registered component packs or **import a library from their own Git repository** through the same staged pipeline as extensions (scan → manifest → enable), not arbitrary `npm install` on the production PHP host.

Two complementary surfaces:

| Surface | User | Runtime |
|---------|------|---------|
| **A — Playground** (Sandpack-style) | Theme/editor developers | Browser only; bundled deps from allow-list |
| **B — Monaco** (existing) | Same users | Save to allow-listed paths; policy gate on write |

Playground is **not** a second SSOT for content; export/save is always explicit and validated.

---

## Why not “embed codesandbox.io” or run npm on the server?

| Approach | Verdict |
|----------|---------|
| iframe `codesandbox.io` | Third-party data egress, CSP, offline/air-gapped deploys — **not default** |
| `npm install` inside PHP container on user URL | RCE/supply-chain risk — **forbidden** on production |
| **Sandpack** (`@codesandbox/sandpack-react`) in admin SPA | Bundling in browser; deps from **pinned allow-list** or pre-vetted pack — **recommended for 95a** |
| **Git repo → ZIP import** (existing extension pattern) | Private design-system as repo; CI or admin “Import from Git” with token + scan — **recommended for 95c** |
| **Build-time** private npm (deploy token in `npm ci`) | Org packages linked at **frontend build** / deploy — **ops path**, documented in DEPLOY |

---

## Settings contract (planned)

New settings group e.g. `playground` / `designSystem` (exact keys in `SettingsSchema` at implementation):

| Setting | Purpose |
|---------|---------|
| `playgroundEnabled` | SUPER_ADMIN gate; off in `DEMO_MODE` |
| `enabledComponentPacks[]` | IDs from **registry catalog** (bundled + imported packs) |
| `defaultTemplate` | `react-ts` \| `vanilla` \| `vue` (allow-list only) |
| `privateGitRepoUrl` | Optional; import flow only (HTTPS + deploy token), never silent background pull |
| `privateGitRef` | branch/tag/commit pin |

**“Install individual components”** means **toggle packs or modules declared in a manifest**, not free-form npm search:

```json
{
  "packId": "acme-admin-widgets",
  "manifestVersion": 1,
  "source": { "type": "git", "url": "https://github.com/org/design-system.git", "ref": "v1.2.0" },
  "modules": [
    { "id": "stat-card", "sandpackEntry": "components/StatCard.tsx", "capabilities": ["admin-ui:widget"] }
  ]
}
```

Import pipeline reuses **It.78 upload policy** + **CodePolicyEngine** on any shipped JS/TS that can run server-side; browser-only packs skip PHP execution entirely.

---

## Slices

| ID | Work | Outcome |
|----|------|---------|
| **95a** | **Sandpack playground** route or Theme Studio tab | ✅ `/playground` + Theme Studio link; `@codesandbox/sandpack-react`; `GET /api/admin/playground` + `/assets/{pack}/{path}` |
| **95b** | **Monaco bridge** | ✅ “Open in playground” from Theme Studio / Code Editor for allow-listed `.tsx`/`.jsx`/`.css`/`.html`/`.js`; export returns through `themesApi.validate` / normalize (88b) into the Monaco buffer — playground never writes disk |
| **95c** | **Registry + settings toggles** | ✅ Settings `playground.*` + bundled `paginium-starter`; imported slots in `data/playground-packs.json` |
| **95d** | **Private Git import** | ✅ Admin action: fetch zipball/zip from configured repo (encrypted token); Zip-Slip + scan; register pack; **no** auto-update cron |

Recommended order: **95a → 95c → 95b → 95d** (playground visible first; Git import last).

---

## Integration map

```text
Settings (enable packs)
    → Playground UI (Sandpack)
    → optional Export → Theme Studio / shortcode expand / Editor Tool manifest (It.90e + It.89)
Monaco (existing) ←→ same files via 95b
Private repo → Import ZIP/Git (95d) → registry → toggles in Settings
```

- **It.89:** editor tools and plugins that need server render still declare **capabilities**; playground-only widgets stay FE sandbox.  
- **It.94:** toasts for import/save feedback (94a).  
- **Footer/version API:** unrelated.

---

## Security (mandatory)

- Playground iframe/sandpack: **no** access to admin cookies beyond same-origin admin; no `eval` of server secrets.  
- User-authored preview code: treat as **untrusted**; CSP for playground route stricter than public site.  
- Git/npm URLs: **`OutboundUrlGuard::assertAllowed()`** before fetch; tokens encrypted at rest (`password` settings fields).  
- Exported code saved to disk: **`CodePolicyEngine::validateUntrusted`** + theme/shortcode scanners — **no weaker Monaco path**.  
- Regressions: Vitest for Sandpack shell; PHPUnit for import guard + registry schema.

---

## Out of scope

- Public multi-tenant “Codesandbox for visitors”.  
- Arbitrary npm registry search inside production admin.  
- PHP plugins executing TypeScript from playground.  
- Replacing Theme Studio Monaco tabs entirely.

---

## Definition of Done

- SUPER_ADMIN can open playground, run a template from allow-list, see live preview.  
- At least one **bundled** component pack toggled via Settings.  
- Optional: import pack from **private Git** on a test repo; failed scan blocks enable.  
- Monaco bridge exports one validated artifact into an existing theme or snippet path.  
- `./scripts/iteration-gate.sh` green; SK/EN i18n; DEPLOY note for build-time private npm (optional org packages).

---

## Production rollout checklist (It.95 on server)

After deploy of a build that includes beta.90+ (tag e.g. `v2.1.0-hotfix.1` per [RELEASE §4.1](developer/RELEASE.md#41-operational-patch-tags-production-ui-deploy-no-main-checkout)):

1. **Settings → Component playground** — enable Sandpack; pick default template; enable bundled **`paginium-starter`** pack if toggles exist.
2. **SUPER_ADMIN** — open **`/playground`**; confirm live preview loads (CSP allows Sandpack only when playground is enabled).
3. **95b** — from Theme Studio / Code Editor, **Open in playground** on a small `.tsx` snippet; export back via validate path (no direct disk write from Sandpack).
4. **95d (optional)** — configure Git import URL + encrypted token; run import once on a test repo; failed scan must block registration.
5. **Demo** — confirm playground stays off when `DEMO_MODE` is on.

Deploy note: [DEPLOY.md §16.5](../deploy/DEPLOY.md#165-component-playground-it95).

---

## Queue

**95a/c/b/d shipped.** [It.69 Redis cache driver](ITERATION_69.md) is optional derived cache (Docker prod stack + Setup probe). Do not invent 95e. [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md), [CONTINUATION.md](CONTINUATION.md).
