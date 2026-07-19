# Iteration 41 — Email OTP workflows

**Status:** ✅ Complete  
**Priority:** 🟡  
**Depends on:** It.6 (SMTP), It.47 (notification connectors)

## Scope

| Flow | Role | Status |
|------|------|--------|
| Registration | USER | ✅ |
| Comment approval | EDITOR+ | ✅ |
| Publish approval | EDITOR+ | ✅ |

## Backend

- Settings group `workflows`: `registrationOtpEnabled`, `commentApprovalOtpEnabled`, `publishApprovalOtpEnabled`, `otpTtlMinutes`, `otpMaxAttempts`
- `OtpChallengeStore` — flat-file `data/otp-challenges.json` with flock
- `OtpWorkflowService` — registration + editor flows via `NotificationService` (email channel)
- Auth routes (registration):
  - `POST /api/auth/register` → `202` + `requires_otp` when enabled
  - `POST /api/auth/register/verify-otp`
  - `POST /api/auth/register/resend-otp`
- Admin routes (editor OTP):
  - `POST /api/admin/workflows/otp/verify`
  - `POST /api/admin/workflows/otp/resend`
- Comment approve / content publish return `202` + `requires_otp` when respective workflow toggle is on
- Bulk comment approve **does not** require OTP (documented limitation)
- Public settings: `workflows.registrationOtpEnabled`, `general.allowRegistration`
- In `APP_ENV=testing|development`, `debug_code` returned when SMTP/email adapter unavailable

## Frontend

- `RegisterModal` — registration OTP step
- `OtpConfirmModal` — reusable editor confirmation (comments + publish)
- `CommentsManager` — OTP on single approve
- `MarkdownEditor` — OTP when saving with status `published`
- `authApi` + `workflows.ts` API helpers

## Tests

- `OtpWorkflowServiceTest` — registration, comment approval, publish approval
- `AuthControllerTest::testRegisterWithOtpEnabled`
- `CommentsControllerTest::testApproveCommentWithOtpEnabled`

## Enable in admin

1. Settings → **Workflow OTP** → enable desired toggles
2. Settings → **Notification connectors** → enable email + configure SMTP (or use dev `debug_code`)

## Roles

- Registration OTP: public `/api/auth/*`
- Comment / publish OTP: `EDITOR`, `ADMIN`, `SUPER_ADMIN` (`/api/admin/workflows/*`, comments admin API)
