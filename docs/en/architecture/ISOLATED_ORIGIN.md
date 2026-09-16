# Isolated origin widgets (cancelled iteration — archive)

> **Status:** ❌ **cancelled as an iteration** (2026-09-15). Never implemented. Spec-only; no code, no settings keys, no shortcode.  
> **Was:** the first It.93 draft. Number **93** now means **admin** chrome + daily apps.  
> **This file** is an architecture archive (why iframe isolation was considered). It is **not** on the implementation queue. Do not schedule it. Do not invent a new iteration number for it.

---

# Isolated origin widgets (not a second CMS)

> **Status:** ❌ cancelled (archive). Not planned for implementation.  
> **Priority:** — (removed from the iteration queue)  
> **Wave:** Isolation of untrusted/dynamic page JS (extends [It.91](../ITERATION_91.md) embeds, [It.58](../ITERATION_58.md) shortcodes, [It.25](../ITERATION_25.md) setup)  
> **Depends on:** `ExternalEmbedShortcode`, `ShortcodeExpanderService`, `SecurityMiddleware` CSP, `OutboundUrlGuard`, ISS-162  
> **Does not replace:** Core Slim API, public React SPA, plugins, Theme Studio, No-SQL SSOT

## Better solution (decision)

The marketing “parallel Cloud app” (second Slim + second React + CORS + HMAC + setup-installed nginx) is the **wrong product** for Paginium. It doubles maintenance and still hydrates worse than what already exists.

**Ship this instead:** an operator-configured **isolated origin**, wired like YouTube/Vimeo in It.91.

| Layer | What Paginium Core does | What Core does **not** do |
|-------|-------------------------|---------------------------|
| Public page | Expand a shortcode to a **sandboxed `<iframe>`** (`loading="lazy"`) | Load widget `.js`, mount React, `fetch` the truck, `srcdoc`, `dangerouslySetInnerHTML` of sidecar HTML |
| Admin | Native settings: origin URL, health, copy/insert shortcode | A second admin SPA, federated scripts, iframe of a Cloud back-office as the primary UI |
| Install | Optional skippable origin field + copy-paste nginx | Auto-write nginx, start a second PHP-FPM, generate a Cloud `.env` |
| Sidecar app | Document a **reference** Slim+React (or any stack) the operator deploys | Treat `sidecar/` as a second official CMS that must ship in the same release |

Hydration is **server-emitted iframe**. No CMS client hydrator. That is the simplest, most usable, and the only option that keeps widget JS **out of the CMS origin** without weakening `script-src`.

Restaurant: the waiter puts an empty window on the table with the stall number. The food truck behind the glass does the cooking. The kitchen never takes the truck’s knives.

---

## What problem this actually solves

Public pages already run the **Core** React SPA (`script-src 'self'`). The `.js` / Code Policy problem is **untrusted extra JS** (themes, pasted scripts, plugin frontends), not the existence of the SPA.

| Wrong fix | Why it fails |
|-----------|----------------|
| Theme / `fetch-cloud.js` / `cloud-widgets.min.js` | New executable surface on the CMS origin; fights It.87 and CSP |
| Core widget runtime that `fetch`es JSON and mounts React from the network | Still CMS-origin JS; XSS in the runtime is CMS XSS |
| Second full Cloud CMS in the installer | Two products, two auth stacks, two admin UIs; isolation is real but the cost is a second Paginium |

| Right fix | Why it wins |
|-----------|-------------|
| **It.91-style iframe** to the operator origin | Widget JS, cookies, and XSS stay on that origin. CMS HTML is inert. Same pattern as YouTube. The origin may be a VPS **or** a serverless/edge deployment. |

**JS isolation invariant**

| Origin | Widget / dynamic page JS |
|--------|--------------------------|
| CMS (`example.com`) | **No.** Expander output is iframe + optional static fallback text. |
| Isolated origin (`cloud.example.com`, `*.workers.dev` host, Lambda Function URL, Vercel/Netlify app) | **Yes.** Entire interactive app, including its own `fetch` to weather/FX APIs. |

---

## Decision tree (do not mix tracks)

Start at the top. Most “dynamic” marketing effects stop at **Zero-JS**.

| Need | Track | Extra JS on CMS origin? |
|------|-------|-------------------------|
| Animation, hover, dropdown, simple open/close, parallax-lite, theme-colored chrome | **Zero-JS** — HTML + CSS in theme / layout shortcode / snippet (`:hover`, `:focus-within`, `@keyframes`, `<details>`). `themeScriptsEnabled` stays **false**. | No (theme). Core SPA JS already exists. |
| Reusable copy / layout | Snippet / layout shortcode (**shipped**) | No |
| Widget needs CMS articles, users, `data/` | **It.89 plugin** | No extra theme JS |
| Live data / untrusted compute (weather, FX, cloud calc, vendor form) | **It.93 isolated origin iframe** (VPS **or** Lambda / Workers / Vercel / Netlify) | No — JS runs in the iframe origin |
| First-party calculator in the existing public SPA | **Not It.93.** Separate iteration; no blast-radius isolation | Yes (Core bundle only) |

This track does **not** implement menus, modals, or dark mode. Those are [THEMES.md — Zero-JS](THEMES.md). Sending chrome to a Worker is wasted money and worse UX.

**Zero-JS caveat:** checkbox hacks are fine for decorative disclosure. Accessible modal/dialog behavior (focus trap, Escape) stays in Core React, not in untrusted theme CSS.

---

## Architecture

```text
Browser
  │
  ├─ https://example.com          CMS (existing Slim + React)
  │     HTML: [isolated-widget id="loan-calc-v1"/]
  │        → <iframe sandbox="…" loading="lazy"
  │             src="https://cloud.example.com/w/loan-calc-v1">
  │     script-src unchanged; frame-src += cloud origin when enabled
  │
  └─ isolated origin (operator-chosen HTTPS host)
        self-hosted Slim / Node
        OR Cloudflare Worker / Lambda Function URL / Vercel / Netlify
        Serves /w/{id} (full UI) + calls weather/FX/APIs from THAT origin
        Must not mount CMS storage/ or data/
```

**Operator isolation (infra, not PHP):** if self-hosted — separate document root, UID/container, host-only CMS cookies. If serverless — the vendor account **is** the isolation boundary; still never share CMS `data/`, still host-only CMS cookies, still no `Domain=.example.com`.

**CMS → isolated origin HTTP** is only the optional health ping (`OutboundUrlGuard`). Public hydration does **not** need CORS, HMAC, or CMS `connect-src`. The iframe document talks to weather/Lambda/its own Worker with **its** `fetch` — that is `connect-src` on the **isolated** origin, not on CMS.

HMAC / widget catalog is **out of v1**. The editor pastes or picks an `id`; unknown ids 404 inside the frame.

---

## Serverless / Edge (AWS, Vercel, Netlify, Cloudflare)

Use them. Do **not** load their `.js` on the CMS page.

The marketing pattern “frontend calls Lambda / Edge by inserting `<script src="https://….workers.dev/widget.js">`” is the **same hole** as `fetch-cloud.js`: it puts vendor JS on `example.com`, requires `script-src` extras, and a compromised or swapped script is CMS XSS.

| Pattern | Allowed? | How |
|---------|----------|-----|
| `<script src="https://lambda-url…/app.js">` on the CMS page | **No** | Never add Workers/Vercel/Lambda to CMS `script-src` |
| CMS public SPA `fetch('https://….vercel.app/api')` | **No** (v1) | Would need CMS `connect-src`; XSS on CMS can then abuse it. Weather/FX stay inside the iframe app |
| CMS PHP fetches JSON (weather) via `OutboundUrlGuard`, prints numbers in HTML | Different feature (plugin / Core data embed) | No JS. Not It.93 |
| **Iframe `src` = Function URL / Worker / Vercel origin** | **Yes — this is It.93** | Same shortcode. Origin in settings is `https://widgets.<account>.workers.dev` (exact host, not `*.workers.dev`) |
| Worker/Lambda internally `fetch`es OpenWeather, ECB, etc. | **Yes** | Runs on the isolated origin. CMS never sees those keys |

**Exact host, never a vendor wildcard.** `frame-src https://*.workers.dev` would let any Worker on the internet frame into the page. Settings store one origin: `https://loan-calc.yourname.workers.dev`.

Examples of valid `isolatedOrigin.origin`:

- `https://cloud.example.com` (VPS + nginx)
- `https://widgets.yourname.workers.dev`
- `https://xxxxx.lambda-url.eu-central-1.on.aws`
- `https://your-app.vercel.app`
- `https://your-app.netlify.app`

Health ping: `GET {origin}/health` or `GET {origin}/` through `OutboundUrlGuard` (HTTPS, no private/link-local). A Worker that only serves `/w/{id}` should still expose a cheap `/health`.

`pathTemplate` (v1 default `/w/{id}`): allow-listed patterns only (`/w/{id}`, `/{id}`, `/?id={id}`). No free-form URLs — that would be an open redirect into the iframe.

---

## Verdict on the marketing outline

Keep the isolation idea. Drop the parallel product.

| Claim | Decision |
|-------|----------|
| Same host for CMS + Cloud FE + Cloud BE | Reject. Different origin required. |
| Auto-rewrite nginx from the wizard | Reject (ISS-162). Copy-paste include. |
| Global fetch-cloud.js / `<script src>` from Lambda/Workers/Vercel | Reject. Iframe `src` to that origin instead. |
| Slim + React for the truck | Allowed as a **reference**. Serverless/Edge is a first-class origin, not a lesser cousin. |
| Cloud HTML injected into CMS | Reject. |
| JWT shared with CMS session | Reject. Not needed for v1 iframe. |
| SQLite in the operator app | Allowed for **that** app only. Not CMS SSOT. Not It.92. |

---

## What already exists (reuse)

| Piece | Role |
|-------|------|
| `ExternalEmbedShortcode` (It.91) | Copy iframe construction: allow-listed `src`, `sandbox`, `loading="lazy"`, escaped attrs |
| `ShortcodeExpanderService` (It.58d) | Expand `[isolated-widget …]` / `[cloud-widget …]` |
| Snippets (It.81) | Wrap the shortcode in reusable copy |
| Setup wizard (It.25) | Optional skippable origin field |
| `SecurityMiddleware` | Add `frame-src` token only when enabled + origin valid |
| `OutboundUrlGuard` | Health ping |
| Embed insert wizard (It.91) | UX to copy into the editor |

---

## Settings (`isolatedOrigin.*`)

Disabled by default. Missing keys = no iframe, no extra CSP.

```yaml
isolatedOrigin:
  enabled: false
  origin: ''              # exact HTTPS origin — VPS or Worker/Lambda/Vercel/Netlify. No path, no wildcard.
  pathTemplate: '/w/{id}' # allow-list: /w/{id} | /{id} | /?id={id}
  publicWidgets: true
```

| Key | Default | Rule |
|-----|---------|------|
| `enabled` | `false` | Off → never emit iframe. |
| `origin` | `''` | Exact host. Probe (`OutboundUrlGuard`) before enable. Not `*.workers.dev`. |
| `pathTemplate` | `/w/{id}` | Only the three allow-listed patterns. |
| `publicWidgets` | `true` | Fail-closed if origin empty. |

No shared secret in v1. **Never auto-enable.**

I18n/schema may keep a `cloudSidecar` alias in the admin copy if product language stays “Cloud”; the storage keys above are the contract.

---

## Setup wizard

Skippable step after Infrastructure. Default Skip.

- Origin URL (self-hosted **or** Worker / Lambda Function URL / Vercel / Netlify).
- Path template picker (default `/w/{id}`).
- Copy-paste nginx include **only if** the origin is a hostname the operator terminates locally. Hidden/skipped when the origin is a known serverless host.
- Optional health ping; failure is a **warning**, not a hard block.
- Do not start processes, write `/etc/nginx`, or deploy to AWS/Vercel from the web request.

---

## Public shortcode (hydration = expander iframe)

```text
[isolated-widget id="loan-calc-v1" title="Splátková kalkulačka" height="420"/]
```

Alias `[cloud-widget …]` may expand to the same handler.

**Expander output** (same construction style as It.91 — **no** `<script>`, **no** placeholder hydrator):

```html
<iframe
  class="paginium-isolated-widget"
  src="https://cloud.example.com/w/loan-calc-v1"
  title="Splátková kalkulačka"
  width="100%"
  height="420"
  loading="lazy"
  referrerpolicy="strict-origin-when-cross-origin"
  sandbox="allow-scripts allow-forms allow-same-origin"
></iframe>
```

Rules:

- `src` built from settings `origin` + `pathTemplate` + `id` only. `id` = `[a-z0-9-]{1,64}`. Reject `..`, `/`, `:`, query, hash.
- `origin` from settings, not from the shortcode (author cannot point the iframe at `https://evil.example`).
- `allow-same-origin` is required so the **iframe document** can use its own API cookies on the isolated origin. Parent is a different origin, so this does not grant the widget the CMS origin.
- Do **not** add `allow-top-navigation`.
- Disabled / empty origin / `publicWidgets=false` → static fallback (`<p class="paginium-isolated-widget-fallback">…</p>`), never a broken iframe.
- Height default 420; optional `height` attr clamped (e.g. 120–2400). Optional later: `postMessage` resize from the iframe (`93f`).

Snippets may wrap the shortcode. Sanitizer must **allow this Core iframe** the same way It.91 allows provider iframes — not author-pasted arbitrary iframes.

---

## Admin (existing SPA)

`/platform/isolated-origin` (or Cloud label), `settings:manage`:

1. Enable, origin, health.
2. Show the exact shortcode to copy.
3. Editor insert panel: same family as embed wizard (id + optional title/height).

No requirement to iframe a Cloud admin. Operator opens `origin` in a new tab to build widgets. An optional “open origin” link is enough for v1.

---

## Operator app (reference only)

Core DoD does **not** require a Slim tree or a cloud vendor account in this repo.

The isolated origin is **whatever HTTPS host** the operator configured:

- VPS: Slim/React (or Go/Node) behind nginx
- **Cloudflare Workers / Pages**, **AWS Lambda Function URL** or API Gateway, **Vercel** / **Netlify Edge**

Minimum contract for any of them:

- `GET` widget URL returns HTML UI (not a raw `.js` file meant for the CMS page)
- `GET /health` (or documented ping path) for the wizard
- `Content-Security-Policy: frame-ancestors https://example.com` (CMS origin)
- secrets (OpenWeather, FX) stay in the vendor env — never in CMS settings plaintext logs
- no filesystem access to CMS `data/` / `storage/`

A later `examples/isolated-widget/` may show one Worker **or** one Slim app. Neither is a Core release blocker.

---

## CSP (CMS)

When enabled and origin valid:

- Add that **exact** origin to **`frame-src`** only (one host, no `*.workers.dev` / `*.vercel.app` wildcards).
- Do **not** add it to `script-src` or public `connect-src`.
- Do **not** allow the isolated origin to frame CMS.

Sidecar CSP is the operator’s problem; recommend `frame-ancestors` = CMS origin.

---

## Security tests

| Test | Pass if |
|------|---------|
| Isolation | Operator-app UID cannot write CMS `data/` (documented + fixture if example ships) |
| Code Policy | Expand output has no `<script>`; `script-src` unchanged |
| Src lock | Shortcode `origin` / `src` attributes ignored; only settings origin is used |
| Id lock | `id=../x`, `id=https://…` emit fallback, not iframe |
| Cookie | Scripts in the iframe do not see CMS session cookie |
| Fail-closed | Off / empty origin → no `frame-src` extra, no iframe |
| TTFB | HTML TTFB comparable; iframe is `loading="lazy"` |
| No vendor wildcard | Origin `https://evil.workers.dev` is not allowed just because another Worker host is enabled |
| No script-src | Enabling a Worker origin does not add it to CMS `script-src` |

---

## Slices (void)

These IDs belonged to the **cancelled** iteration. They are not work items. Do not implement them as 93a–q (those IDs now mean admin chrome slices).

| ID (void) | Work (not scheduled) | Status |
|-----------|----------------------|--------|
| ~~93a~~ | Settings `isolatedOrigin.*` + i18n | ❌ cancelled |
| ~~93b~~ | Setup skippable origin + nginx copy-paste + preflight ping | ❌ cancelled |
| ~~93c~~ | CSP `frame-src` contributor (fail-closed) | ❌ cancelled |
| ~~93d~~ | Core shortcode → It.91-style iframe + sanitizer allow + insert panel | ❌ cancelled |
| ~~93e~~ | Admin connect page (health, copy shortcode) | ❌ cancelled |
| ~~93f~~ | Height clamp + optional `postMessage` resize schema | ❌ cancelled |
| ~~93g~~ | Optional reference app + runbook | ❌ cancelled |
| ~~93h~~ | Tests above | ❌ cancelled |

These slice IDs must not be implemented. Admin chrome `93a`–`93q` are defined in [ITERATION_93.md](../ITERATION_93.md).

---

## Out of scope

- Second official CMS (Slim admin + React Cloud modules in Core)
- Widget JS on the CMS origin (Core runtime, theme scripts, CDN scripts, **Lambda/Workers/Vercel `<script src>`**)
- CMS `script-src` or public `connect-src` for `*.workers.dev`, `*.vercel.app`, `*.netlify.app`, `*.lambda-url.*.on.aws`
- Vendor wildcard `frame-src`
- HMAC catalog, shared JWT/session, CORS for public widgets
- Auto-enable, auto-nginx, SQLite as CMS SSOT
- Replacing It.89
- First-party in-SPA widget catalog (separate iteration if ever needed)
- Theme menus / dark-mode / animations via iframe or theme JS (that is Zero-JS / Core appearance — [THEMES.md](THEMES.md))

---

## Definition of Done

**None.** This iteration was cancelled before any slice landed. The bullets below are historical intent only — they are not a current contract.

- Default install: zero iframe, zero CSP delta.
- Enabled: one sample `id` on a configured origin works from a shortcode **without** lowering `script-src`.
- Author cannot retarget `src` to a third site via shortcode attributes.
