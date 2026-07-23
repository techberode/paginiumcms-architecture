# Iteration 60 – Vlastné komponenty editora (Markdown / WYSIWYG)

**Status:** ⏳ Planned  
**Priorita:** 🟡 Stredná  
**Nadväzuje na:** [It.54 Editor profiles](ITERATION_54.md) ✅ · [It.15 Plugins](ITERATION_15.md) ✅ · [It.55 Tiptap](ITERATION_55.md) ✅

## Cieľ

Administrátor (nie rola **USER**) môže **rozšíriť Markdown a WYSIWYG editor** o vlastné bloky/komponenty — cez inštaláciu pluginu alebo konfiguráciu v **Nastavenia → Stránka → Editor**.

## Rozsah

| Oblasť | Popis |
|--------|--------|
| **Settings UI** | Skupina `editor` (alebo podskupina): zoznam povolených custom komponentov, mapovanie na profily (`company`, `blog`, …) |
| **Inštalácia** | Plugin ZIP ([It.15](ITERATION_15.md)) môže registrovať `editor.components[]` (názov, schema, FE bundle hook) |
| **Markdown** | Shortcode / custom directive → render na FE + validácia na BE (`EditorContentValidator`) |
| **WYSIWYG** | Tiptap Node extension registrovaná dynamicky podľa settings + plugin manifest |
| **RBAC** | Konfigurácia viditeľná pre **EDITOR**, **ADMIN**, **SUPER_ADMIN** — **nie USER** |

## Technicky

- Rozšírenie `EditorProfileService` o `allowedCustomComponents` per profile.
- BE: whitelist komponentov pri save (400 ak neznámy typ).
- FE: lazy import komponentov podľa manifestu (code-splitting).
- Dokumentácia pre plugin autorov: nová sekcia v [PLUGINS.md](user/PLUGINS.md).

## Mimo rozsahu (v1)

- Arbitrárny HTML/JS v komponentoch bez sanitizácie.
- Marketplace mimo existujúceho plugin importu.

## Acceptance criteria

- [ ] Settings → Editor: zapnúť/vypnúť custom komponent pre profil
- [ ] Plugin demo registruje 1 custom block (MD + WYSIWYG)
- [ ] USER nemá prístup k nastaveniam editor extensions
- [ ] PHPUnit: validator + save rejection
- [ ] Vitest: toolbar zobrazí custom block keď povolený

## Súvisiace

- [ITERATION_54.md](ITERATION_54.md) — profily
- [ITERATION_15.md](ITERATION_15.md) — plugin runtime
- [architecture/SETTINGS.md](architecture/SETTINGS.md)
