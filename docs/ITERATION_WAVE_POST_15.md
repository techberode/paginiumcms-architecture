# Post–Iteration 15 wave: Editor, navigation & layout

**Status:** ⏳ Planned — **It.15 gate ✅ (2.0.38)** — wave may start  
**Current release:** 2.0.38 · Last shipped: [It.15](ITERATION_15.md)

## Gate rule

> **It.53–58 may start now that `PluginManager` and extension runtime (It.15) are shipped in 2.0.38.**

Reason: modular editor extensions (`code_blocks`, custom Tiptap nodes) and optional layout blocks depend on the plugin law (`Http/Extensions/{id}/`, FE dynamic import).

## Wave map

```
It.15 PluginManager ✅ (prerequisite)
    │
    ├── It.53  Smooth SPA reload (UX foundation)
    │
    ├── It.54  Editor profiles (Markdown + Tiptap modular toolbar)
    │       └── It.55  Tiptap JSON storage + image upload
    │
    ├── It.56  Rich navigation (description, icon, hover preview)
    │
    ├── It.57  Auto tags & description generator
    │
    └── It.58  Page layout builder (5 templates) + color schemes & appearance
```

## Iteration index

| It. | Title | Doc | Depends on |
|-----|-------|-----|------------|
| **53** | Smooth SPA reload | [ITERATION_53.md](ITERATION_53.md) | It.15 |
| **54** | Modular MD + WYSIWYG profiles | [ITERATION_54.md](ITERATION_54.md) | It.15, It.53 recommended |
| **55** | Tiptap JSON + media upload | [ITERATION_55.md](ITERATION_55.md) | It.15, It.54 |
| **56** | Rich menu items | [ITERATION_56.md](ITERATION_56.md) | It.15 |
| **57** | Tag & description generator | [ITERATION_57.md](ITERATION_57.md) | It.15, It.54/55 optional |
| **58** | Layout builder (lightweight) | [ITERATION_58.md](ITERATION_58.md) | It.15, It.54/55, It.48 optional |

## Recommended delivery order

1. **It.15** — plugins (blocker)
2. **It.53** — quick UX win, unblocks editor work
3. **It.54 → It.55** — editor stack (largest value)
4. **It.56** — navigation (parallel possible after It.53)
5. **It.57** — SEO helpers (after editor can export plain text)
6. **It.58** — layout builder last (highest complexity)

Each iteration: `./scripts/iteration-gate.sh` → docs → release C&P (per `.cursorrules`).

## User story mapping

| Request | Iteration |
|---------|-----------|
| Reload stránky nesmie sekať | It.53 |
| Profily editora, modulárny Tiptap/Markdown | It.54 |
| Ukladanie Tiptap JSON + obrázky do flat-file | It.55 |
| Popis + ikona/obrázok menu, hover náhľad | It.56 |
| Generátor tagov a popisu z obsahu | It.57 |
| Editor vzhľadu / bloky ako Joomla (bez porušenia flat-file) | It.58 |
| Predvolené farebné schémy + náhľad + light/dark/system | It.58 |

## Related backlog items

- [It.31](ITERATION_BACKLOG.md) Live Preview — overlaps It.51/58; merge at implementation time
- [It.48](ITERATION_48.md) Static HTML — layout builder HTML cache
- [It.32](ITERATION_BACKLOG.md) Performance — complements It.53
