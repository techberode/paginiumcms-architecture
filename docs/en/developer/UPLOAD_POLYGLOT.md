# Upload polyglot hardening (It.99)

Cheap **upload-time** checks inside the existing unified upload pipeline ([It.78](../ITERATION_78.md)). No change to public GET paths or editor latency beyond upload I/O + one sample scan.

## Settings (Security → Upload)

| Key | Default | Purpose |
|-----|---------|---------|
| `polyglotMarkerScanEnabled` | **true** | Scan first N bytes for HTML/script markers on images, video, PDF |
| `polyglotMarkerScanMaxBytes` | **65536** (max 262144) | Sample window size |
| `reencodeRasterUploads` | **false** | GD decode/re-encode JPEG/PNG/WebP/GIF on media upload — strips trailing polyglot payload |
| `secureMediaFileNaming` | **true** | On-disk names `image_YYYYMMDD_{id}.ext` / `video_…`; display title unchanged |

**Avatars** always pass through raster normalization (`AvatarImageProcessor`) regardless of `reencodeRasterUploads`.

## What is checked

- **Raster / video:** `<script`, `<html`, `<?php`, `javascript:` in the sample window.
- **SVG:** `<script`, event handlers (`on*=`), `javascript:` in first 16 KB.
- **PDF:** `/JavaScript`, `/OpenAction`, `/Launch`, … and `/JS` objects in the sample — not a full PDF parser.

## Residual risk ([ISS-195](../../ISSUES.md#iss-195))

- Payload **after** the scan window unless `reencodeRasterUploads` is enabled.
- Obfuscated PDF JS outside the sample.
- Serve-time mitigations remain: attachment + `nosniff` + sandbox for active types ([SECURITY.md](SECURITY.md) §15).

## Verification

```bash
vendor/bin/phpunit backend/tests/Core/Security/Upload/PolyglotUploadGuardTest.php
vendor/bin/phpunit backend/tests/Modules/Media/Services/MediaImageOptimizerTest.php
```

## Related

- [MEDIA_UPLOAD.md](MEDIA_UPLOAD.md) — auto-compress on upload (GD) vs `reencodeRasterUploads`
- [ITERATION_99.md](../ITERATION_99.md) · [ITERATION_78.md](../ITERATION_78.md) · [CORE_HARDENING.md](../architecture/CORE_HARDENING.md) §17
