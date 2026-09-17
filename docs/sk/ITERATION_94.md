# Iterácia 94 — Admin UX pre samostatnosť (efektivita pre neskúsených)

> **Stav:** ⏳ plánované (audit 17. 9. 2026)  
> **Priorita:** 🟡 **P1 produkt**  
> **Anglická špecifikácia:** [en/ITERATION_94.md](../en/ITERATION_94.md)  
> **Vizuálny block builder:** nie It.94 — slice **[58f-h](ITERATION_58f.md#slice-58f-h--vizuálne-plátno-blokov)**

## Prečo

Audit UX (september 2026): admin funguje pre pokročilých, ale chýba spätná väzba po akciách, pretrvávajúci onboarding, nápoveda pri zložitých nastaveniach a objaviteľnosť skratiek. Skladanie stránky z blokov ostáva gap oproti sľubu It.58f — rieši **58f-h**, nie 94.

## Odporúčané poradie

| Poradie | Slice | Obsah |
|--------:|-------|--------|
| 1 | **58f-h** | Skutočný drag-and-drop canvas (dnd-kit), nadväzba na renderery z It.58f |
| 2 | **94a** | Jednotný toast systém (sonner / react-hot-toast) |
| 3 | **94b** | Getting-started checklist na dashboarde (progress, pretrváva) |
| 4 | **94c** | Rozšírenie tooltipov pri Performance Guard, S3, redirectoch |
| 5 | **94d** | Cheat sheet klávesových skratiek (`?`, Cmd+/) |

## Slices 94a–94d (stručne)

- **94a** — jeden toast provider v admin root; postupná náhrada inline bannerov.  
- **94b** — widget s 5–6 úlohami (článok, SEO, doména, mail, 2FA); odlíšené od jednorazového `OnboardingTour`.  
- **94c** — `SettingHelpTooltip` + i18n help pre ťažké settings skupiny.  
- **94d** — modal so zoznamom skratiek + odkaz z Command Palette.

## Fronta

[Pokračovanie](../en/CONTINUATION.md) · [Backlog](ITERATION_BACKLOG.md)
