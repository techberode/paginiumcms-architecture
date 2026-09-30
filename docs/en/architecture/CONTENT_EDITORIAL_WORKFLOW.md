# Content editorial workflow (review, links, planner)

Iteration goal: editorial teams can **gate publication**, **track articles in the project planner**, and **validate internal links** before publish — without breaking solo sites (single editor, no teams).

## 1. YouTube / Vimeo embed caption

| Piece | Behaviour |
|-------|-----------|
| Storage | Optional `caption:` and `captionAlign:` (`left` \| `center` \| `right`, default **left**) on `:::embed` blocks |
| Render | Server wraps iframe in `<figure class="paginium-figure paginium-figure--embed">` + `<figcaption>` when caption is non-empty |
| Editor | Embed insert modal: optional caption field + alignment |

## 2. Editorial review statuses

| Status | Meaning | Public site |
|--------|---------|-------------|
| `pending_review` | Author submitted for team-lead review | Hidden |
| `reviewed` | Lead approved content; author/lead may publish | Hidden |

Activation rules:

- Settings → Content → **Editorial review workflow** (`content.editorialReviewEnabled`, default off).
- Workflow is **inactive** when no team exists in `data/teams/` (solo site).
- Team **leaders** (`teamLeaderUserIds`) may publish directly and mark items `reviewed`.
- Non-leaders requesting `published` are downgraded to `pending_review` until `reviewed`.

Planner integration: on first transition to `pending_review`, enqueue a plan item (default plan or auto-created **Content publication** plan) with `linkedContent` `{ type, slug }`.

Notifications: when content transitions to `pending_review`, team leaders can be alerted via **Monitoring → content publish** connector (`content.editorialReviewNotifyLeaders`, requires `monitoring.contentPublishNotifyEnabled`). Hook context includes `previousStatus` so re-saves while already pending do not spam.

**Admin desk:** team leaders see all `pending_review` pages/articles in `/api/auth/me/desk` (`kind: content_review`, links to `/pages/{slug}` or `/articles/{slug}`). Toggle: `content.editorialReviewDeskEnabled` (default on). Implemented by `ContentEditorialDeskService` + `DeskInboxService` merge (no separate flat-file queue). Regression: `DeskInboxServiceTest::testDeskInboxMergesEditorialReviewForTeamLeader`.

## 3. Internal link check (admin)

Scope: **same-site** targets only. The server never performs outbound HTTP (SSRF-safe). It resolves slugs against flat-file content.

### Supported link shapes

| Form | Resolved as (when checking from an **article**) |
|------|--------------------------------------------------|
| `/blog/{slug}`, `/articles/{slug}` | Article `{slug}` |
| `/pages/{slug}`, `/{slug}` | Page `{slug}` (from pages: page; from articles: article for single-segment paths) |
| `./{slug}`, `../{slug}`, bare `{slug}` | Article `{slug}` in article editor; page slug in page editor |
| `https://…/blog/{slug}` (and same path on any host) | Article `{slug}` (path only; no HTTP fetch) |
| Optional locale prefix | `/sk/blog/…` etc. |

Body formats: **markdown**, **html** (`href`), **tiptap_json** (link marks). The editor sends the same normalized payload as save (`contentFormat` + body).

### Validation rules

| Outcome | Reason code |
|---------|-------------|
| Target slug not found for resolved type | `missing_content` |
| Target exists but status ≠ `published` | `not_published` |
| Self-link (same type + slug as item under edit) | Ignored |

| Step | UI |
|------|-----|
| Editor slug card | **Check links & slug** → `POST /api/admin/content/link-check` |
| Result | Issue list + CodeMirror line highlight (`.cm-broken-link-line`); live preview marks `<a class="pg-link-broken">` after link check |
| Optional gate | `content.editorialLinkCheckRequired` — before save as `published` or `pending_review`, auto-runs check and blocks on any issue |

Broken-link fix loop: author edits → re-run check → when clean, proceed to save.

### API

```json
POST /api/admin/content/link-check
{ "type": "page|article", "slug": "current-slug", "body": "…", "contentFormat": "markdown|html|tiptap_json" }
→ { "ok": true|false, "issues": [{ "url", "line", "reason" }] }
```

Auth: `content:edit` (same as content meta routes).

## 4. Settings & permissions

| Key | Notes |
|-----|-------|
| `content.editorialReviewEnabled` | Master switch; ignored when zero teams |
| `content.editorialReviewStatusesEnabled` | Expose `pending_review` / `reviewed` in status UI |
| `content.editorialReviewNotifyLeaders` | Pending-review alerts via content-publish connector |
| `content.editorialReviewDeskEnabled` | Pending-review items in desk queue for team leaders |
| `content.editorialReviewPlanId` | Optional planner id for review queue items (dropdown in Settings → Content) |
| `content.editorialLinkCheckRequired` | Mandatory link check before publish / pending_review save |

Permissions: reuse `content:edit`. **Mark as reviewed** (toolbar) is shown to **team leaders** when status is `pending_review`; sets status `reviewed`.

## 5. Backend services

| Service | Role |
|---------|------|
| `ContentEditorialReviewService` | Workflow active, status gate, planner enqueue |
| `ContentEditorialReviewNotificationService` | Leader notify on transition to `pending_review` |
| `ContentEditorialDeskService` | Pending-review rows for team-leader desk |
| `ContentInternalLinkCheckService` | Link extraction + slug resolution |
| `ContentEditorialReviewHookRegistrar` | Registers `CONTENT_AFTER_SAVE` hooks |

## 6. Remaining / future

- Dedicated `content:review` permission (optional).

Related: [Project planner](../user/PROJECT_PLANNER.md) (if exists), [Content editor](../user/CONTENT_EDITOR.md), teams `teamLeaderUserIds`.
