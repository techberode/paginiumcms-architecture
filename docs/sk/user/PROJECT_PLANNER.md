---
title: Plánovač projektu stránky
description: Plánovanie spustení, míľnikov obsahu a termínov publikovania v plnej verzii CMS
icon: material/clipboard-check
---

# Plánovač projektu stránky

> **Trasa:** Workspace → **Plánovač projektu** (`/platform/project-planner`)  
> **Oprávnenia:** `project-plan:read` (zobrazenie), `project-plan:manage` (vytváranie/úprava)  
> **Nie je Origin Panel.** Origin je pre maintainera; plánovač je v plnej verzii CMS.

Plánovač je **proaktívny**: míľniky a termíny nastavíte *pred* vznikom stránok. [Redakčný kalendár](CONTENT_EDITOR.md) ostáva **reaktívny** — ukazuje obsah, ktorý CMS už pozná (`scheduledAt` / `publishedAt`).

## 1. Vytvorenie plánu

1. Otvorte **Plánovač projektu**.
2. Kliknite **Nový plán**.
3. Zadajte názov (ID sa použije ako súbor `data/project-plans/{id}.json`).
4. Skontrolujte IANA časovú zónu (variance ju používa).
5. Uložte. Predvolené fázy: Prieskum → Obsah → Spustenie.

Jeden plán môže byť **predvolený**. Celkový progres na zozname agreguje všetky plány.

## 2. Položky

V pláne **Pridať položku**:

| Pole | Poznámka |
|------|----------|
| Typ obsahu (čipy) | **Stránka** (+14 d), **Článok** (+7 d), landing (+21 d), médiá (+10 d), newsletter (+7 d), vlastné (bez predvoleného termínu) |
| Názov | Povinný pri jednej položke; pri balíku voliteľný (použije sa názov typu) |
| Balík míľnikov | Počet 1–20 s jedným termínom — napr. 5 článkov do konca marca |
| Fáza | Voliteľné zoskupenie |
| Termín | Predvyplní šablóna typu; dá sa zmeniť |
| Stav | `planned` → `in_progress` → `done` (alebo `skipped` / `blocked`) |

Progres %: hotové = 100 %, prebieha = 50 %, naplánované/blokované = 0 %, preskočené mimo menovateľa.

## 3. Odznaky variance

| Odznak | Význam |
|--------|--------|
| Načas | Hotové v ten istý kalendárny deň ako termín |
| Skôr / neskôr | Hotové pred / po termíne (zóna plánu) |
| Po termíne | Nie je hotové a termín už prešiel |
| Čoskoro | Nie je hotové a termín je do 7 dní |

## 4. Nastavenia

**Nastavenia → Stránka → Plánovač projektu.** Predvolene **zapnuté**. Vypnutie vráti API 404 a skryje položku v menu.

Položku prepojíte na stránku alebo článok. Po publikovaní sa stav zmení na **hotové**. KPI (meškanie / čoskoro) sú na dashboarde.

## 5. Súvisiace

- Architektúra: [PROJECT_PLANNER.md](../../en/architecture/PROJECT_PLANNER.md)
- Špecifikácia: [ITERATION_87.md](../ITERATION_87.md)
- Oprávnenia: [ACCESS_CONTROL.md](ACCESS_CONTROL.md)
