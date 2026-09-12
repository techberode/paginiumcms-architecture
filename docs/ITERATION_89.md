# Iteration 89 — Plugin capability model (3-layer extension security)

> **Status:** ⏳ planned  
> **Priority:** 🟡  
> **English spec:** [en/ITERATION_89.md](en/ITERATION_89.md)

Tri-vrstvový bezpečnostný model pre pluginy: import scan → capability manifest → runtime guardrails. Témy a shortcody ostávajú na existujúcej untrusted-surface linke.

## Slices

| Slice | Obsah |
|------:|-------|
| 89a | Capability katalóg + manifest schéma + validácia pri importe |
| 89b | `PluginCapabilityBroker` + scoped SDK |
| 89c | `SafeHookRunner` + auto-disable + audit |
| 89d | Rozšírenie scanneru (indirection) |
| 89e | CLI scaffold + lokálny scan |

Detailný anglický spec: [docs/en/ITERATION_89.md](en/ITERATION_89.md).
