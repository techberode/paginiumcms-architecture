# Iteration 78 — unified upload security policy

> **Status:** ⏳ planned  
> **Priority:** 🟡 · security gate before new media types  
> **Wave:** Post-HE DAM & security (It.78–79)  
> **Depends on:** [It.24](ITERATION_24.md) DAM · [It.67](ITERATION_67.md) untrusted surfaces  
> **Blocks:** [It.79](ITERATION_79.md) DAM video

## Goal

Establish **one enforceable upload policy** for every surface that accepts user-controlled binaries or archives. Today `UploadSecurityValidator` and `MediaFormats` partially cover media; avatars, backup ZIPs, extension imports, stock import, and editor inline uploads use overlapping but inconsistent rules.

It.78 does **not** add new public file types. It makes existing and future upload paths share the same security contract so video (It.79) and any later DAM expansion cannot bypass gates.

---

## Upload surfaces (inventory)

| Surface | Endpoint / path | Current gate | It.78 target |
|---------|-------------------|--------------|--------------|
| Media Library | `POST /api/media/upload` | `UploadSecurityValidator` + `MediaFormats` | `UploadPolicyEngine` profile `media` |
| Editor inline image | same upload API | same | `media` profile |
| User avatar | avatar upload flow | partial filename rules | `avatar` profile |
| Stock import | `POST /api/media/stock-import` | outbound SSRF + import into media | `stock-import` profile + `OutboundUrlGuard` |
| Backup restore | backup upload/import | Zip-Slip + allow-list | `backup-archive` profile |
| Extension / theme ZIP | extensions import | `CodePolicyEngine` + Zip-Slip | `extension-archive` profile |
| Future video (It.79) | media upload | — | `media-video` profile (extends `media`) |

Every row must map to a **named profile**; no ad-hoc `move_uploaded_file` without policy.

---

## Contract

| Component | Responsibility |
|-----------|----------------|
| `UploadPolicyEngine` | resolve profile → run ordered checks → unified result/audit |
| `UploadPolicyProfile` | allow-list MIME/extensions, max bytes, magic-byte policy, quota hooks |
| `UploadSurfaceRegistry` | maps controller/service → profile id (grep-verifiable in gate) |
| `UploadMagicByteInspector` | shared sniffing for images, PDF, video containers (ftyp/webm), archives |
| `UploadFilenameGuard` | double extension, executable ext, null bytes, path traversal in name |
| `UploadQuotaGuard` | optional per-user / per-site daily bytes (flat-file counter) |
| `UploadAuditLogger` | sanitized audit event (no file content, no secrets) |

Existing `UploadSecurityValidator` becomes a thin adapter or is merged into the engine without weakening defaults.

---

## Policy rules (mandatory for all profiles)

1. **Allow-list only** — declared MIME, extension, and magic bytes must match; unknown = reject.
2. **Intersection semantics** — when settings define both `uploadSecurity.allowedMimeTypes` and domain settings (e.g. `media.allowedMimeTypes`), effective list = **intersection**, never union.
3. **Size caps** — profile max bytes; stricter of global and domain limit wins.
4. **Filename hardening** — block double extensions, executable extensions, leading dots, null bytes, `..`, absolute paths.
5. **No client-controlled storage path** — server derives object key; client supplies filename for display only.
6. **Active content** — SVG/HTML/XML in media stay `Content-Disposition: attachment` + CSP sandbox (existing rule).
7. **Archive uploads** — Zip-Slip check per entry; entry allow-list; max uncompressed ratio / bomb guard.
8. **Outbound fetch** (stock, future remote import) — `OutboundUrlGuard::assertAllowed()` before any fetch; redirect revalidation; size cap while streaming.
9. **Audit** — user id, profile, byte size, mime, outcome; values through `LogSanitizer`.
10. **Fail closed** — missing profile mapping = reject in production; tests must cover every surface.

---

## Settings

```yaml
uploadSecurity:
  # existing It.19b keys remain
  blockDoubleExtensions: true
  blockExecutables: true
  scanMagicBytes: true
  allowedMimeTypes: ""          # empty = defer to domain profile
  maxUploadBytes: 0             # 0 = defer to domain profile
  unifiedPolicyEnabled: true    # It.78 master switch; default true when shipped
  auditUploads: true
  dailyQuotaBytesPerUser: 0     # 0 = disabled
```

Domain groups keep their limits (`media.maxUploadSizeKb`, future `media.maxVideoUploadSizeKb`). It.78 documents precedence: **min(global, domain, profile)**.

---

## Security baseline (non-negotiable)

Aligns with workspace security rules and [CORE_HARDENING.md](architecture/CORE_HARDENING.md):

- Mutating upload routes: `AuthMiddleware` + appropriate permission (`media:write`, `backup:write`, …).
- CSRF on session-based uploads.
- No plaintext secrets in upload metadata or logs.
- Regression pack entry in `scripts/run-all-tests.zsh` for every profile.
- PHPStan L8 on new services; PHPUnit for bypass attempts (double ext, mime mismatch, zip slip, oversize).

---

## Out of scope

- Adding video MIME types (It.79).
- Malware scanning SaaS integration (hook point only).
- CDN/transcoding.
- Replacing flat-file quota store with Redis/SQL.

---

## Tests

- Each upload surface resolves a registered profile (wiring grep + integration).
- MIME/extension/magic-byte mismatch rejected on every surface.
- Intersection of security + domain MIME lists.
- Zip-Slip and zip bomb samples rejected on backup/extension profiles.
- Stock import blocked on disallowed outbound URL (SSRF fixture).
- Audit log contains no raw filename injection / CSV break characters unsanitized.
- Disabling `unifiedPolicyEnabled` falls back to current behavior without widening allow-list.
- Classic media upload regression suite stays green.

---

## Definition of Done

- [ ] `UploadPolicyEngine` is the single entry point for all listed upload surfaces.
- [ ] `UploadSurfaceRegistry` documents every surface; iteration gate wiring check passes.
- [ ] Intersection semantics for MIME/size are tested and documented.
- [ ] Archive and outbound-import paths share Zip-Slip / SSRF guards.
- [ ] Upload audit events are sanitized and permission-gated.
- [ ] SK/EN security and architecture docs updated.
- [ ] It.79 can add `media-video` profile without new one-off validators.

## Related

[It.24 DAM](ITERATION_24.md) · [It.67](ITERATION_67.md) · [It.79 DAM video](ITERATION_79.md) · [developer SECURITY](developer/SECURITY.md)
