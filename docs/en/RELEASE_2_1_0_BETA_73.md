# Release `v2.1.0-beta.73` — Editor Workbench (Mermaid, charts, CodeMirror)

> **Date:** 2026-09-13  
> **Tag:** `v2.1.0-beta.73`  
> **Type:** It.90c–e — completes Editor Workbench MVP (diagrams, charts, optional rich Markdown surface)

---

## One-line summary

Authors can insert **Mermaid diagrams** and **bar/line charts** via toolbar wizards; optional **CodeMirror 6** replaces the plain textarea for Markdown editing. All blocks render to **server-side SVG** — no client-side diagram/chart eval.

---

## What shipped

| Area | Change |
|------|--------|
| **Mermaid (`:::mermaid`)** | `MermaidInsertModal`; BE render via `atelier/diagram`; SVG sanitizer; admin preview shows source only |
| **Charts (`:::chart`)** | `ChartInsertModal`; JSON schema (bar/line, ≤12 points); `ChartSvgRenderer` → SVG |
| **CodeMirror 6** | Settings `editor.markdownSurface`: `native` \| `codemirror6`; dark mode aware |
| **Toolbar** | Capabilities `mermaid`, `chart` in Settings → Editor toolbar builder |
| **Security** | Trusted `<figure class="paginium-mermaid\|paginium-chart">` preserved through `ContentSecuritySanitizer` |

**Not in this release:** plugin Editor Tool SDK (It.89 overlap); Tiptap parity for mermaid/chart (It.91c).

---

## Deploy (production)

```bash
cd /var/www/paginiumcms.com
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.73 \
  APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms \
  BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

## Deploy (demo)

```bash
cd /var/www/paginiumcms-demo
git fetch --tags origin
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.73 \
  APP_ROOT=/var/www/paginiumcms-demo \
  STACK_DIR=/var/lib/docker/compose/paginiumcms-demo \
  BACKEND_PORT=8091 \
  ./scripts/deploy-instance-update.sh
```

---

## Smoke test checklist

- [ ] Settings → Editor → enable **Mermaid** and **Charts** in Markdown toolbar; save
- [ ] Optional: set **Markdown surface** = CodeMirror 6; open article editor — syntax highlight visible
- [ ] Insert Mermaid diagram → save draft → public page shows SVG (not raw `:::mermaid`)
- [ ] Insert chart (bar + line) → validator rejects mismatched labels/values
- [ ] `./scripts/iteration-gate.sh` green on checkout

---

## Related

[ITERATION_90.md](ITERATION_90.md) · [CHANGELOG.md](../../CHANGELOG.md) · [CONTINUATION.md](CONTINUATION.md)
