# Iterácia 93 — Admin chrome + denné aplikácie

> **Stav:** ✅ hotové (Wave 1–5 v strome, vrátane **93l-2** interné poznámky / SLA)
> **Kanónická špecifikácia (EN):** [../en/ITERATION_93.md](../en/ITERATION_93.md)

Pôvodná It.93 (izolovaný origin) je **zrušená**. Archív: [ISOLATED_ORIGIN.md](../en/architecture/ISOLATED_ORIGIN.md).

## Čo pridávame

| Oblasť | U nás |
|--------|--------|
| Dashboard mriežka | Naše KPI (stránky, články, médiá, návštevy) + plánovač + disk |
| Analytics | Ten istý `/analytics` (prehľad, stránky, zdroje, zariadenia, geo, boti, 404) — nový vzhľad |
| Management | **Tímy** (editorial / support / ops). RBAC ostáva v rolách |
| Support desk | Tickety + členovia support tímu (základ, potom rozšírenie) |
| Pošta | IMAP schránky **len `@doména-webu`**, len prihlásený; ADMIN+ všetky také účty |
| Udalosti | Firemné udalosti (zoznam + vytvorenie). Editoriálny kalendár ostáva na publikovanie obsahu |
| Účet | Rozšírený profil. **93o-2–8:** sociálne overenie, karty, smerovanie, Messenger stôl, odpovede na komentáre, odosielanie odpovedí z tímovej/operátorskej schránky na doméne, externý tím + jednorazový invite, karty Používatelia / Nový používateľ / Jednorazová registrácia. Bublina stola: zapnúť/vypnúť a umiestnenie na Verejnej karte, ťahaním alebo PiP. |
| Time tracker | Čas na položku projektového plánu alebo udalosť |
| Moduly (forms/tables/charts/widgets) | Spoločný admin kit (`AdminOfferCard` katalóg). Nie e-shop, LMS, sociálne. Support Kanban je naša tabuľa. |

Komentáre ostávajú komentármi. **93o-3** nie je nový WebSocket chat — návštevnícke správy z kariet padajú do existujúcej schránky Správ (`channel=staff-chat`).

## Pošta (tvrdé pravidlá)

Iba session, iba doména webu, IMAP host cez `OutboundUrlGuard`, heslá šifrované, audit čítania, žiadny dump celej pošty do `data/` ako SSOT.

## Wave

1. Chrome + kit (`93a`, `93q`, `93f`, `93g`, `93r`, `93s`) — farby menu/lišty, gradient, horné menu, Použiť/Uložiť náhľad  
2. Dashboard + analytika + zoznamy  
2b. **Widgety na verejný web** (`93t`) — vizuálny katalóg; markdown `[widget]`  
3. Tímy, účet, udalosti, time tracker  
4. Support základ  
5. Domain mail (`93m` posledné — bezpečnosť). **93m-2** UTF-8 v sanitizéri je hotové. Admin URL: `/mail`, `/kanban` (nie `/platform/…`).
