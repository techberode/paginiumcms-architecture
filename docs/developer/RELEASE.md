# Release checklist — PaginiumCMS

> Posledná verzia: **2.0.29** · 2026-07-19  
> Tento súbor obsahuje **copy-paste** bloky pre GitHub Release. Samotný release/tag zatiaľ nevytváraj, kým nie je schválený deploy.

---

## 2.0.29 — pred release kontrola

```bash
# Backend
./vendor/bin/phpstan analyse backend --level=8
./vendor/bin/phpunit

# Frontend
cd frontend && npm run type-check && npm run lint && npm test && npm run build:prod
```

**LAN deploy (split nginx + PHP):**

```bash
# Na .26 (nginx SPA)
./scripts/deploy-frontend-lan.sh

# Na .20 (PHP) — pull + reštart kontajnera
# Skontroluj .env: SESSION_LIFETIME, SESSION_STRICT, TRUSTED_PROXIES
curl -s http://192.168.10.26:8081/api/health | jq .
```

**Po deployi:** vymazať cookies pre host, znova prihlásiť, otestovať uloženie článku + nastavení.

---

## GitHub Release — copy-paste (2.0.29)

**Title:**

```
2.0.29 — Session hardening, cache admin, auth fixes
```

**Tag:** `v2.0.29` · **Target:** `main`

**Body (skopíruj celé):**

```markdown
## Summary

Release **2.0.29** stabilizuje prihlásenie a session za nginx proxy (LAN split deploy), pridáva manuálne vymazanie cache v admin nastaveniach a opravuje kritický 500 na auth trasách. Súhrn incidentov: [docs/ISSUES.md](docs/ISSUES.md) (ISS-023–ISS-029).

## Added

- **Secure session layer** — `SecureSessionManager`, `ClientIpResolver`, singleton DI, `touchSession()` on auth requests
- **Admin cache panel** — Settings → Cache systému; `GET/POST /api/admin/cache` (`content` | `all` purge)
- **CLI** — `security:clear-lockouts` for login lockout reset
- **Demo login guard** — demo credentials only when `DEMO_MODE=true`
- **Auth UX** — session keepalive (4 min), `probeSession()` for expired vs network errors
- **Debug log** — PHPUnit (`APP_ENV=testing`) no longer writes to debug log

## Fixed

| ID | Issue |
|----|-------|
| ISS-024 | `AuthMiddleware` DI mismatch → HTTP 500 on protected routes |
| ISS-025 | Logout during edit/save (session + FE 401 redirect) |
| ISS-026 | `SESSION_USE_STRICT_MODE` vs `SESSION_STRICT` documentation |
| ISS-027 | Fake login 401 lines in debug log from PHPUnit |
| ISS-028 | `SettingsView.tsx` JSX — production build failure |
| ISS-029 | Login loop after brief dashboard access |
| ISS-023 | Flaky admin draft search PHPUnit test |

## Ops — recommended `.env` (PHP server behind nginx)

```env
SESSION_LIFETIME=28800
SESSION_STRICT=false
SESSION_USE_STRICT_MODE=true
TRUSTED_PROXIES=127.0.0.1,::1,192.168.10.26
```

Restart PHP after change. Clear browser cookies and re-login.

## Test plan

- [ ] `POST /api/auth/login` → 200 + `Set-Cookie: PHPSESSID`
- [ ] `GET /api/auth/me` → 200 after login
- [ ] Edit article/page → save → still authenticated (no redirect to `/login`)
- [ ] Settings → save monitoring group → 200 (not 500)
- [ ] Settings → Cache systému → purge content cache
- [ ] `npm run build:prod` succeeds
- [ ] `./vendor/bin/phpunit` green

## Full changelog

[CHANGELOG.md#2029--2026-07-19](CHANGELOG.md#2029--2026-07-19)
```

---

## Git commit message (optional, keď schváliš commit)

```
Release 2.0.29: session hardening, cache admin, auth incident fixes.

Fixes ISS-023–ISS-029 (AuthMiddleware 500, login loop, debug log noise,
SettingsView build). Adds SecureSessionManager, admin cache purge, clear-lockouts CLI.
```

---

## Predchádzajúce release

- **2.0.28** — It.12 Blueprint + It.13 Demo sandbox v2 — [CHANGELOG.md#2028--2026-07-19](../CHANGELOG.md#2028--2026-07-19)
