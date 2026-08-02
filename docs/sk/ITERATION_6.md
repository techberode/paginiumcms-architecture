---
title: Iterácia 6 – Notifikácie, analytika a autentifikačné UI
description: Historický záznam konektorov, visit analytics, incident alertov a kompletných auth flow
icon: material/history
---

# Iterácia 6 – Notifikácie, analytika a autentifikačné UI

> **Historický záznam dodávky.** Dokument opisuje rozsah iterácie v čase jej realizácie a zahŕňa aj neskoršie opravy, ktoré boli k pôvodnému záznamu doplnené. Pre aktuálne architektonické pravidlá majú prednosť dokumenty v `docs/architecture/`, bezpečnostná politika, `ISSUES.md` a aktuálny release kontrakt.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.1+ |
| Typ záznamu | historická funkčná iterácia |

## Cieľ

Prepojiť notifikačné kanály, analytiku návštevnosti, incident alerty, nastavenia toastov a kompletné prihlasovacie/obnovovacie flow medzi backendom a SPA.

## Dodaný rozsah

| Oblasť | Hlavné prvky |
|---|---|
| Settings | `smtp`, `notifications`, `connectors`, `monitoring` |
| Notifikácie | `SmtpTransport`, `NotificationFactory`, `IncidentNotifier`, channel adaptéry |
| Analytika | `Reporter`, `AnalyticsManager`, `AnalyticsMiddleware`, admin API |
| Auth | reset hesla cez mail, odstránenie demo tokenu z produkčných odpovedí, alert pri failed login |
| Frontend | `/notifications`, toast z public settings, login/register/forgot/reset routes, change-password modal |

## Konfigurácia a overenie

Na serveri bolo potrebné nastaviť SMTP, zapnúť vybrané konektory, monitoring alerty a toast správanie. Overenie pozostávalo z testu konektora na `/notifications`, password-reset flow a kontrolovaného toast debug režimu.

Aktuálne secret fields musia byť šifrované a outbound URL musia prejsť `OutboundUrlGuard`; pôvodný quick-start sa nemá chápať ako úplný produkčný hardening.

## Testy

Pribudli `NotificationFactoryTest`, `IncidentNotifierTest`, frontend testy notifikačných nastavení a aktualizované auth testy. Nadväzujúce plánované schopnosti boli neskôr rozdelené do samostatných iterácií.

