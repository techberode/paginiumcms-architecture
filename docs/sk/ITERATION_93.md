# Iterácia 93 — Admin v štýle Falcon (chrome + denné aplikácie)

> **Stav:** ⏳ čiastočné (Wave 1–3 chrome + tímy + účet + udalosti + časovač + widgety + katalóg v `beta.77`; ďalej support / pošta)  
> **Kanónická špecifikácia (EN):** [../en/ITERATION_93.md](../en/ITERATION_93.md)  
> **Vzory:** [Falcon dashboard](https://prium.github.io/falcon/v3.26.0/index.html) · [Aurora account](https://aurora.themewagon.com/pages/account) · [Aurora time tracker](https://aurora.themewagon.com/apps/time-tracker) — inšpirácia, nie kópia

Pôvodná It.93 (izolovaný origin) je **zrušená**. Archív: [ISOLATED_ORIGIN.md](../en/architecture/ISOLATED_ORIGIN.md).

## Čo berieme z Falconu / Aurory

| Vzor | U nás |
|------|--------|
| Dashboard mriežka | Naše KPI (stránky, články, médiá, návštevy) + plánovač + disk |
| Analytics | Ten istý `/analytics` (prehľad, stránky, zdroje, zariadenia, geo, boti, 404) — nový vzhľad |
| Management | **Tímy** (editorial / support / ops). RBAC ostáva v rolách |
| Support desk | Tickety + členovia support tímu (základ, potom rozšírenie) |
| App → Email | IMAP schránky **len `@doména-webu`**, len prihlásený; ADMIN+ všetky také účty |
| App → Events | Firemné udalosti (zoznam + vytvorenie). Editoriálny kalendár ostáva na publikovanie obsahu |
| Aurora account | Rozšírený profil používateľa (záložky) |
| Time tracker | Čas na položku projektového plánu alebo udalosť |
| Modules (forms/tables/charts/widgets) | Spoločný admin kit (`AdminOfferCard` katalóg). Nie e-shop, LMS, kanban, sociálne |

Komentáre ostávajú komentármi. Chat z Falconu nerobíme.

## Pošta (tvrdé pravidlá)

Iba session, iba doména webu, IMAP host cez `OutboundUrlGuard`, heslá šifrované, audit čítania, žiadny dump celej pošty do `data/` ako SSOT.

## Wave

1. Chrome + kit (`93a`, `93q`, `93f`, `93g`, `93r`, `93s`) — farby menu/lišty, gradient, horné menu, Použiť/Uložiť náhľad  
2. Dashboard + analytika + zoznamy  
2b. **Widgety na verejný web** (`93t`) — vizuálny katalóg, nie kópia CoreUI; markdown `[widget]`  
3. Tímy, účet, udalosti, time tracker  
4. Support základ  
5. Domain mail (`93m` posledné — bezpečnosť)
