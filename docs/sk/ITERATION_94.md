# Iterácia 94 — Admin self-service UX (bezpečnosť pre začiatočníkov)

> **Stav:** ✅ **hotovo** (18. september 2026)  
> **Priorita:** 🟡 **P1 produkt**  
> Anglická špecifikácia a DoD: [ITERATION_94.md](../en/ITERATION_94.md)

## Čo pribudlo

- **94a** — `ConfirmProvider`, `useAdminConfirm`, `confirmDialog()`; migrácia admin `confirm` / `window.confirm`.
- **94e** — `ContextHelpPanel`, rozšírené tooltip/docLink (system update, performance, media S3, redirecty).
- **94c** — `FieldError` v nastaveniach, API kľúčoch, zmene hesla.
- **94b** — audit toastov: [ADMIN_TOAST_COVERAGE_94.md](../en/ADMIN_TOAST_COVERAGE_94.md).
- **94d** — dashboard widget `GettingStartedChecklist` (automatické probe: obsah, názov webu, médiá, 2FA, SMTP).
- **94g** — modal skratiek (`?`, Ctrl+/ / ⌘+/), odkaz z command palette.

## Kontrola

```bash
./scripts/iteration-gate.sh
```
