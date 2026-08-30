# Iterácia 87 — Plánovač projektu stránky a dokončenie UX auditu

> **Stav:** ⏳ plánované — **prvá produktová iterácia po stabilnom vydaní** (po It.25 + tag `v2.2.0`)  
> **Priorita:** 🟡 **P1** (Plánovač projektu — plná verzia CMS); 🟡 **P2** (UX audit z It.86d)  
> **Kanónická špecifikácia (EN):** [../en/ITERATION_87.md](../en/ITERATION_87.md)

## Cieľ

1. **Track A (`87a`–`87d`):** dokončiť odložené položky frontend auditu (srcset, skeletony, empty states, onboarding).
2. **Track B (`87e`–`87j`):** **Plánovač projektu stránky** — admin modul pre majiteľov webu a editorov na plánovanie míľnikov, termínov publikovania a sledovanie progresu (včas / meškanie / skoršie dokončenie). V **plnej verzii CMS**, nie env-gated ako Origin Panel.
3. **Track C (`87k`–`87m`):** **Voliteľné** — allow-list statických `.js` len z `themes/{id}/assets/` so **SRI + CSP hash/nonce**; nie „pustiť všetko“ (žiadny inline script, žiadne CDN, default vypnuté).

## Tri panely — nezamieňať

| Modul | Pre koho | Účel |
|-------|----------|------|
| **Origin Panel** (It.82) | Maintainer | Progres vývoja, deploy, probes |
| **Editoriálny kalendár** (It.81d) | Editori | Čo je už naplánované / publikované v obsahu |
| **Plánovač projektu** (It.87) | Editor, admin, majiteľ projektu | Proaktívny plán webu pred vznikom obsahu |

## Priorita a poradie

| Sub | Názov | Priorita |
|-----|-------|----------|
| **87e** | Flat-file schéma plánu | 🟡 P1 |
| **87f** | API + RBAC | 🟡 P1 |
| **87g** | Panel plánovača (UI) | 🟡 P1 |
| **87h** | Termíny podľa typu obsahu | 🟡 P1 |
| **87i** | Prepojenie na content + auto stav | 🟡 P2 |
| **87j** | Widgety (meškanie, tento týždeň) | 🔵 P2 |
| **87a–87d** | UX audit | 🟡 P2 / 🔵 P3 |
| **87k–87m** | Téma: allow-list JS + SRI + CSP | 🔵 P3 (voliteľné) |

Odporúčané: `87e → 87f → 87g → 87h`, potom zvyšok. Track C až po MVP plánovača alebo ako samostatný patch.

## Plná verzia vs Origin

- **Plánovač** je súčasť štandardného CMS balíka (nie vylúčený z customer archívu).
- **Origin Panel** zostáva len pre maintainera (`ORIGIN_PANEL=true`).

Detail API, dátový model, bezpečnosť a Definition of Done → [anglická špecifikácia](../en/ITERATION_87.md).
