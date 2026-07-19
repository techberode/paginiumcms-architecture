# Iteration 41 — Email OTP workflows

**Status:** ✅ Registration OTP (phase 1)  
**Priority:** 🟡  
**Depends on:** It.6 (SMTP), It.47 (notification connectors)

## Scope

| Flow | Role | Status |
|------|------|--------|
| Registration | USER | ✅ Implemented |
| Comment approval | EDITOR | ⏳ Phase 2 |
| Publish approval | EDITOR | ⏳ Phase 2 |

## Backend

- Settings group `workflows`: `registrationOtpEnabled`, `commentApprovalOtpEnabled`, `publishApprovalOtpEnabled`, `otpTtlMinutes`, `otpMaxAttempts`
- `OtpChallengeStore` — flat-file `data/otp-challenges.json` with flock
- `OtpWorkflowService` — start / verify / resend registration OTP via `NotificationService` (email channel)
- Routes:
  - `POST /api/auth/register` → `202` + `requires_otp` when enabled
  - `POST /api/auth/register/verify-otp`
  - `POST /api/auth/register/resend-otp`
- Public settings expose `workflows.registrationOtpEnabled` and `general.allowRegistration`
- In `APP_ENV=testing|development`, `debug_code` returned when SMTP/email adapter unavailable

## Frontend

- `RegisterModal` — two-step flow (form → OTP verify + resend)
- `authApi`: `verifyRegisterOtp`, `resendRegisterOtp`
- Admin toggles via existing Settings schema (`workflows` group)

## Tests

- `OtpWorkflowServiceTest` — start + verify + invalid code
- `AuthControllerTest::testRegisterWithOtpEnabled` — full HTTP flow with `debug_code`

## Enable in admin

1. Settings → **Workflow OTP** → enable **OTP pri registrácii**
2. Settings → **Notification connectors** → enable email + configure SMTP (or use dev `debug_code`)
