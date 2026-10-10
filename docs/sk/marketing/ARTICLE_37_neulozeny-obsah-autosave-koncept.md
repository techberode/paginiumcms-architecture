---
title: "Nezabudnete na rozpracovaný článok: autosave koncept a pripomienka v admin"
slug: neulozeny-obsah-autosave-koncept
perex: "Nová stránka alebo článok sa ukladá na pozadí ako koncept — aj bez názvu. Pri návrate do zoznamu alebo na stôl uvidíte, že máte rozpracovanú prácu."
kategoria: Novinky
tagy: paginiumcms, editor, koncept, autosave, stranky, clanky, administracia
serie: "PaginiumCMS po beta.96"
serie_diel: 37
release_anchor: "Unreleased (pred ďalším beta tagom)"
status: draft
---

# Nezabudnete na rozpracovaný článok: autosave koncept a pripomienka v admin

*Naväzuje na editor s autosave pre existujúci obsah (diel 13). Tento diel rieši **prvý klik na „Nová stránka / Nový článok“**, kým ešte neexistuje záznam na serveri.*

Redaktor otvorí editor, napíše odsek, telefon zazvoní — a pol hodiny neskôr si nie je istý, či sa niečo uložilo. PaginiumCMS už roky ukladá **koncepty** pri editácii existujúcich stránok. Teraz to isté platí aj pre **úplne nový** záznam, ktorý ste ešte nikdy neuložili tlačidlom Uložiť.

## Čo sa deje na pozadí

1. Po **Vytvoriť stránku / článok** systém priradí dočasný identifikátor (`koncept-DDMMYYYY-…`) a zapne rovnaký **autosave** interval ako pri bežnej editácii (nastaviteľný v **Nastavenia → Obsah**).
2. Do flat-file konceptu (`data/drafts/`) sa ukladá titulok, telo a voliteľne celý stav editora (locale, SEO, šablóna — ak máte zapnuté *draftFullEditorState*).
3. Ak **názov** necháte prázdny, koncept dostane názov z **prvých slov tela** alebo záložný tvar **`koncept_DDMMYYYY`**.

Žiadna magická publikácia: návštevníci na webe nič nevidia, kým neuložíte stránku/článok normálne (stav draft/published podľa workflow).

## Kde vás systém pripomenie

| Miesto | Správanie |
|--------|-----------|
| **Stránky / Články** | Banner: pokračovať v práci alebo koncept odstrániť |
| **Stôl** (bublina / notifikácia) | Položka *Neuložená stránka* / *Neuložený článok* — klik otvorí editor |
| **Editor** | Po návrate na rovnaký koncept sa obnoví rozpracovaný obsah |

Po **prvom úspešnom uložení** sa dočasný koncept zmaže a pracujete už s bežným slugom obsahu.

<!-- SCREENSHOT: Banner na zozname Stránky s tlačidlami Pokračovať / Odstrániť; anonymizovať titulky. -->
<!-- SCREENSHOT: Fronta stola s oranžovým štítkom Neuložený obsah a náhľadom názvu konceptu. -->

## Pre koho je to dôležité

- **Agentúry** s viacerými rozpracovanými landingmi naraz.
- **Redakcie**, kde editor skúša text pred schválením názvu a URL.
- **Jeden prevádzkovateľ**, ktorý nechce stratiť polotovar pri náhodnom zatvorení karty.

## Technická pravda (pre autora článku)

- API: `GET /api/drafts/pending`, existujúce `PUT/GET/DELETE /api/drafts/{type}/{slug}`.
- Vyžaduje oprávnenie **content:edit**; koncepty nie sú verejné.
- Verejná dokumentácia: [CHANGELOG.md](https://github.com/techberode/paginiumcms-architecture/blob/main/CHANGELOG.md) (Unreleased), editor autosave v [ITERATION_2](https://github.com/techberode/paginiumcms-architecture/blob/main/docs/en/ITERATION_2.md) tradícia.

**CTA ďalší diel:** [Rýchlejší admin pri viacerých editoroch](rychlejsi-admin-cache-dashboard.md) — cache dashboardu a čo to znamená pre váš tím.
