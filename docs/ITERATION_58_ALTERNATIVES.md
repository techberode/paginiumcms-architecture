# It.58 — Porovnanie alternatív layout buildera (2026-07-26)

> **Stav:** Rozhodovací dokument pred implementáciou [ITERATION_58.md](ITERATION_58.md)  
> **Verzia CMS:** `v2.1.0-beta.7` · React 18 · flat-file SSOT

Tri navrhované architektúry oproti **aktuálnemu plánu It.58** (5 šablón + bloky + CSS variables, bez pixel canvas).

---

## Odporúčanie (stručne)

| Alternatíva | Vhodnosť pre PaginiumCMS | Poznámka |
|-------------|-------------------------|----------|
| **1 — Grid (react-grid-layout)** | 🟡 Pre dashboard widgety, nie pre verejné stránky | Ťažké JS na admin; verejný render OK cez CSS Grid, ale komplexita vysoká |
| **2 — Sekvenčný page builder** | ✅ **Najbližšie It.58** | `@hello-pangea/dnd` + Tailwind šírky = plánovaný „visual outline, reorder blocks“ |
| **3 — Vanilla JS + statické HTML** | 🟡 Ako **It.48 slice** (cache), nie ako jediný editor | Server-side HTML cache dobrý pre výkon; editor ostáva React admin |

**Odporúčaný hybrid pre It.58:** **Alt 2** (admin UX) + **CSS variables / Tailwind tokeny** zo It.58b + voliteľný **statický HTML cache** z Alt 3 v rámci [It.48](ITERATION_48.md).

---

## Alternatíva 1 — Komplexný Grid (Dashboard Style)

### Flat-file JSON (ukážka)

```json
{
  "schemaVersion": 1,
  "template": "grid-dashboard",
  "items": [
    { "id": "hero-1", "type": "hero", "x": 0, "y": 0, "w": 12, "h": 4, "props": { "title": "Vitajte" } },
    { "id": "text-1", "type": "richtext", "x": 0, "y": 4, "w": 8, "h": 6, "props": { "bodyRef": "main" } },
    { "id": "cta-1", "type": "cta", "x": 8, "y": 4, "w": 4, "h": 3, "props": { "href": "/contact" } }
  ]
}
```

Uloženie: sidecar `content/pages/{slug}.layout.json` alebo front matter kľúč `layout`.

### Admin React (skica)

```tsx
// Lazy: const Grid = lazy(() => import('react-grid-layout'));
// onLayoutChange → debounce → PUT /api/admin/pages/{id}/layout
// Validácia: w/h v rozsahu 1–12, x/y ≥ 0, žiadne prekryvy (voliteľné)
```

### PHP validácia (Slim)

```php
// LayoutGridValidator::validate(array $payload): void
// - schemaVersion int
// - items[].x,y,w,h: int, min/max grid (12 cols)
// - items[].type: allowlist (hero, richtext, …)
// - props: per-type Validator (HtmlDomSanitizer pre HTML polia)
```

### CSS izolácia

- Verejný web: `className="paginium-public-grid"` + `--theme-*` na `#public-root` only
- Admin grid editor: wrapper `data-admin-layout-editor` — **bez** globálnych `--theme-*` na `document.documentElement` admin shell

### Čo už máme vs. čo chýba

| Položka | Stav |
|---------|------|
| Flat-file JSON SSOT | ✅ vzor v `navigation.json`, settings |
| `HtmlDomSanitizer` / XSS | ✅ ISS-086 |
| Lazy Monaco | ✅ `@monaco-editor/react` v projekte (Code Editor) |
| Grid layout editor | ⏳ It.58 — **neimplementované** |
| Public render bez admin bundle | ✅ SPA public routes oddelené; layout renderer ⏳ |

---

## Alternatíva 2 — Sekvenčný page builder (Page Builder Style)

### Flat-file JSON (ukážka)

```json
{
  "schemaVersion": 1,
  "template": "hero-content",
  "sections": [
    { "id": "s1", "type": "hero", "width": "full", "props": { "title": "…", "image": "media/uuid.jpg" } },
    { "id": "s2", "type": "richtext", "width": "2/3", "props": { "bodyRef": "main" } },
    { "id": "s3", "type": "cta", "width": "1/3", "props": { "label": "Kontakt", "href": "/contact" } }
  ]
}
```

`width`: allowlist `full` \| `1/2` \| `1/3` \| `2/3` \| `1/4` \| `3/4`.

### Admin React (skica)

```tsx
// @hello-pangea/dnd DragDropContext — vertikálne reorder sections
// Select width → PATCH section.width
// onDragEnd → PUT layout JSON
```

### PHP validácia

```php
// LayoutSectionValidator
// - sections: array, max 50 items
// - width enum
// - href v CTA: OutboundUrlGuard / relative path only
// - HTML props → ContentSecuritySanitizer::sanitizeHtml()
```

### CSS izolácia

- Dynamic Tailwind: **prefer fixed class map** (`widthClass['1/2'] => 'max-w-1/2'`) — **nie** arbitrary `w-[${user}]` (Tailwind purge + injection)
- Téma: `data-scheme` + `data-theme` len na public wrapper ([ITERATION_58.md](ITERATION_58.md) token model)

### Čo už máme vs. čo chýba

| Položka | Stav |
|---------|------|
| Tiptap / MD bloky | ✅ It.54–55 |
| `SitePreviewModal` | ✅ It.51 |
| DnD knižnica | ⏳ `@hello-pangea/dnd` **nie v package.json** |
| `LayoutValidator` | ⏳ It.58 backend |
| Sekvenčný builder UI | ⏳ It.58 frontend |

**→ Toto je baseline pre It.58a.**

---

## Alternatíva 3 — Vanilla JS + statické HTML (Zero-Dependency visitor)

### Flat-file JSON (ukážka)

Rovnaké ako Alt 2; navyše server generuje cache:

```
content/pages/about.layout.json     ← SSOT (admin edit)
content/cache/pages/about.html      ← compiled snapshot (public serve)
content/cache/pages/about.meta.json ← { compiledAt, layoutHash, scheme }
```

### Admin React (skica)

- Ľahký textarea + Prism pre HTML embed bloky (profile-gated)
- Drag: native HTML5 DnD alebo tenká React obálka

### PHP — kompilácia pri save

```php
// LayoutHtmlCompiler::compile(Page $page, array $layout): string
// - render sections → HTML string
// - ContentSecuritySanitizer on each HTML fragment
// - write cache atomically: temp + rename + LOCK_EX
```

### CSS izolácia

- Statické HTML obsahuje len `paginium-public-*` triedy + inline `:root` token block pre schému
- Admin nikdy nenačítava compiled HTML do iframe bez sandbox CSP

### Čo už máme vs. čo chýba

| Položka | Stav |
|---------|------|
| `ContentCacheService` / purge | ✅ admin panel cache |
| `ContentBodyRenderer` | ✅ BE render MD/HTML/Tiptap |
| `LOCK_EX` / atomic write | ✅ SettingsRepository, LockManager, … |
| Layout → static HTML pipeline | ⏳ It.48 + It.58 |
| Prism.js editor | ⏳ nie v projekte (Monaco pre code editor) |

---

## Bezpečnostný checklist (26.07.2026) — stav v PaginiumCMS

| Oblast | Požiadavka | Stav | Kde / poznámka |
|--------|------------|------|----------------|
| **1. Flat-file FS** | Web root izolácia | ✅ | `backend/public/` docroot; `data/` mimo |
| | HMAC podpis súborov | ⏳ | Len `DevTokenGenerator` HMAC; **nie** content integrity |
| | CHMOD / vlastník ≠ www-data | ⏳ Ops — dokumentácia [PRIVATE_DOMAIN_DEPLOY.md](../PRIVATE_DOMAIN_DEPLOY.md) |
| | `LOCK_EX` pri zápise | ✅ | Settings, locks, audit, cache, … |
| **2. Backend** | Path traversal / slug | ✅ | `FileValidator`, storage allow-list (C-STORAGE) |
| | CSRF | ✅ | `CsrfMiddleware` (ISS-012) |
| | Rate limiting | ✅ | Login, OTP, suggest-meta, global middleware |
| | `disable_functions` | ⏳ Ops — `php.ini` na serveri |
| **3. Upload** | Blok PHP v upload dir | ⏳ Ops — nginx `location` (docs) |
| | Magic bytes (`finfo`) | ✅ | `FileValidator.php` |
| | Re-encode obrázkov | ⏳ | MIME check áno; GD recompress **nie** |
| | UUID názvy súborov | ✅ | Media repository |
| **4. Frontend** | DOMPurify + BE sanitizácia | ✅ | ISS-086 `HtmlDomSanitizer` + `sanitizePublicHtml` |
| | `javascript:` v href | ✅ | `safeUrl.ts` + BE URI guard |
| | Token v httpOnly cookie | 🟡 | Session cookie httpOnly; CSRF token v **localStorage** (ISS-012 design) |
| **5. HTTP** | CSP | 🟡 | `SecurityMiddleware` — `script-src` bez unsafe-inline; `style-src` unsafe-inline (React) |
| | X-Frame-Options, nosniff, HSTS | ✅ / ⏳ | Middleware + nginx docs |
| **6. Audit** | Immutable audit log | 🟡 | JSON logs + SecurityAuditStore; append-only **nie** OS-level |
| | `npm audit` / `composer audit` | ✅ CI | ISS-089: RR RSC high = false positive pre SPA |
| **Sieť** | WAF / static deny | ✅ | `FirewallMiddleware` (It.50) |
| | Session-bound API | ✅ | AuthMiddleware + CSRF |

### Otvorené (priorita pre ďalšie iterácie)

1. **ISS-014** — overiť `CORS_ALLOWED_ORIGINS` na test/prod serveri  
2. **ISS-089** — npm audit high (RR RSC-only) — akceptované na React 18; plná oprava = React 19 + RR 8.3  
3. **ISS-083** — ESLint 10 + flat config upgrade  
4. **HMAC content integrity** — nový návrh (nie v scope beta.7)  
5. **GD image re-encode** — hardening upload pipeline  
6. **It.58** — layout builder (Alt 2 + tokeny z plánu)

---

## Súvisiace

- [ITERATION_58.md](ITERATION_58.md) — plánovaná implementácia  
- [ISSUES.md](ISSUES.md) — ISS-086–090  
- [SECURITY.md](../SECURITY.md) — verejný prehľad
