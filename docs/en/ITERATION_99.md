# Iteration 99 — Upload polyglot hardening (lean)

> **Status:** ✅ shipped (October 2026)  
> **Priority:** 🟡 security · **does not block** It.98 workspace  
> **Wave:** Post-It.78/79 upload follow-up  
> **Depends on:** [It.78](ITERATION_78.md) unified upload · [It.79](ITERATION_79.md) video markers · [ISS-195](../ISSUES.md#iss-195)  
> **Design goal:** close known polyglot gaps with **minimal impact** on CMS **speed**, **stability**, and **simplicity** (flat-file, no new daemons, no mandatory ImageMagick).

## Problem

Today’s pipeline already rejects MIME/extension mismatch, magic-byte impostors, video/text markers in the first 64 KB, and serves active MIMEs as **attachment + sandbox**. Gaps remain:

| Format | Current | Risk |
|--------|---------|------|
| JPEG/PNG/GIF/WebP | Header magic only | Trailing payload / metadata polyglot (GIFAR-style) if file is still served as `image/*` |
| SVG | Structure sniff | Active markup; mitigated at **serve** time, not normalized at upload |
| PDF | `%PDF-` prefix | Embedded JS/HTML not scanned |
| WebM | EBML + video marker scan | Same 64 KB window as MP4 |

See [ISS-195](../ISSUES.md#iss-195) for severity and tracking.

## Chosen approach (minimal blast radius)

**Principle:** one extra pass on **upload only** inside existing `UploadPolicyEngine` → `MediaFormats::validate` hook — **no** change to public GET, cache, or editor hot paths.

### Tier 1 — default ON, O(n) capped (cheap)

1. **Shared marker probe** (reuse video logic): after magic-byte match, scan **first 64 KB** (configurable ceiling, default 65536) for `<script`, `<html`, `<?php`, `javascript:` on **all** binary profiles that accept images and on **avatar** profile.
2. **SVG active-content reject** (no DOM parser): if MIME is `image/svg+xml`, reject when raw sample matches `(?i)(<script\b|on[a-z]+\s*=|javascript:)` within first **16 KB**.
3. **Settings** (under `uploadSecurity`, defaults preserve today’s behaviour until shipped):
   - `polyglotMarkerScanEnabled` — default **true** when It.99 ships.
   - `polyglotMarkerScanMaxBytes` — default **65536** (max **262144** in schema).

**Cost:** one `str_contains` loop per upload; negligible vs disk I/O.

### Tier 2 — opt-in re-encode (stability-first default OFF)

4. **`reencodeRasterUploads`** — default **false**. When **true**, after Tier 1 pass, run existing **GD** path (same family as `AvatarImageProcessor` / `MediaImageOptimizer::imagecreatefromstring`) to decode and re-encode JPEG/PNG/WebP/GIF to a **clean** bitmap stream before persist.  
   - **Avatar profile:** already re-encodes — wire explicitly as Tier 2 always-on for avatars only (no new user setting).  
   - **Media profile:** opt-in only — avoids surprise CPU on large galleries and keeps “what you upload is what you store” unless operator enables hardening.

**Cost:** CPU proportional to image size; bounded by existing upload size caps; **no** background queue (keeps architecture simple).

### Tier 3 — explicitly out of scope (It.99)

- Full-file malware scanning SaaS, ClamAV daemon, PDF JS parser, WebM container rewrite, transcoding video.
- Replacing SVG with raster automatically (would break icon workflows).
- Changing `scanMagicBytes` default or weakening unified policy.

## Backend touchpoints

| Component | Change |
|-----------|--------|
| `MediaFormats::validate` | call `PolyglotUploadGuard::assertCleanSample()` after magic bytes |
| `PolyglotUploadGuard` (new, ~80 LOC) | marker + SVG probes; optional delegate to raster re-encode service |
| `MediaImageOptimizer` or thin `RasterUploadNormalizer` | shared re-encode entry used by avatar (always) and media (flag) |
| `UploadPolicyEngine` | no new surfaces; same `enforceBinary` contract |
| `SettingsSchema` | three keys above + SK/EN help text |

## Tests (regression pack)

- JPEG with valid header + `<script` in byte 50_000 → reject (Tier 1).
- Clean PNG upload → unchanged behaviour.
- SVG with `onload=` → reject.
- Video polyglot fixture stays rejected ([existing `MediaFormatsTest`](../../backend/tests/Modules/Media/MediaFormatsTest.php)).
- With `reencodeRasterUploads=false`, GIFAR tail **after** 64 KB → **documented residual** (accept or extend scan max in settings).
- With `reencodeRasterUploads=true`, appended tail stripped after re-encode.
- PHPUnit + gate wiring grep unchanged.

## Definition of Done

- [x] `PolyglotUploadGuard` integrated in `UploadPolicyEngine` + legacy `MediaRepository` path.
- [x] Settings: `polyglotMarkerScanEnabled`, `polyglotMarkerScanMaxBytes`, `reencodeRasterUploads`, `secureMediaFileNaming`.
- [x] Secure storage names `image_YYYYMMDD_{id}.ext` / `video_…` (display name unchanged in registry).
- [x] [ISS-195](../ISSUES.md#iss-195) updated — raster/SVG/video Tier 1; PDF residual documented.
- [x] PHPUnit: `PolyglotUploadGuardTest`, `MediaSecureUploadNamingTest`.
- [x] Serve-time `nosniff` unchanged (already global — not part of upload code path).

## Related

[It.78](ITERATION_78.md) · [It.79](ITERATION_79.md) · [developer/SECURITY](developer/SECURITY.md) · [MEDIA_IN_CONTENT.md](user/MEDIA_IN_CONTENT.md)
