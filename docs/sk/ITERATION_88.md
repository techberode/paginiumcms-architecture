# Iterácia 88 — Theme Studio (Monaco, politika, náhľad, náhľadový obrázok)

> **Stav:** ⏳ prebieha — **88a hotové** (10. 9. 2026); ostáva 88b–88g  
> **Kanónická špecifikácia (EN):** [../en/ITERATION_88.md](../en/ITERATION_88.md)

## Cieľ

Admin editor vlastnej témy/šablóny: **Monaco** (HTML/CSS/voliteľné JS + manifest), **validácia** rovnakou Code Policy ako ZIP import, **sandbox náhľad**, **uloženie thumbnailu**, a **normalizácia** surového vloženého kódu na balík použiteľný v CMS.

Nie je to pixel builder a nie je to „vlož Themeforest HTML a spusti ho ako je“.

## Zhrnutie sliceov

| ID | Čo |
|----|-----|
| **88a** | Admin shell, Monaco záložky | ✅ |
| **88b** | `POST …/themes/validate` → markery, bez zápisu | ✅ |
| **88c** | `POST …/themes/normalize` — strip/rewrite + report |
| **88d** | Sandbox iframe náhľad |
| **88e** | Thumbnail upload / voliteľný capture |
| **88f** | Mapovanie slotov header/main/footer |
| **88g** | Uloženie balíka + aktivácia (existujúce theme API) |

JS záložka až po **87k–m** (infra už je v Core). **88b** otvorí JS na lokálnu úpravu + validate; zápis ostáva 88g.

Kanónický popis 88a: [en/ITERATION_88.md](../en/ITERATION_88.md) (API `GET …/files` a `GET …/file`, RBAC `themes:read` / `themes:edit`).

Poradie: `88b → 88a → 88d → 88c → 88g → 88e → 88f`.
