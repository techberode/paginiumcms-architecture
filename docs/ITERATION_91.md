# Iteration 91 — Trusted HTML & external embeds

> **Status:** ⏳ planned (91a in progress)  
> **Full spec (EN):** [en/ITERATION_91.md](en/ITERATION_91.md)

## Summary

Role-based **trusted HTML blocks** (`:::html-safe` + HTMLPurifier) and **external embeds** (`:::embed` YouTube/Vimeo) for ADMIN/SUPER_ADMIN. React **“Insert HTML block”** bypasses WYSIWYG only — backend always purifies and validates.

## Phases

| Phase | Scope |
|-------|--------|
| 91a | Permissions, `:::html-safe`, HTMLPurifier, FE modal |
| 91b | `:::embed` providers |
| 91c | Tiptap parity + audit |
| 91d | Security regression pack |

## Related

[It.90](ITERATION_90.md) · [It.79](ITERATION_79.md)
