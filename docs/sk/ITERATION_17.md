---
title: Iterácia 17 – Zákon API ↔ frontend scaffoldingu
description: Historický záznam pravidla endpoint–typed client–consumer–dokumentácia a postupnej migrácie
icon: material/history
---

# Iterácia 17 – Zákon API ↔ frontend scaffoldingu

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | 🟡 Čiastočne dokončené; MVP Wave 5e dodané |
| Release / obdobie | 2.0.9 + 2.0.55 Wave 5e |
| Typ záznamu | historická procesná iterácia |

## Zákon

```text
endpoint + middleware
→ controller/application service
→ typed frontend API module
→ reálny consumer
→ API dokumentácia
→ backend + frontend test
```

Server-only CLI/worker/scheduler môže byť výnimkou, ale musí byť explicitne zdokumentovaná.

## Dodaný rozsah

Typed moduly vznikali postupne; 2.0.9 opravil `content.ts`, `user.ts` a barrel. Wave 5e dodala kompletné exporty `api/index.ts`, `CONTRIBUTING.md`, `lint-api-barrel` a CI krok.

## Zostávajúci dlh

Zdroj uvádza raw `useApi`/`apiClient.get` consumery, scaffold wizard „Nový doplnok“ a úplný endpoint inventory ako nedokončené. Neskoršia It.05 dokumentácia už výrazne obnovila API inventár, ale úplnú migráciu všetkých komponentov zdroj nepotvrdzuje.

## Chronologická poznámka

Pôvodný text uvádzal tento zákon ako prerequisite pre It.15, hoci plugin runtime bol medzičasom dodaný. Aktuálna interpretácia: nové alebo menené extension endpointy musia zákon dodržať, existujúci legacy dlh sa migruje postupne.

## Súvisiace

MVP detail: [ITERATION_17E.md](ITERATION_17E.md). Contributing workflow: [CONTRIBUTING.md](developer/CONTRIBUTING.md).

