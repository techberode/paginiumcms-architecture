# Iterácia 96 — Knižnica dokumentov a file manager

> **Stav:** ⏳ plánované (18. 9. 2026)  
> **Priorita:** 🟡 **P1 produkt**  
> **Anglická špecifikácia:** [en/ITERATION_96.md](../en/ITERATION_96.md)

Rozšírenie **Media Library** o dokumenty (PDF, text, OpenDocument, Office Open XML): upload podľa **It.78** profilu `documents`, bezpečné servírovanie (attachment, žiadny inline XSS), sťahovanie, metadata, voliteľná **editácia textu** v admin, prepojenie na obsah.

**Už hotové pred 96:** tlačidlo **Stiahnuť súbor** v médiách (`GET /api/media/file/…?download=1`).

Slice **96a–96f** — pozri EN dokument (allow-list, UI file manager, editor textu, PDF náhľad, integrácia do obsahu, nastavenia politiky).
