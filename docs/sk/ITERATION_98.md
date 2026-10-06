---
title: Iterácia 98 — Workspace + CLI obsah
description: Plán viacerých workspace v jednej inštancii a bezpečné CLI ako WP-CLI
---

# Iterácia 98 — Workspace + CLI obsah

> **Stav:** ⏳ plánované · **Anglická špecifikácia:** [ITERATION_98.md](../en/ITERATION_98.md)

## V skratke

- **Workspace** = viac „stránok/klinetov“ v **jednej** inštancii (`data/workspaces/{id}/…`), nie jeden admin na desiatky vzdialených serverov (to až neskôr).
- **CLI** (`content:list`, `content:show`, `content:create`, `content:update`, `content:publish`) — default **dry-run**, zápis len s `--run` + `CLI_ALLOW_MUTATIONS` v produkcii; rovnaká validácia ako API, audit `actor=cli`.
- Už existuje **`content:import`** / export — 98 na to nadväzuje, nekopíruje WordPress celý.

## Fázy

| Fáza | Čo |
|------|-----|
| 98a | Model workspace + migrácia `default` |
| 98b | CLI čítanie |
| 98c | CLI zápis (chránený) |
| 98d | Audit + dokumentácia |

Ostatné návrhy (A/B testy, súvisiace články, TOC, cross-post, vizuálne B2–B3): [PRODUCT_GROWTH_PROPOSALS.md](../en/architecture/PRODUCT_GROWTH_PROPOSALS.md).
