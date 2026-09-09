---
title: Project site planner
description: Plan launches, content milestones, and publication deadlines in the Full CMS
icon: material/clipboard-check
---

# Project site planner

> **Route:** Workspace → **Project planner** (`/platform/project-planner`)  
> **Permissions:** `project-plan:read` (view), `project-plan:manage` (create/edit items)  
> **Not Origin Panel.** Origin is maintainer-gated; the planner ships in the Full CMS.

The planner is **proactive**: you set milestones and due dates *before* pages or articles exist. The [editorial calendar](CONTENT_EDITOR.md) remains **reactive** — it shows content the CMS already knows (`scheduledAt` / `publishedAt`).

## 1. Create a plan

1. Open **Project planner**.
2. Click **New plan**.
3. Set a title (the ID is slugified for the filename `data/project-plans/{id}.json`).
4. Keep or edit the IANA timezone (variance uses this zone).
5. Save. Default phases are Discovery → Content → Launch.

One plan can be marked **Default**. Overall progress on the list page aggregates every plan.

## 2. Add items

Open a plan and **Add item**:

| Field | Notes |
|-------|--------|
| Content type chips | **Page** (+14d), **Article** (+7d), landing (+21d), media (+10d), newsletter (+7d), custom (no preset) |
| Title | Required for a single item; optional for a pack (uses the type label) |
| Milestone pack | Count 1–20 with one shared due date — e.g. 5 articles by the end of March |
| Phase | Optional grouping |
| Due date | Filled from the type template; you can still pick a calendar date |
| Status | `planned` → `in_progress` → `done` (or `skipped` / `blocked`) |

Progress % uses the same weights as Origin catalog cards: done = 100%, in progress = 50%, planned/blocked = 0%, skipped excluded.

## 3. Variance badges

| Badge | Meaning |
|-------|---------|
| On time | Done on the same calendar day as the due date |
| Early / Late | Done before / after the due day (plan timezone) |
| Overdue | Not done and the due instant has passed |
| Due soon | Not done and due within 7 days |

## 4. Settings

**Settings → Site → Project planner → Enable.** Default **on**. Turning it off returns API 404 and hides the nav item (stripped demos).

Link a page or article from the item row (or when adding a single item). Publishing that slug marks the item **done** automatically.

Dashboard KPIs (overdue / due soon) appear on **Dashboard** when you have `project-plan:read`.

## 5. Related

- Architecture SSOT: [PROJECT_PLANNER.md](../architecture/PROJECT_PLANNER.md)
- Spec: [ITERATION_87.md](../ITERATION_87.md)
- Permissions: [ACCESS_CONTROL.md](ACCESS_CONTROL.md)
