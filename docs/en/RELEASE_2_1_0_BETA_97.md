# Release `v2.1.0-beta.97` — Experience Phase C + B3

> **Date:** 2026-10-06  
> **Tag:** `v2.1.0-beta.97`  
> **Previous:** [`v2.1.0-beta.96`](../../CHANGELOG.md#release-2-1-0-beta-96)

---

## One-line summary

Ships **Experience Phase C** (three reference landing seeds + Outline starter packs + demo links) and **Phase B3** (reading progress bar, View Transitions on public navigation, `.pg-glass` CSS).

---

## Operator notes

1. Rebuild frontend after deploy.
2. **Settings → Layout:** `showReadingProgress`, `viewTransitionsEnabled` (both default on).
3. Optional: import seeds from `backend/resources/content-seeds/reference-*.en.md`.
4. Demo home links to `/reference-agency`, `/reference-saas`, `/reference-local`.

---

## Deploy

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.97 ./scripts/deploy-instance-update.sh
```

Confirm health version → `2.1.0-beta.97`.

---

## Smoke

- [ ] Article `/blog/…` — top reading progress bar while scrolling.
- [ ] Click primary nav — subtle view transition (Chromium/Safari 18+).
- [ ] Outline → **Agency / SaaS / Local** starter inserts blocks.
- [ ] `./scripts/iteration-gate.sh` green.
