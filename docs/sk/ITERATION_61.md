---
title: Iterácia 61 – Newsletter, odberatelia a verejný consent UX
description: Viacfázový newsletter od zberu a admin zoznamu po sending, double opt-in, preferences a footer UX.
icon: material/history
---

# Iterácia 61 – Newsletter, odberatelia a verejný consent UX

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené vo fázach 1–5 |
| Release / obdobie | 2.1.0-beta.16–18 (neskoršie fázy) |
| Typ záznamu | historický multi-phase module record |

## Cieľ

Spojiť footer a maintenance odbery do jedného flat-file registra, pridať admin prehľad/export a následne bezpečný sending, double opt-in, unsubscribe, preference management a kultivovaný footer UX.

## Rozsah a výsledok

Základ: `POST /api/newsletter/subscribe`, admin `/newsletter`, deduplikácia a source stĺpec. Phase 1 pridala preferences, consent a dedicated rate limit; Phase 2 weekly digest/new-article mail a scheduler state; Phase 3 double opt-in a HMAC unsubscribe; Phase 4 manage preferences a CMS release campaigns; Phase 5 zjednodušila footer na inline email + modal.

Cookie consent pridal `privacy` settings a podmienil functional storage, vrátane public theme preference. Wiring audit potvrdil osem endpointov, maintenance bypass pre confirm/manage/unsubscribe a sidebar counts.

## Architektonické a bezpečnostné hranice

Subscriber údaje sú osobné údaje: admin list je ADMIN+, CSV export musí byť injection-safe a logy nesmú obsahovať tokeny. Confirm token sa ukladá ako hash s expiry; outbound mail je gated master switchom, batch limitom a preference/status filtrom. Public subscribe potrebuje honeypot, generic response a rate limit.

## Overenie a súvisiace záznamy

Pôvodná medzera admin zoznamu je [ISS-097](ISSUES.md#iss-097). Dedikovaný newsletter abuse hardening je [ISS-107](ISSUES.md#iss-107). Footer polish variant B vyriešil [ISS-109](ISSUES.md#iss-109) a bol dodaný v [v2.1.0-beta.18](../../CHANGELOG.md#release-2-1-0-beta-18); wiring audit je označený `v2.1.0-beta.16`.

## Aktuálna interpretácia

It.61 je uzavretý multi-phase modul, nie iba jednoduchý footer formulár. Budúce kampane musia používať existujúci subscriber status/preferences SSOT a scheduler identity, nie druhý mailing list.
