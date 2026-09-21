# Iteration 93 — Admin chrome + daily apps

> **Status:** ✅ complete (Wave 1–5 shipped in tree, including **93l-2** canned replies / SLA notes)
> **Priority:** 🟡 **P1 operator UX** — after [It.58f](ITERATION_58f.md) publishing blocks  
> **Wave:** Admin chrome, then daily **apps** on our data  
> **Depends on:** `ResponsiveLayout`, dashboard/analytics APIs, users/roles (It.84), project planner (It.87), comments/messages, SMTP settings  
> **Does not replace:** public Theme Studio look, Origin Panel, SQL  

Former isolated-origin It.93 was **cancelled** (never implemented). Archive only: [architecture/ISOLATED_ORIGIN.md](architecture/ISOLATED_ORIGIN.md).

## Why this iteration

The kitchen pass (admin) should match a modern SaaS console **and** grow the apps operators actually run a company site with: analytics, teams, support, domain mail, events, project time.

Restaurant: we still cook **our** food (pageviews, comments, project plans) — we do not plate demo metrics.

---

## What this wave maps to in Paginium

We do **not** clone shops, courses, or social apps.


| Area                                                            | Paginium (this iteration)                                                                     | Already in Core                                           |
| --------------------------------------------------------------- | --------------------------------------------------------------------------------------------- | --------------------------------------------------------- |
| Dashboard (KPI row, widget grid, storage bar, project progress) | Restyle `DashboardView` with **our** stats + planner progress                                 | Dashboard overview, disk, It.87 widget                    |
| Dashboard → Analytics                                           | Restyle `/analytics` (same tabs/APIs: overview, pages, sources, devices, geo, bots, 404)      | It.6 + later analytics                                    |
| Management                                                      | **Teams**: group CMS users; Support team is a team type                                       | Users + custom roles (`/security/roles`)                  |
| Support desk                                                    | Tickets + agents (CMS users on the Support team). Foundation first, then canned replies / SLA | Comments + contact messages stay; tickets are a new store |
| Email                                                           | **Domain mail inbox** (IMAP) for `@site-domain` mailboxes only, session required              | SMTP send exists; **no IMAP yet**                         |
| Events                                                          | Public/company **events** (list + create). Not the editorial calendar                         | Editorial calendar = content go-live (It.81d)             |
| Calendar                                                        | Restyle editorial calendar; events have their own calendar view                               | Editorial calendar                                        |
| Chat                                                            | **Out.** Use comments + messages                                                              | Inbox                                                     |
| User account                                                    | Extended **user profile** tabs (avatar, bio, job, timezone, notify prefs, security link)      | Users: name/email/role/2FA/avatar/bio                     |
| Time tracker                                                    | Time entries on a **project plan item** or **event** (start/stop + log)                       | Project planner items, no timer                           |
| Modules (Forms, Tables, Charts, Widgets)                        | Shared admin kit used by all of the above                                                     | Ad-hoc Tailwind per view                                  |
| Maps / E-commerce / LMS / Social                                | **Out** unless a later spec says otherwise                                                    | Geo is already an analytics tab                           |


---



## Wave 1 — Chrome (look of the kitchen)

Same as before: canvas, cards, light sidebar, **opaque** topbar (flex sibling of the scroll pane — not sticky over content), search → command palette, list toolbars, dark tokens. Primary chips use `admin-chip` / `admin-chip-on` so label color stays readable in both modes. Operators pick sidebar/topbar color (8 swatches), optional gradient, and side vs top dropdown nav in Settings → Admin UI — not the public theme.

Shared **module kit**: `AdminKpiCard`, `AdminWidgetCard`, `AdminDataTable`, `AdminToolbar`, chart wrappers around existing analytics charts.

---



## Wave 2 — Existing apps, admin layout



### Dashboard (full grid)

Keep our numbers. Layout:

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



### Extended user account

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

**93o-2 (social verify)** — Account → Public card: each social row has **Verify link**. `POST /api/auth/me/social/verify` (session + CSRF, 20 req/min) probes the URL via `SocialAccountLinkProbe` + `OutboundUrlGuard`. Successful rows store `verifiedAt`. Saving **Publish social accounts** fails closed until every row is verified; the public staff card lists only verified links. Changing platform/URL clears the stamp.

**93o-3 (staff cards + Messages chat)** — Editors insert `[staff-card user="…"]` or `[staff-team type="support"|id="team_…"]`. Placeholders expand to islands; the public SPA hydrates published cards (phone/job/email/socials + online badge). Visitor chat is **not** a new socket SSOT: `POST /api/public/staff/{id}/message` writes a `ContactMessage` with `channel=staff-chat` into Messages. Effective chat = listed public card AND user `chatEnabled` AND (no team membership OR at least one team with `chatEnabled`). Support-team members get a floating online/offline bubble (`GET /api/auth/me/chat-status`, `POST /api/auth/me/presence`, 90s last-seen window).

**93o-4 (subject routing + Messenger desk)** — Settings → Contact: map each form subject to teams/people (`GET`/`PUT /api/admin/messages/routing`). A matching inbound message is assigned to that desk. The first staff reply **claims** it (`handleStatus=in_progress`) so teammates see “In progress” instead of doubling up. Follow-up visitor posts with the same email+subject (or email+staff card) append to the thread. After claim, email alerts go only to the claimant. Admin Messages is a Messenger-style thread, not a public live-chat bubble. Non-admin assignees can open `/messages` (filtered to their desk); delete/bulk/routing stay admin.

**93o-5 (on-page comment desk)** — Editorial/Support team members (and editors) reply under a public article comment without opening admin. `GET /api/auth/me/desk` feeds the floating bubble (public site + admin): badge count, queue, **Open on the page** (`/comments#comment-{id}` or `/messages#message-{id}`) or reply in the bubble. The same comment reply is stored as an approved child and appears under the article. Comments use the same flock claim as Messages. The bubble portals to `document.body` at `z-[300]` with an opaque card. Account → Public card toggles it and sets placement (edge pad or drag). With the bubble off, a pulsing desk count sits next to the user block (under the sidebar header) and in the admin top bar; click opens the queue and jumps to that comment or message so you can chat in the item. The in-item composer is the reply surface while the bubble is off. **Always on top** uses Document Picture-in-Picture (Chrome/Edge); other browsers keep the in-page overlay only.

**93o-7 (external team + registration types)** — Teams get an **External** type with its own Discord-style room at `/team-chat` (`data/team-chat/{teamId}/`, membership or ADMIN). Messages are Markdown or fenced code; files use the `documents` upload profile and are **download-only**. Public registration, when types exist, can still show a picker linked to Roles. Fail-closed: ADMIN/SUPER_ADMIN cannot be chosen on the public form.

**93o-8 (external as a named choice + invite link)** — External is only a **type on create**, named by purpose. Cards show a chosen hex color and member avatars. Adding a member does **not** require public registration: `POST /api/admin/registration-invites` issues a one-time `/register?invite=` link (token stored as SHA-256). A visitor can request that path on the contact form (`registrationRequest`). The account is created inactive with role USER; the superadmin confirms it under Users, sets role and team membership **manually**, then the welcome mail is sent. `RegistrationService::approve` no longer auto-attaches a team.

**93o-6 (desk reply mail)** — Outbound staff replies use a **site-domain From**: operator mailbox first (`deskMailEnabled` + account e-mail on the CMS domain), else the team central mailbox (`replyMailEnabled` + `replyMail`, independent of generic site/company SMTP From). Public providers are rejected (`SiteMailboxGuard`). The visitor **To** is the e-mail from the contact form or comment. That address is required on contact, staff-chat, and comments. `VisitorEmailGuard` checks RFC shape and disposable hosts; it does **not** probe whether the mailbox exists (no MX/VRFY). Guest comments stay guests — “registered” here means a lasting mailbox, not a CMS account.

**93o follow-ups (same iteration, no new number)**

- **Users tabs** — `/users` uses `AdminTabs`: Users / New user / One-time registration (`RegistrationInvitesPanel` + `RegistrationOptionsPanel`).
- **Comment approve** — When comment settings require approval, the item and bulk bar expose **Approve** (`POST /api/admin/comments/bulk-workflow` `approve`). Same store as processed; the old “Vybavené” label is not the approve action.
- **In-page chat vs Desk** — Opening a hash (`/comments#comment-…`, `/messages#message-…`) expands that thread. The in-item composer is visible only while `deskBubbleEnabled` is false. Comment replies still publish as approved children under the article.
- **Desk queue labels** — Beacon and Stôl mark each item as message or comment, show message priority, and keep “your desk / in progress”.
- **Shared admin tabs** — Newsletter: Settings / Email sending / Recipients. Backups: Backup management / Backup list (sortable by name, created, size, scope, type). Firewall incidents / bans / whitelist use the same `AdminTabs` chrome as Settings → System.
- **Contact phone + SMTP Reply** — Public contact requires country prefix + national number as E.164 (`+421909554887`) with a format hint. Messages composer always sends through site SMTP (To = form e-mail; From = configured SMTP mailbox). **Call** uses `tel:` when a phone is present.
- **Article discussion + rating** — Public comments are a discussion. Settings → Comments can require a 1–5 star rating with the comment (`ratingEnabled`); each article can inherit/override. `GET /api/auth/me/desk` returns an empty queue instead of HTTP 500 when the inbox fails.

### Events planner

New SSOT `data/events/{id}.json` (`site-event@1`): title, slug, startsAt, endsAt, location, body (Markdown), status (`draftpublished`), optional `projectPlanId`.

- Admin: list (calendar + table) + create/edit.
- Public listing is **optional later** (not required to close 93n). Editorial calendar remains content scheduling.



### Time tracker (our projects)

Entries in `data/time-entries/{id}.json`: `userId`, `target` (`planItem`  `event`), ids, `startedAt`, `endedAt`, `seconds`, `note`.

- UI: running timer, today/week table, filter by plan.
- Permission `time-entry:manage` (own entries); ADMIN can see team totals.
- Computed only — do not write progress % into the plan document.

---



## Wave 4 — Support desk (foundation → expand)

**93l-1 foundation**

- Tickets: `data/support-tickets/{id}.json` — subject, body, status (`openpendingclosed`), `assigneeUserId` (must be on Support team), requester email/name, timestamps.
- Admin list + detail (status chips, assignee).
- Add/remove Support team members = Teams UI with `type=support` (user, role, active, avatar — not a second HRIS).

**93l-2 (same iteration — no new number)**

- ✅ Internal notes, canned replies, tags on the card, SLA `dueAt` UI. Store: `data/support-canned.json` (`support-canned@1`) + `internalNotes[]` on the ticket. Notes are staff-only and append-only (`POST …/tickets/{id}/notes`).

Comments and contact **messages** stay. A ticket may *link* a message id; do not merge stores.

---



## Wave 5 — Domain mail inbox (strict)

A **logged-in** mail viewer for mailboxes **under the site’s domain only**.

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


v1: list folders + message list + read pane. Send/reply reuse the existing SMTP group; From is the operator mailbox.

**Not v1:** fetching the whole internet, shared JWT to Roundcube, storing `.eml` in git, wildcards `*@`*.

---



## Security / quality (all new stores)

- Mutating `/api/*` → `AuthMiddleware` + `PermissionMiddleware`.
- Path traversal, Zip-Slip N/A unless attachments: attachments only via DAM allow-list.
- No third-party admin template JS or HTML in the repo.
- SK/EN i18n for every new screen.
- Gate green per slice.

---



## Slices


| ID      | Work                                                                                                      | Status                                                         |     |
| ------- | --------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------- | --- |
| **93a** | Admin tokens + shell (canvas, cards, light sidebar, topbar)                                               | ✅                                                              |     |
| **93q** | Shared kit: KPI card, widget card, offer card, toolbar, data table (Modules: forms/tables/charts/widgets) | ✅                                                              |     |
| **93f** | Persisted nav collapse, topbar search → palette                                                           | ✅                                                              |     |
| **93b** | Dashboard grid (our KPIs, planner, storage, activity)                                                     | ✅                                                              |     |
| **93j** | Analytics layout (existing `/analytics` APIs)                                                             | ✅                                                              |     |
| **93c** | Workspace + inbox list chrome                                                                             | ✅                                                              |     |
| **93k** | Teams (management) + Support team type                                                                    | ✅                                                              |     |
| **93o** | Extended user account (profile tabs)                                                                      | ✅ + chrome entry, public card, share bar, **93o-2–8** (verify, staff cards, routing/Messenger, comment desk, reply mail, external team + invite, Users/desk/admin-tab follow-ups) |     |
| **93n** | Events planner (list + create)                                                                            | ✅                                                              |     |
| **93p** | Time tracker on plan item / event / page                                                                  | ✅                                                              |     |
| **93u** | Catalog side menu (levels, hover preview, left/right, sticky; no clash with header)                       | ✅                                                              |     |
| **93v** | Coming-soon countdown linked to a page/article and published as part of that page                         | ✅                                                              |     |
| **93w** | Viewport audit: header/side/hamburger never overlap; grids adapt with side column                         | ✅                                                              |     |
| **93l** | Support desk foundation (tickets + agents)                                                                | ✅ Kanban board + settings (our tickets)                       |
| **93l-2** | Internal notes, canned replies, tags, SLA dueAt                                                          | ✅ staff notes + `data/support-canned.json` + dueAt UI          |
| **93m** | Domain IMAP inbox (rules above)                                                                           | ✅ folders / tags / Junk / SMTP send+reply; two-pane inbox chrome; UTF-8 HTML; own `@site` mailbox only |
| **93d** | Settings / navigation form chrome                                                                         | ✅                                                              |     |
| **93e** | Content editor shell chrome                                                                               | ✅                                                              |     |
| **93g** | Dark-mode token parity + admin light/dark toggle                                                          | ✅                                                              |     |
| **93r** | Admin chrome colors (8) + gradient + top dropdown nav                                                     | ✅                                                              |     |
| **93s** | Settings Apply preview + floating Apply/Save dock                                                         | ✅                                                              |     |
| **93t** | Public **Widgets** (visual catalog + markdown insert)                                                     | ✅ a–c (d later)                                                |     |
| **93h** | SK/EN leftovers, gate                                                                                     | ✅                                                              |     |


Order: **Wave 1** `a → q → f → g` · **Wave 2** `b → j → c → d → e` · **Wave 3** `k → o → n → p` · **Wave 4–5** `l → m` · `h` with each wave.

`93l` foundation is the Kanban board (columns/labels + tickets assigned to the Support team). **93l-2** (internal notes, canned replies, SLA dueAt UI) is shipped in the same iteration; no new iteration number.

**93m-2** — `MailHtmlSanitizer` now decodes libxml numeric/named entities to UTF-8 after `saveHTML`, while keeping `&lt;` / `&amp;` escaped so markup stays inert.

**93m-3 (privacy)** — Remote `http(s)` images in HTML mail are **blocked by default** (tracking-pixel protection). Operators opt in per message via **Load remote images** (`GET …/messages/{uid}?remoteImages=1`). See [ISS-171](../ISSUES.md#iss-171).

**93m-4 (signatures + mobile UX)** — Per-mailbox HTML **signatures** (six templates, profile + company merge, overrides in `data/mail-client/`). Sidebar editor at `/mail`; SMTP send appends when enabled (`GET`/`PUT /api/admin/mail/signature`, `POST …/signature/import-profile`). **Trusted sender** remote images (browser) and **blocklist** + **spam autoclean** ship with the inbox client. **Mobile:** folder drawer (hamburger under admin topbar), list-only home, Gmail-style full-pane read with **Back to list**. **Settings → Email / IMAP:** `imap.listLimit` (10–500) for fetch window.

**93m-5 (labels + trash + compose polish)** — Shipped **`v2.1.0-beta.79`**. Custom **labels** (name/color, browser catalog per mailbox) with edit/delete and remove-from-message (single + bulk). **Empty local trash** (`POST /api/admin/mail/local-trash/empty`) permanently dismisses hidden messages in this client. Compose **draft autosave**, multi-recipient To, manual refresh, expandable envelope details, optional **`imap.appendSentOnSend`**. Outbound MIME builder + inline signature avatars (CID). List toolbar **clear search** (×).

**Admin SPA paths** — sidebar hrefs are first-segment (`/mail`, `/kanban`, `/teams`, `/security-audit`). Legacy `/platform/*` and `/security/{audit,roles}` redirect. Contract: [ADMIN_DEEP_LINKS.md](architecture/ADMIN_DEEP_LINKS.md).

### 93t — Public widgets (stepped)

Visual catalog of reusable blocks. No third-party UI kits in git.

Restaurant: Widgets are plated dishes on the public table. The waiter (markdown `[widget type="kpi" … /]`) carries a ticket; the chef (PHP `WidgetCatalog`) plates HTML; the admin **Widgets** tab is the pass window with a visual picker (not JSON like Shortcodes).


| Slice     | Scope                                                                                         |
| --------- | --------------------------------------------------------------------------------------------- |
| **93t-a** | Built-in catalog + expand `[widget]` in `ShortcodeExpanderService` + public `pg-widget-`* CSS |
| **93t-b** | Admin `/widgets` — visual gallery, field form, live preview, copy markdown           |
| **93t-c** | Markdown editor insert (same gallery)                                                         |
| **93t-d** | Later: more types, WYSIWYG, outline palette                                                   |
| **93t-e** | Custom widgets in `/widgets` (HTML `{{fields}}` + CodePolicy, same expand path)      |


Do **not** vendor third-party widget kits. Existing landing shortcodes (`stats-row`, `cta-banner`, …) stay; widgets are a second, visual insert path.

---



## Out of scope

- Vendoring third-party admin templates
- Public theme restyle
- LMS, e-commerce, social, chat app (support Kanban is **in** — our tickets)
- Isolated origin widgets (cancelled)
- Inventing metrics the analytics API does not expose
- IMAP for third-party domains or anonymous access

---



## Definition of Done (iteration)

Wave 1–2: daily admin (shell, dashboard, analytics, lists) uses the admin kit on **our** data.  
Wave 3–5: teams, richer user profile, events, time log, support Kanban, domain mail — with the mail rules above — gate green.  
Public site identity unchanged.