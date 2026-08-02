---
title: Iterácia 9 – Port prototypového backendu a napojenie admin frontendu
description: Historický záznam migrácie prototypových endpointov na Slim moduly a reálne admin/public UI
icon: material/history
---

# Iterácia 9 – Port prototypového backendu a napojenie admin frontendu

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.5 |
| Typ záznamu | historická integračná iterácia |

## Cieľ a číslovanie

Portovať legacy `prototype/backend` skripty na typované Slim routy pod `/api/*` s flat-file úložiskom. Staré roadmapy používali číslo 9 aj pre SEO; skutočne dodaný SEO engine patrí do [It.23](ITERATION_23.md).

## Dodaný rozsah

| Modul | Verejné/admin API | Úložisko |
|---|---|---|
| Navigácia | `GET /api/navigation`, `PUT /api/admin/navigation` | `data/navigation.json` |
| Komentáre | public list/submit + admin moderation | `data/comments.json` |
| Kontakt/Správy | `POST /api/contact`, admin inbox | `data/messages/{id}.json` |
| GitHub sync | status/export/import/sync/auto-sync | GitHub service + env konfigurácia |

## Frontend

`navigation.ts`, `comments.ts`, `contact.ts`, `messages.ts` a `github.ts` dostali reálnych consumerov: Navigation Manager, Comments Manager, Messages Viewer, GitHub Sync Panel a public komponenty. Insecure prototypové mocky, debug toast endpointy a SMTP secrets boli zámerne vynechané.

## Nastavenia, testy a deploy

Pribudla comments settings skupina a repository/controller testy. Nasadenie nevyžadovalo databázovú migráciu; same-origin `/api` proxy bolo zdokumentované v [NGINX_API.md](deploy/NGINX_API.md).

## Manuálny smoke

Reorder navigácie, submit→approve komentára, contact→admin inbox a GitHub status/export pri nastavenej env konfigurácii tvorili historický acceptance test.

