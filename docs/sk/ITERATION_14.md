---
title: Iterácia 14 – Code Policy a základ Code Editora
description: Historický záznam bezpečnostnej politiky pre editáciu kódu a whitelist filesystem prístupu
icon: material/history
---

# Iterácia 14 – Code Policy a základ Code Editora

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dodané; pôvodný tag bol v zdroji pending |
| Release / obdobie | historický zdroj uvádza 2.0.3 pending; funkcia je neskôr potvrdená |
| Typ záznamu | historická bezpečnostná iterácia |

## Cieľ

Opraviť path resolution Code Editora, pridať `CodePolicyEngine`, bezpečnostný token scanner, Developer Mode unlock a whitelist ciest kompatibilný s extension architektúrou.

## Backend

| Oblasť | Implementácia |
|---|---|
| Policy | veľkosť, syntax, forbidden funkcie, namespace a strict-mode pravidlá |
| Scanner | PHP token scan (`T_EVAL`, function calls) |
| Chyby | `CodePolicyViolationException` → HTTP `422` s grouped errors |
| Editor | normalizácia ciest, project root, backup, typed `FileInfo[]` |
| Whitelist | Modules, Http/Extensions, theme views, config |
| Forbidden | Core, bootstrap, vendor |

## Frontend

`developer.ts`, `DeveloperUnlockGate.tsx` a `CodeEditor.tsx` poskytli TOTP/dev-token unlock a zobrazenie policy chýb pri ukladaní.

## Aktuálna bezpečnostná interpretácia

Scanner je write-time ochranná vrstva, **nie sandbox**. Nemôže bezpečne spúšťať nedôveryhodný PHP kód v rovnakom procese. Aktuálne pravidlá importu, symlinkov, ZIP a AI-generovaného kódu sú v [EXTENSION_CODE_POLICY.md](developer/EXTENSION_CODE_POLICY.md).

## Nadväznosť

Plugin runtime vznikol v It.15 a full-stack editor v It.16.

