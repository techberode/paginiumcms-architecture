# Origin Panel — customer packaging exclusion

> Maintainer-only module (It.82). Same exclusion tier as the [Demo module](ITERATION_13.md).

## Rule

Customer install archives (ZIP/tarball for self-hosted downstream installs) **must not** include Origin Panel files. The module is enabled only on:

- maintainer development machines (`ORIGIN_PANEL=true`, `APP_ENV=development` allows LAN hosts),
- **paginiumcms.com** production (explicit allowlist).

## Paths to exclude from customer archive

```text
backend/app/Modules/Origin/
backend/app/Http/Routes/origin.php
backend/app/Http/Controllers/Origin/
backend/tests/Modules/Origin/
docs/manifest/project-catalog.json
frontend/src/components/backend/OriginPanelView.tsx
frontend/src/api/origin.ts
frontend/src/i18n/modules/origin/
```

## Runtime behaviour when excluded

- Route file absent → auto-discovery registers nothing; no `/api/admin/origin/*`.
- `ORIGIN_PANEL=false` (default) → even if files present, API returns **404** (fail-closed, no feature leakage).
- Public settings omit `origin.enabled`; admin nav item hidden (`originOnly` gate).

## Maintainer checkout

Full git checkout includes Origin module. Enable in **root** `.env`:

```env
ORIGIN_PANEL=true
ORIGIN_PANEL_ALLOWED_HOSTS=localhost,127.0.0.1,paginiumcms.com
```

See [ITERATION_82.md](ITERATION_82.md) for full spec.
