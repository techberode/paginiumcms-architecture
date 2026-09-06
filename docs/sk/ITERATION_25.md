---
title: Iterácia 25 – Setup wizard a update UX pred 1.0
description: Browser-first onboarding a dashboard update UX — základ beta.62, M1+ preflight beta.65
icon: material/check-circle
---

# Iterácia 25 – Setup wizard a update UX pred 1.0

| Pole | Hodnota |
|---|---|
| Stav | ✅ **Základná fáza** (`v2.1.0-beta.62`) + ✅ **M1+ preflight/infra** (`v2.1.0-beta.65`) |
| Obdobie | pre-stable (cieľ september 2026) |

## Produktový cieľ

Prvé spustenie v prehliadači a dashboard „Update available / Update now“ pre SUPER_ADMIN (It.63 deploy engine).

## Dodané (základná fáza — STABILIZATION §5.1) — `beta.62`

- Wizard **`/setup`** pre nenainštalovanú inštanciu
- Prvý SUPER_ADMIN + názov webu/jazyk
- `general.installed = true` + redirect na dashboard
- Dashboard update banner (SUPER_ADMIN, nie demo)
- Backup prompt pred deployom
- Dokumentácia INSTALLATION + FIRST_STEPS SK/EN
- PHPUnit + `scripts/smoke-it25.sh`

## Dodané (M1+ stretch — `beta.65`)

- **Krok Server** — read-only kontrola PHP, rozšírení, úložiska, CLI nástrojov
- **`GET /api/setup/preflight`** — štruktúrované kontroly + postup inštalácie (Ubuntu/Debian)
- **Bez auto-inštalácie z webu** — len copy-paste príkazy (bezpečnostný baseline)
- **Krok Infra** — `backendPort`, `media.storageDriver` pri dokončení setupu
- Orphan recovery pri 0 useroch (`beta.63`); deploy readiness banner (`beta.64`)

### Kroky wizardu (aktuálne)

| # | Krok | Účel |
|---|------|------|
| 1 | **Server** | Preflight + návod na doinštaláciu |
| 2 | **Administrátor** | Prvý SUPER_ADMIN |
| 3 | **Web** | Názov + jazyk adminu |
| 4 | **Infra** | Backend port, storage driver |
| 5 | **Hotovo** | Nastavenia, presmerovanie na `/login` (oficiálne prihlásenie) |

## Ešte odložené

- voliteľný stock-image seed vo wizardi,
- auto-inštalácia OS balíkov z webu (**zamietnuté**),
- plné rollback UI (zatiaľ len backup prompt).

## Bezpečnostný kontrakt

- Preflight = **iba GET**, žiadny shell, žiadne outbound volania.
- Inštalačné kroky = hardcoded whitelist v PHP.
- `/api/setup/*` CSRF-exempt len pre pre-auth bootstrap.

## Odkazy

- [RELEASE_2_1_0_BETA_65.md](../en/RELEASE_2_1_0_BETA_65.md)
- [ITERATION_25.md (EN)](../en/ITERATION_25.md)
- [INSTALLATION.md](user/INSTALLATION.md) §7
