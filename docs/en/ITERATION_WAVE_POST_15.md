# Post-It.15 wave — editor, navigation, and layout

> **Status:** ✅ historical wave, mostly complete  
> **Original gate:** It.15 PluginManager and extension runtime  
> **Remaining active scope:** It.58d and the related It.67 security gate

This document is no longer a plan for an unstarted wave. It records what the It.15 dependency unlocked, what shipped, and what moved into the new Hybrid Engine direction.

---

## 1. Original dependency

```text
It.15 PluginManager ✅
    ├─► It.53 Smooth SPA reload
    ├─► It.54 Modular editor profiles
    │      └─► It.55 Tiptap JSON + media upload
    ├─► It.56 Rich navigation
    ├─► It.57 Auto tags & description
    └─► It.58 Layout and appearance
```

It.15 introduced external extension rules, the registry, and hook runtime. This removed the architecture blocker for a modular editor and additional UX extensions.

---

## 2. Wave outcome

| It. | Topic | Status | Release / note |
|-----|-------|--------|----------------|
| **53** | Smooth SPA reload | ✅ | `2.0.39` |
| **54** | Modular Markdown/WYSIWYG profiles | ✅ | `2.0.42` |
| **55** | Tiptap JSON and image upload | ✅ | `2.0.43` |
| **56** | Rich navigation items | ✅ | `v2.1.0-beta.5` |
| **57** | Auto tags and meta description | ✅ | `v2.1.0-beta.4` |
| **58b** | Color schemes and themed public site | ✅ | `v2.1.0-beta.8` |
| **58c** | Layout Switch and page templates | ✅ | `v2.1.0-beta.23` |
| **58d** | Remaining layout blocks/polish | ⏳ | scope must be frozen precisely |

Expanded beyond the original map:

| It. | Topic | Status |
|-----|-------|--------|
| **59** | Scheduled publishing | ✅ |
| **60** | Custom editor components | ✅ |
| **61** | Newsletter footer + admin subscribers | ✅ |

---

## 3. Do not plan again

The following are no longer active backlog items:

- generic “smooth reload” without a concrete regression,
- basic editor profiles,
- Tiptap JSON storage,
- rich navigation descriptions/icons,
- the basic auto-tags/meta generator,
- color schemes,
- the basic layout template picker,
- custom editor component foundation,
- newsletter footer foundation.

A new request must be described as a concrete extension or bugfix, not as reopening the entire shipped iteration.

---

## 4. Remaining It.58d

Before implementation, freeze:

- which layout blocks are still missing,
- whether they belong to the page model, theme runtime, or static rendering pipeline,
- compatibility with `layoutTemplate` from It.58c,
- schema/policy rules for user-editable layout JSON,
- preview and fallback for invalid configuration,
- relationship to It.48 static rendering and It.70 Git publishing.

It.58d must not create a second independent page-builder model.

---

## 5. Security follow-up — It.67

The more extensible the editor and layout become, the more important it is to separate trusted content from untrusted code. It.67 should primarily cover:

- shortcode and custom component input,
- Monaco/Code Editor write gate,
- theme and layout package imports,
- CSP and dependencies,
- deny-list/allow-list combined with schema validation,
- regression security packs.

---

## 6. Relationship to Hybrid Engine

| Older wave | Hybrid Engine continuation |
|------------|----------------------------|
| Tiptap JSON | It.68 schema registry and safe storage contract |
| Layout templates | It.48 static rendering + It.70 publishing pipeline |
| Media upload | It.72 media drivers |
| UI i18n | It.73 multi-locale content document |
| Meta generator | It.75 AI agent proposals, still with human approval |

The original wave is therefore not a dead end; it is the frontend and UX foundation of the new architecture.

---

## 7. Archive rule

Maintain this document as a **wave outcome**. Active priorities are in [ROADMAP.md](ROADMAP.md) and [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md).
