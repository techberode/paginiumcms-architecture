# Iteration 60 – Vlastné komponenty editora (Markdown / WYSIWYG)

**Status:** ✅ Implemented  
**Priorita:** 🟡 Stredná  
**Nadväzuje na:** [It.54 Editor profiles](ITERATION_54.md) ✅ · [It.15 Plugins](ITERATION_15.md) ✅ · [It.55 Tiptap](ITERATION_55.md) ✅

## Cieľ

Administrátor (nie rola **USER**) môže **rozšíriť Markdown a WYSIWYG editor** o vlastné bloky/komponenty — cez inštaláciu pluginu alebo konfiguráciu v **Nastavenia → Stránka → Editor**.

## Rozsah

| Oblasť | Popis |
|--------|--------|
| **Settings UI** | Skupina `editor`: master switch + matica profil × komponent |
| **Inštalácia** | Plugin ZIP ([It.15](ITERATION_15.md)) registruje `editor.components[]` v `plugin.json` |
| **Markdown** | Direktíva `:::component-id` → validácia na BE |
| **WYSIWYG** | Tiptap Node extension dynamicky podľa settings |
| **RBAC** | Skupina `editor` dostupná pre **EDITOR**, **ADMIN**, **SUPER_ADMIN** — **nie USER** |

## Technicky

- `EditorComponentRegistry` + `EditorProfileService::getAllowedCustomComponents()`
- `EditorContentValidator` — whitelist custom komponentov pri save (400 ak neznámy / nepovolený)
- Referenčný plugin `hello-widget` — demo block MD + WYSIWYG
- FE: `loadAllowedEditorComponents()`, toolbar tlačidlá, `EditorCustomComponentsPanel`

## Acceptance criteria

- [x] Settings → Editor: zapnúť/vypnúť custom komponent pre profil
- [x] Plugin demo registruje 1 custom block (MD + WYSIWYG)
- [x] USER nemá prístup k nastaveniam editor extensions (EDITOR áno, ostatné skupiny nie)
- [x] PHPUnit: validator + save rejection
- [x] Vitest: toolbar zobrazí custom block keď povolený

## Smoke test

1. **Extensions** — povoľ `hello-widget` (ak ešte nie je enabled).
2. **Settings → Editor** — zapni „Povoliť custom komponenty“, zaškrtni Hello Widget pre profil **Blog**.
3. **Editor článku** (profil Blog) — v toolbare sa objaví tlačidlo **Hello Widget** (MD aj WYSIWYG).
4. **Ulož** obsah s `:::hello-widget` — HTTP 200. Bez povolenia v settings → 400.

## CI poznámka (2026-07-26)

- Opravený parse error v `ExtensionManifestValidator.php` (chýbajúca `}`).
- PHPUnit testy používajú reálny `EditorComponentRegistry` + mock `PluginManagerInterface`.

## Súvisiace

- [ITERATION_54.md](ITERATION_54.md) — profily
- [ITERATION_15.md](ITERATION_15.md) — plugin runtime
- [architecture/SETTINGS.md](architecture/SETTINGS.md)
