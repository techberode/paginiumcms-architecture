# Content Editor Regression Audit (2026-08-15)

Full trace of article save / publish / bulk-publish breakage introduced between **v2.1.0-beta.47** (It.81 editorial workflow) and **v2.1.0-beta.52**.

## Executive summary

The editor broke in **beta.49** when locale v2 read/write repair landed. Subsequent hotfixes (beta.50–52) addressed symptoms (persist-on-read, deploy script, index cleanup) but the **core write-path bug** remained:

> **`LocalizedContentWriter::syncFlatFieldsFromDefaultLocale()` unconditionally overwrote flat `title`, `body`, `status`, and SEO from the default-locale slice on every save** — clobbering SSOT when slices were empty, desynced, or when saving a non-default locale tab.

This explains failures on **both dev and prod** regardless of Docker recreate.

## Timeline

| Release | Change | Impact |
|---------|--------|--------|
| **beta.47** | It.81 editorial (bulk, saved views, calendar) | Baseline — content editor worked |
| **beta.49** | `LocalizedContentWriter`, hydrate on read, slug repair, permissions | **Regression start** — empty title/slug, list broken |
| **beta.50** | Conservative hydrate; metadata sanitizer; auto-save on read | Fixed read clobber; **introduced persist-on-read** side effects |
| **beta.51** | Removed persist-on-read; bulk `localeStatus`; index `removeByPath` | Partial fix; sync-on-write still broken |
| **beta.52** | Deploy script health retry; `AppVersion` fallback | Ops only — editor logic unchanged |
| **beta.53** (this fix) | Conservative `syncFlatFieldsFromDefaultLocale`; OTP/index/bulk/FE fixes | Target restore |

## Confirmed root causes

### RC-1 — Destructive flat-field sync on write (CRITICAL)

**File:** `backend/app/Core/Content/LocalizedContentWriter.php` — `syncFlatFieldsFromDefaultLocale`

**Before fix:** Every `applyLocalePayload()` call replaced flat title/body/SEO with default-locale slice values, even when empty.

**Symptoms:**
- Save article → title/body/SEO wiped
- Published on SK tab → flat status stays draft if slice desynced
- List shows empty title / untitled

**Fix (beta.53):** Conservative sync — only overwrite flat fields when slice has data or flat is empty; pass `$writtenLocale` so non-default locale writes never clobber flat SSOT.

---

### RC-2 — Persist-on-read bumped revision (beta.50, fixed beta.51)

**File:** `backend/app/Core/FlatFile/Services/ContentRepository.php`

`findByPath()` called `save()` after metadata repair → **409 Conflict** on next editor PUT.

**Status:** Fixed in beta.51 (in-memory repair only).

---

### RC-3 — Bulk publish skipped localeStatus (fixed beta.51)

**File:** `ContentController::bulkUpdateContentStatus`

Only set flat `status`; schema v2 list filters use `localeStatus` → items vanished from published filter.

**Status:** Fixed via `applyBulkStatus()` in beta.51; extended in beta.53 with flat-field hydrate.

---

### RC-4 — OTP publish workflow desync

**Files:**
- `ContentController.php` — OTP branch saved `localeStatus=published` but flat `status=draft`
- `OtpWorkflowService.php` — verify used `setStatus()` only, not `localeStatus`
- `MarkdownEditor.tsx` — no `baseRevision` update on 202; no reload after verify

**Symptoms:** Save as published → Koncept; second save → 409; OTP verify → list still draft.

**Fix (beta.53):** `applyLocaleStatus(draft)` on OTP pending; `applyBulkStatus` on verify; FE revision + reload.

---

### RC-5 — Index orphan rows on slug repair

**File:** `ContentIndexService::upsertFromContent`

Dedup keyed only by `slug+type`; empty-slug rows orphaned when slug repaired.

**Fix (beta.53):** Also dedup by `path+type`.

---

### RC-6 — Frontend locale tab / hydrate gaps

**Files:** `contentEditorLocale.ts`, `MarkdownEditor.tsx`

- `resolveInitialEditorLocale` preferred admin UI locale over content → blank editor when EN tab empty but SK has body
- `scheduledAt` / `tags` not hydrated into default locale state → save overwrote with empty values

**Fix (beta.53):** Content-aware initial tab; hydrate global fields into default locale state.

---

## What was NOT the root cause

| Suspected | Verdict |
|-----------|---------|
| Docker PHP container recreate | **Not required** — bind-mount serves code from git checkout |
| Missing beta.51 deploy | Can worsen symptoms but dev also broken → logic bug |
| `accessControl` permissions (ISS-150) | Fixed beta.49; causes 403 not data loss |
| Lock heartbeat 403 | Secondary CSRF/session issue |

## Data repair on production

After deploying beta.53:

```bash
cd /var/www/paginiumcms.com
php backend/bin/console content:diagnose --fix
```

Manual checks:
- Remove `content/blog/.json` if present (empty slug orphan)
- Remove `"slug": ""` rows from `data/index/content.json`
- Open each affected article → Save once (persists repaired v2 slices)

## Verification checklist

- [ ] Create article SK locale, title + body, status **Published** → list shows title + published badge
- [ ] Edit existing beta38 article → body/SEO persist after save + reload
- [ ] Bulk publish 2+ articles → remain in list with correct status
- [ ] OTP publish (if enabled): save → OTP → verify → published in list, no 409 on next edit
- [ ] `PUT /api/articles/{slug}` with `baseRevision` — no 409 after list refresh

## Related issues

- [ISS-149](ISSUES.md#iss-149) — empty title/slug (beta.49)
- [ISS-151](ISSUES.md#iss-151) — persist-on-read / bulk localeStatus (beta.51)
- [ISS-152](ISSUES.md#iss-152) — deploy false failures (beta.52)
- **ISS-153** — syncFlatFields write-path clobber (beta.53)
