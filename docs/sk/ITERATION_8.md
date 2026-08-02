---
title: Iterácia 8 – Media Manager vo frontende
description: Historický záznam admin media knižnice, editora, media pickeru a same-origin API opravy
icon: material/history
---

# Iterácia 8 – Media Manager vo frontende

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené; neskôr rozšírené |
| Release / obdobie | 2.0.4 + post-2.0.20 editor doplnenia |
| Typ záznamu | historická frontend iterácia |

## Cieľ

Prepojiť existujúce `/api/media` s reálnym admin UI, drag-and-drop uploadom, úpravou alt textu, media pickerom pre editor/SEO a opraviť same-origin API URL pri LAN deployi.

## Frontend

| Súbor | Úloha |
|---|---|
| `src/api/media.ts` | List, upload, patch, delete a URL helpery |
| `MediaManager.tsx` | Grid/list UI, upload, metadata, delete |
| `MediaPickerModal.tsx` | Výber obrázka pre editor a OG/thumbnail |
| `SeoMetadataPanel.tsx` | SEO media integration |
| `MarkdownEditor.tsx` | Markdown/WYSIWYG toggle + insert media |
| `apiBaseUrl.ts` | Same-origin fallback bez `VITE_API_URL` |

## API a nasadenie

Všetky media routy vyžadujú session autentifikáciu. Nginx musí proxyovať `/api` pred SPA fallbackom; inak klient dostane HTML namiesto JSON. Aktuálne pravidlá sú v [NGINX_API.md](deploy/NGINX_API.md).

## Neskoršie rozšírenia

Full DAM funkcie (adresáre, bulk operácie) boli dodané neskôr. Monaco editor a Developer unlock pribudli po 2.0.20 a sú podrobnejšie popísané v [Iterácii 16](ITERATION_16.md).

## Testy

PHPUnit pokrýval repository/controller; Vitest media API helpery, manager UI a same-origin resolver.

