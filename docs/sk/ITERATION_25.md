---
title: Iterácia 25 – Setup wizard a update UX pred 1.0
description: Browser-first onboarding a dashboard update UX — základná fáza dodaná vo v2.1.0-beta.62
icon: material/check-circle
---

# Iterácia 25 – Setup wizard a update UX pred 1.0

| Pole | Hodnota |
|---|---|
| Stav | ✅ **Základná fáza dodaná** (`v2.1.0-beta.62`, 2026-09-03) |
| Obdobie | pre-stable (cieľ september 2026) |

## Dodané (základná fáza — STABILIZATION §5.1)

- Wizard **`/setup`** pre nenainštalovanú inštanciu
- Prvý SUPER_ADMIN + názov webu/jazyk
- `general.installed = true` + redirect na dashboard
- Dashboard update banner (SUPER_ADMIN, nie demo)
- Backup prompt pred deployom
- Dokumentácia INSTALLATION + FIRST_STEPS SK/EN
- PHPUnit + `scripts/smoke-it25.sh`

## Odložené

- voliteľný stock-image seed,
- git/deploy checklist krok vo wizardi,
- plné rollback UI (zatiaľ len backup prompt).

## Odkazy

- [RELEASE_2_1_0_BETA_62.md](../en/RELEASE_2_1_0_BETA_62.md)
- [ITERATION_25.md (EN)](../en/ITERATION_25.md)
