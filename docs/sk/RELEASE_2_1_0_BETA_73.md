# Release `v2.1.0-beta.73` — Editor Workbench (Mermaid, grafy, CodeMirror)

> **Dátum:** 2026-09-13  
> **Tag:** `v2.1.0-beta.73`  
> **Typ:** It.90c–e — dokončenie Editor Workbench MVP

---

## Zhrnutie

Autori môžu vkladať **Mermaid diagramy** a **stĺpcové/čiara grafy** cez toolbar wizardy; voliteľne **CodeMirror 6** namiesto textarea pre Markdown. Všetky bloky sa renderujú na **serveri do SVG** — bez client-side eval.

---

## Čo je v release

| Oblasť | Zmena |
|--------|--------|
| **Mermaid (`:::mermaid`)** | Modal, BE render (`atelier/diagram`), sanitizer SVG |
| **Grafy (`:::chart`)** | Modal, JSON schéma, `ChartSvgRenderer` → SVG |
| **CodeMirror 6** | Nastavenie `editor.markdownSurface`: `native` \| `codemirror6` |
| **Toolbar** | Capabilities `mermaid`, `chart` v Settings → Editor |
| **Bezpečnosť** | Dôveryhodné figure bloky cez `ContentSecuritySanitizer` |

**Mimo release:** plugin Editor Tool SDK (It.89); Tiptap parita pre mermaid/chart (It.91c).

---

## Deploy

Rovnaký postup ako v [en/RELEASE_2_1_0_BETA_73.md](../en/RELEASE_2_1_0_BETA_73.md) s `GIT_REF=v2.1.0-beta.73`.

---

## Smoke test

- [ ] Settings → Editor → zapnúť Mermaid + Charts v Markdown toolbare
- [ ] Voliteľne CodeMirror 6 surface
- [ ] Vložiť diagram a graf → verejná stránka má SVG

---

## Súvisiace

[ITERATION_90.md](ITERATION_90.md) · [CHANGELOG.md](../../CHANGELOG.md)
