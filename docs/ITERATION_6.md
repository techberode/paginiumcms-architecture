# Iteration 6 – Notifications, Analytics & Auth UI

**Status:** Complete  
**Version:** 2.0.1

## Summary

Iteration 6 wires notification connectors, visit analytics, incident monitoring, toast settings, and complete auth UI flows across backend and frontend.

## Backend

### Settings schema (new groups)

- `smtp` – SMTP transport configuration
- `notifications` – toast enabled/position/duration/debug mode
- `connectors` – email, ntfy, Discord, Telegram, webhook
- `monitoring` – incident alerts, severity filter, traffic spike threshold

### Notification stack

- `SmtpTransport`, `NotificationFactory`, `IncidentNotifier`
- Channel adapters with settings-driven registration
- `NotificationController` – overview + test send
- `AuditTrailService::logSecurityEvent()` forwards to `IncidentNotifier`

### Analytics

- `Reporter` and `AnalyticsManager` implemented
- `AnalyticsMiddleware` tracks public page views
- `AnalyticsController` – overview + chart API

### Auth

- Password reset sends email when SMTP/email channel is enabled
- Demo reset token removed from production responses
- Failed login triggers `IncidentNotifier` when monitoring is enabled

### DI & middleware

- All services registered in `backend/app/Http/Config/services.php`
- `AnalyticsMiddleware` added globally in `bootstrap/app.php`
- `AuthController` receives settings, notifications, and incident notifier

## Frontend

- `NotificationsOverview` at `/notifications`
- Toast UI reads `GET /api/settings/public` → `notifications` group
- Auth routes: `/login`, `/register`, `/forgot-password`, `/reset-password`
- `ChangePasswordModal` in admin sidebar
- `password` field type in `SettingsView`

## Tests added

- `NotificationFactoryTest`
- `IncidentNotifierTest`
- `notificationSettings.test.ts` (Vitest)
- Updated `AuthControllerTest::testResetPassword` for testing env token fallback

## Documentation

- `docs/architecture/SETTINGS.md` rewritten in English
- `.cursorrules` updated: documentation and commit messages in English

## Next (Iteration 7+)

- Media manager FE, Developer Mode unlock UI, RSS/sitemap feeds, plugin system

---

## Rýchly štart (SK) – konfigurácia na serveri

It.6 je **implementované v kóde** (verzia 2.0.1+). Na tvojom hoste (`:8081`) treba len nastaviť a overiť:

### 1. SMTP (reset hesla, e-mailové alerty)

Admin → **Nastavenia** → skupina **SMTP**:
- `enabled` = zap
- `host`, `port`, `encryption` (tls/ssl)
- `username`, `password`
- `fromEmail`, `fromName`

### 2. Konektory (voliteľné)

Admin → **Nastavenia** → **Notification connectors**:
- Zapni `emailEnabled` (vyžaduje SMTP)
- Alebo ntfy / Discord / Telegram / webhook podľa potreby

### 3. Monitoring (incident alerty)

Skupina **Monitoring**:
- `alertsEnabled` = zap
- `alertEmail` = fallback adresa (ak konektor zlyhá)

### 4. Toast v admin UI

Skupina **Toast notifications**:
- pozícia (`top-right`, …), trvanie, debug mód

### 5. Overenie

1. `/notifications` → **Test** pri zapnutom konektore
2. `/forgot-password` → skontroluj doručenie mailu
3. Zapni toast debug → akcia v admin by mala logovať do konzoly

**Súvisiace:** [SETTINGS.md](architecture/SETTINGS.md) · incidenty → [ISSUES.md](ISSUES.md)
