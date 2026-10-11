# Media upload — compression, security, and limits

> **Scope:** raster/video/document uploads through Media Library and unified upload surfaces (It.78, It.79, It.96, It.99).

---

## Auto-compress on upload (default ON)

**Settings → Media / DAM → Auto-compress images on upload** (`autoOptimizeOnUpload`).

When PHP **GD** is available, JPEG/PNG/WebP uploads are decoded and re-encoded if the result is **smaller** than the original — typical savings on photos (e.g. 800 KB → ~200 KB) without a separate “optimize” step. SVG and PDF are unchanged.

| Setting | Default | Effect |
|---------|---------|--------|
| `autoOptimizeOnUpload` | true | Run `compressForUpload` after policy checks |
| `autoOptimizeMaxEdgePx` | 3840 | Downscale longest edge; `0` = compress only, no resize |
| `jpegQuality` / `webpQuality` | ~85 | 60–95 in schema |

Manual **Optimize** in Media Library uses the same `MediaImageOptimizer` engine.

Implementation: `MediaRepository::applyUploadImageCompression()`.

---

## Upload security (It.78 + It.99)

Unified pipeline: `UploadPolicyEngine` → extension/MIME/magic bytes → **`PolyglotUploadGuard`** (marker scan, SVG/PDF probes).

| Setting (Upload security) | Default | Purpose |
|---------------------------|---------|---------|
| `polyglotMarkerScanEnabled` | true | HTML/script markers in sample window |
| `polyglotMarkerScanMaxBytes` | 65536 | Sample size (max 262144) |
| `reencodeRasterUploads` | false | **Security** GD re-encode (strip trailing payload); more CPU |
| `secureMediaFileNaming` | true | On-disk `image_YYYYMMDD_{id}.ext` |

**Avatars** always normalized via `AvatarImageProcessor` regardless of `reencodeRasterUploads`.

Operator guide: [UPLOAD_POLYGLOT.md](UPLOAD_POLYGLOT.md) · [ISS-195](../../ISSUES.md#iss-195).

---

## Documents and video

- **Documents (It.96):** PDF, Office Open XML, ODF, text — profile in upload policy; PDF sample probe for active content.
- **Video (It.79):** MP4/WebM; first 64 KB scanned for script markers.

Serve-time: active types use attachment + `nosniff` + sandbox where applicable — [SECURITY.md](SECURITY.md) §15.

---

## Related

- [ITERATION_78.md](../ITERATION_78.md) · [ITERATION_96.md](../ITERATION_96.md) · [user/MEDIA_IN_CONTENT.md](../user/MEDIA_IN_CONTENT.md) · [runbooks/CACHE_OPERATIONS.md](../runbooks/CACHE_OPERATIONS.md) (upload compression note)
