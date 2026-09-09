# Iterácia 88 — Theme Studio (Monaco, politika, náhľad, náhľadový obrázok)

> **Stav:** ⏳ plánované — nový produktový slice (9. 9. 2026)  
> **Kanónická špecifikácia (EN):** [../en/ITERATION_88.md](../en/ITERATION_88.md)

## Cieľ

Admin editor vlastnej témy/šablóny: **Monaco** (HTML/CSS/voliteľné JS + manifest), **validácia** rovnakou Code Policy ako ZIP import, **sandbox náhľad**, **uloženie thumbnailu**, a **normalizácia** surového vloženého kódu na balík použiteľný v CMS.

Nie je to pixel builder a nie je to „vlož Themeforest HTML a spusti ho ako je“.

## Zhrnutie sliceov

| ID | Čo |
|----|-----|
| **88a** | Admin shell, Monaco záložky |
| **88b** | `POST …/themes/validate` → markery, bez zápisu |
| **88c** | `POST …/themes/normalize` — strip/rewrite + report |
| **88d** | Sandbox iframe náhľad |
| **88e** | Thumbnail upload / voliteľný capture |
| **88f** | Mapovanie slotov header/main/footer |
| **88g** | Uloženie balíka + aktivácia (existujúce theme API) |

JS záložka až po **87k–m**. Dovtedy sa skripty pri normalizácii **zahodia**.

Poradie: `88b → 88a → 88d → 88c → 88g → 88e → 88f`.
