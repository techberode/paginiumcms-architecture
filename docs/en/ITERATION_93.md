# Iteration 93 — Falcon-inspired admin (chrome + daily apps)

> **Status:** ⏳ partial (Wave 1–2 chrome + 93k Teams + **93o account** + **93n events** + **93p time tracker** + **93u catalog menu** + **93v coming soon** + **93w responsive chrome** shipped; Wave 4–5 support/mail remain)  
> **Priority:** 🟡 **P1 operator UX** — after [It.58f](ITERATION_58f.md) publishing blocks  
> **Wave:** Admin chrome, then Falcon-like **apps** on our data  
> **Depends on:** `ResponsiveLayout`, dashboard/analytics APIs, users/roles (It.84), project planner (It.87), comments/messages, SMTP settings  
> **Does not replace:** public Theme Studio look, Origin Panel, SQL  
> **Reference look:** [Falcon v3.26 dashboard](https://prium.github.io/falcon/v3.26.0/index.html) · [Aurora account](https://aurora.themewagon.com/pages/account) · [Aurora time tracker](https://aurora.themewagon.com/apps/time-tracker) — **inspiration, not a copy**

Former isolated-origin It.93 was **cancelled** (never implemented). Archive only: [architecture/ISOLATED_ORIGIN.md](architecture/ISOLATED_ORIGIN.md).

## Why this iteration

The kitchen pass (admin) should match a modern SaaS console **and** grow the apps operators actually run a company site with: analytics, teams, support, domain mail, events, project time.

Restaurant: Falcon is the dining-room *style guide*. We still cook **our** food (pageviews, comments, project plans) — we do not serve Falcon’s fake “Weekly Sales”.

---



## What “Falcon Apps” maps to in Paginium

Falcon’s **App** sidebar is Calendar / Chat / Email / Events / E-commerce / LMS / Kanban / Social / Support desk. We do **not** clone shops, courses, or social.


| Falcon / Aurora                                                 | Paginium (this iteration)                                                                     | Already in Core                                           |
| --------------------------------------------------------------- | --------------------------------------------------------------------------------------------- | --------------------------------------------------------- |
| Dashboard (KPI row, widget grid, storage bar, project progress) | Restyle `DashboardView` with **our** stats + planner progress                                 | Dashboard overview, disk, It.87 widget                    |
| Dashboard → Analytics                                           | Restyle `/analytics` (same tabs/APIs: overview, pages, sources, devices, geo, bots, 404)      | It.6 + later analytics                                    |
| Management                                                      | **Teams**: group CMS users; Support team is a team type                                       | Users + custom roles (`/security/roles`)                  |
| Support desk                                                    | Tickets + agents (CMS users on the Support team). Foundation first, then canned replies / SLA | Comments + contact messages stay; tickets are a new store |
| App → Email                                                     | **Domain mail inbox** (IMAP) for `@site-domain` mailboxes only, session required              | SMTP send exists; **no IMAP yet**                         |
| App → Events                                                    | Public/company **events** (list + create). Not the editorial calendar                         | Editorial calendar = content go-live (It.81d)             |
| App → Calendar                                                  | Restyle editorial calendar; events have their own calendar view                               | Editorial calendar                                        |
| App → Chat                                                      | **Out.** Use comments + messages                                                              | Inbox                                                     |
| Aurora account                                                  | Extended **user profile** tabs (avatar, bio, job, timezone, notify prefs, security link)      | Users: name/email/role/2FA/avatar/bio                     |
| Aurora time tracker                                             | Time entries on a **project plan item** or **event** (start/stop + log)                       | Project planner items, no timer                           |
| Modules (Forms, Tables, Charts, Widgets)                        | Shared admin kit used by all of the above                                                     | Ad-hoc Tailwind per view                                  |
| Modules Maps / E-commerce / LMS / Kanban / Social               | **Out** unless a later spec says otherwise                                                    | Geo is already an analytics tab                           |


---



## Wave 1 — Chrome (look of the kitchen)

Same as before: canvas, cards, light sidebar, **opaque** topbar (flex sibling of the scroll pane — not sticky over content), search → command palette, list toolbars, dark tokens. Primary chips use `admin-chip` / `admin-chip-on` so label color stays readable in both modes. Operators pick sidebar/topbar color (8 swatches), optional gradient, and side vs top dropdown nav in Settings → Admin UI — not the public theme.

Shared **module kit** (Falcon Modules, useful subset only): `AdminKpiCard`, `AdminWidgetCard`, `AdminDataTable`, `AdminToolbar`, chart wrappers around existing analytics charts. No Chart.js-from-Falcon, no Falcon SCSS.

---



## Wave 2 — Existing apps, Falcon layout



### Dashboard (full grid)

Keep our numbers. Layout like Falcon Default:

- KPI row: pages, articles, media, visitors (from overview/analytics — **real** fields only)
- “Running projects” → default project plan progress (It.87), not fake product names
- Storage bar → existing disk/quota widget
- Activity / shared files → existing activity + recent media
- Charts → existing dashboard/APM charts in card chrome

No weather widget. No e-commerce revenue table.

### Analytics

`/analytics` already has overview / pages / sources / devices / geo / bots / 404. It.93 restyles:

- KPI cards with trend chips (API already has `trends`)
- Period control in the card header
- Charts and ranked bars in white widgets
- Same export/ban actions, new buttons

No second analytics store.

---



## Wave 3 — Teams, account, events, time



### Teams (Management)

Flat-file `data/teams/{id}.json` (`team@1`). A team has `name`, `type` (`editorial`  `support`  `ops`  `custom`), `memberUserIds`. Built-in types use the type label; **custom** requires an operator-entered name.

- ADMIN+ manage; members can be listed on Support desk.
- Does **not** replace RBAC. Roles stay `/security/roles`. Teams are grouping + Support agent pool.



### Extended user account (Aurora)

Route e.g. `/users/{id}` or `/account` tabs:


| Tab         | v1 fields                                                                                                                                                         |
| ----------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Profile     | avatar, name, email, username, bio, job title, phone (optional), timezone                                                                                         |
| Public card | optional address, experience, education, social accounts (direct chat / channel notify); per-field publish flags for Contact / Support cards (`false` by default) |
| Security    | link to existing `/account/security` (2FA, password) — do not fork                                                                                                |
| Preferences | admin locale, notify toggles (reuse notification settings where they exist)                                                                                       |


Self-edit is opened from the **header identity**, **sidebar user block**, and **top-nav first item** (before Dashboard). Not a Platform nav item. Other users stay in `/users` (ADMIN+).

New fields need schema defaults (`?? ''`). Secrets stay password-typed. GDPR export must include new profile fields. Public `GET /api/public/staff` returns stripped cards only.

### Content share (93o)

Settings → Content: master switch + per-network (Facebook, X, LinkedIn, email, copy link) + articles vs pages. Intent URLs only (no SDKs). Home page is never shared.

### Events planner

New SSOT `data/events/{id}.json` (`site-event@1`): title, slug, startsAt, endsAt, location, body (Markdown), status (`draftpublished`), optional `projectPlanId`.

- Admin: list (calendar + table) + create/edit.
- Public listing is **optional later** (not required to close 93n). Editorial calendar remains content scheduling.



### Time tracker (Aurora pattern, our projects)

Entries in `data/time-entries/{id}.json`: `userId`, `target` (`planItem`  `event`), ids, `startedAt`, `endedAt`, `seconds`, `note`.

- UI: running timer, today/week table, filter by plan.
- Permission `time-entry:manage` (own entries); ADMIN can see team totals.
- Computed only — do not write progress % into the plan document.

---



## Wave 4 — Support desk (foundation → expand)

**93l-1 foundation**

- Tickets: `data/support-tickets/{id}.json` — subject, body, status (`openpendingclosed`), `assigneeUserId` (must be on Support team), requester email/name, timestamps.
- Admin list + detail (Falcon ticket chrome: status chips, assignee).
- Add/remove Support team members = Teams UI with `type=support` (same settings as Falcon agents at a **basic** level: user, role, active, avatar — not a second HRIS).

**93l-2 later (same iteration if time, else explicit remainder in this spec)**

- Internal notes, canned replies, tags, SLA dueAt.

Comments and contact **messages** stay. A ticket may *link* a message id; do not merge stores.

---



## Wave 5 — Domain mail inbox (strict)

Not Falcon’s demo mailbox. A **logged-in** mail viewer for mailboxes **under the site’s domain only**.

### Rules (fail-closed)


| Rule        | Detail                                                                                                                                                                |
| ----------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AuthN       | Session required. No public `/api/mail`.                                                                                                                              |
| AuthZ       | `mail:read-own` (mailbox == user email); `mail:read-all` ADMIN+ for every configured site-domain box.                                                                 |
| Domain lock | Site domain from settings (`general` / company website host). Mailbox local-part `@` that **exact** domain. Reject `user@gmail.com`, IDN tricks, `user@evil.example`. |
| Transport   | IMAP **read** (and optional SMTP send via **existing** smtp group). Host through `OutboundUrlGuard`.                                                                  |
| Secrets     | IMAP passwords `type=password` in settings / per-mailbox secret in user or mailbox record — `EncryptionService`, never logs.                                          |
| Scope       | No arbitrary IMAP URL from the editor. One configured mail host (e.g. `mail.example.com`).                                                                            |
| Body        | Do not dump full mail archives into `data/` as SSOT. Proxy IMAP for the session; optional short cache with TTL + path guard.                                          |
| Audit       | Every mailbox open / message read → `LogSanitizer` audit.                                                                                                             |
| CSRF        | Mutating send uses global CSRF.                                                                                                                                       |


v1: list folders + message list + read pane (Falcon inbox layout). Send can reuse SMTP in a follow-up inside 93m if the read path is green.

**Not v1:** fetching the whole internet, shared JWT to Roundcube, storing `.eml` in git, wildcards `*@`*.

---



## Security / quality (all new stores)

- Mutating `/api/*` → `AuthMiddleware` + `PermissionMiddleware`.
- Path traversal, Zip-Slip N/A unless attachments: attachments only via DAM allow-list.
- No Falcon/Aurora JS or HTML in the repo.
- SK/EN i18n for every new screen.
- Gate green per slice.

---



## Slices


| ID      | Work                                                                                                      | Status                                                         |     |
| ------- | --------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------- | --- |
| **93a** | Admin tokens + shell (canvas, cards, light sidebar, topbar)                                               | ✅                                                              |     |
| **93q** | Shared kit: KPI card, widget card, offer card, toolbar, data table (Modules: forms/tables/charts/widgets) | ✅                                                              |     |
| **93f** | Persisted nav collapse, topbar search → palette                                                           | ✅                                                              |     |
| **93b** | Dashboard Falcon grid (our KPIs, planner, storage, activity)                                              | ✅                                                              |     |
| **93j** | Analytics Falcon layout (existing `/analytics` APIs)                                                      | ✅                                                              |     |
| **93c** | Workspace + inbox list chrome                                                                             | ✅                                                              |     |
| **93k** | Teams (management) + Support team type                                                                    | ✅                                                              |     |
| **93o** | Extended user account (Aurora-like tabs)                                                                  | ✅ + chrome entry, public card, share bar, widget-card sections |     |
| **93n** | Events planner (list + create)                                                                            | ✅                                                              |     |
| **93p** | Time tracker on plan item / event / page                                                                  | ✅                                                              |     |
| **93u** | Catalog side menu (levels, hover preview, left/right, sticky; no clash with header)                       | ✅                                                              |     |
| **93v** | Coming-soon countdown linked to a page/article and published as part of that page                         | ✅                                                              |     |
| **93w** | Viewport audit: header/side/hamburger never overlap; grids adapt with side column                         | ✅                                                              |     |
| **93l** | Support desk foundation (tickets + agents)                                                                | ⏳                                                              |     |
| **93m** | Domain IMAP inbox (rules above)                                                                           | ⏳                                                              |     |
| **93d** | Settings / navigation form chrome                                                                         | ✅                                                              |     |
| **93e** | Content editor shell chrome                                                                               | ✅                                                              |     |
| **93g** | Dark-mode token parity + admin light/dark toggle                                                          | ✅                                                              |     |
| **93r** | Admin chrome colors (8) + gradient + top dropdown nav                                                     | ✅                                                              |     |
| **93s** | Settings Apply preview + floating Apply/Save dock                                                         | ✅                                                              |     |
| **93t** | Public **Widgets** (visual catalog + markdown insert) — CoreUI/Konrix *inspired*, not vendored            | ✅ a–c (d later)                                                |     |
| **93h** | SK/EN leftovers, gate                                                                                     | ⏳                                                              |     |


Order: **Wave 1** `a → q → f → g` · **Wave 2** `b → j → c → d → e` · **Wave 3** `k → o → n → p` · **Wave 4–5** `l → m` · `h` with each wave.

`93l` may ship foundation only; remainder stays listed in this spec (no new iteration number).

### 93t — Public widgets (stepped)

Inspired by [CoreUI widgets](https://coreui.io/react/docs/components/widgets/) and [Konrix UI](https://themes.coderthemes.com/konrix_r/ui/) — **look and density only**. No ThemeWagon/CoreUI source in git.

Restaurant: Widgets are plated dishes on the public table. The waiter (markdown `[widget type="kpi" … /]`) carries a ticket; the chef (PHP `WidgetCatalog`) plates HTML; the admin **Widgets** tab is the pass window with a visual picker (not JSON like Shortcodes).


| Slice     | Scope                                                                                         |
| --------- | --------------------------------------------------------------------------------------------- |
| **93t-a** | Built-in catalog + expand `[widget]` in `ShortcodeExpanderService` + public `pg-widget-`* CSS |
| **93t-b** | Admin `/platform/widgets` — visual gallery, field form, live preview, copy markdown           |
| **93t-c** | Markdown editor insert (same gallery)                                                         |
| **93t-d** | Later: more types, WYSIWYG, outline palette                                                   |
| **93t-e** | Custom widgets in `/platform/widgets` (HTML `{{fields}}` + CodePolicy, same expand path)      |


Do **not** vendor CoreUI/Konrix. Existing landing shortcodes (`stats-row`, `cta-banner`, …) stay; widgets are a second, visual insert path.

---



## Out of scope

- Vendoring Falcon / Aurora source
- Public theme restyle
- LMS, e-commerce, kanban, social, chat app
- Isolated origin widgets (cancelled)
- Inventing metrics the analytics API does not expose
- IMAP for third-party domains or anonymous access

---



## Definition of Done (iteration)

Wave 1–2: daily admin (shell, dashboard, analytics, lists) looks Falcon-like on **our** data.  
Wave 3–5: teams, richer user profile, events, time log, support tickets, domain mail — with the mail rules above — gate green.  
Public site identity unchanged. No ThemeWagon files in git.