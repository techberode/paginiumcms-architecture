---
title: Iterácia 1 – Systém zamykania obsahu
description: Historický záznam systému zámkov, heartbeat mechanizmu a riešenia súbežnej editácie
icon: material/history
---

# Iterácia 1 – Systém zamykania obsahu

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.6 – základ jadra |
| Typ záznamu | historická základná iterácia |

## Cieľ

Zabrániť tomu, aby dvaja používatelia súčasne upravovali ten istý dokument bez vedomia o konflikte. Iterácia zaviedla flat-file register zámkov, klientsky heartbeat a serverové automatické uvoľnenie po vypršaní TTL.

## Dodaný rozsah

| Vrstva | Implementácia |
|---|---|
| Model | `Core/Locking/Models/ContentLock.php` – vlastník, token, heartbeat a expirácia |
| Kontrakt | `LockManagerInterface.php` |
| Služba | `LockManager.php` nad `data/locks.json` s `flock(LOCK_EX)` |
| Konflikt | `LockConflictException.php` → HTTP `409` s kontextom zámku |
| HTTP | `LockController.php` + automaticky objavené routy |
| Frontend | `src/api/locks.ts`, `useContentLock.ts`, `LockIndicator.tsx` |

## Prevádzkové parametre

| Parameter | Historická hodnota |
|---|---|
| Heartbeat klienta | 30 sekúnd |
| Serverové TTL | 300 sekúnd |
| Register | `backend/storage/app/content/data/locks.json` |

Základné routy: `POST /api/locks/acquire`, `heartbeat`, `release` a administrátorský `GET /api/locks`. Konflikt používa štandardný `409` envelope popísaný v [API_CONTRACT.md](architecture/API_CONTRACT.md).

## Overenie a nadväznosť

Testovanie pokrývalo správcu zámkov, HTTP controller a integráciu dashboardového panelu. Iterácia tvorí pesimistickú vrstvu koordinácie; optimistická kontrola revízie a drafty pribudli v [Iterácii 2](ITERATION_2.md).

