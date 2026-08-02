---
title: Iterácia 47 – Prihlasovacie údaje notifikačných konektorov
description: Doplnenie Bearer/Basic autentifikácie pre ntfy a bezpečný test konektora.
icon: material/history
---

# Iterácia 47 – Prihlasovacie údaje notifikačných konektorov

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Implementované; pôvodný presný release nepotvrdený |
| Release / obdobie | pôvodný target 2.0.25 |
| Typ záznamu | historický bezpečnostný delivery record |

## Cieľ

Doplniť chýbajúcu autentifikáciu konektorov, najmä Bearer token alebo Basic auth pre privátne a self-hosted ntfy topicy, a zaviesť test pripojenia bez vytvárania falošného incidentu.

## Rozsah a výsledok

Settings skupina `connectors` pridala režim `none|token|basic`, ntfy token, username/password a voliteľný názov webhook auth hlavičky. `NtfyAdapter` a `NotificationFactory` mali zostaviť správne hlavičky; admin API poskytlo test konektora a stav autentifikácie.

Frontend pridal password polia, režim autentifikácie, test tlačidlo a stavový badge. Testy pokrývali Bearer, Basic aj no-auth vetvu.

## Architektonické a bezpečnostné hranice

Test endpoint má byť rate-limited a auditovaný bez secret hodnôt. Credentials nesmú ísť do plaintext logov; neskorší security baseline ich zahŕňa do encryption-at-rest cez `EncryptionService`. Outbound URL stále podlieha SSRF guardu.

## Overenie a súvisiace záznamy

Aktuálny register uvádza ntfy autentifikáciu ako opravenú v [ISS-013](ISSUES.md#iss-013). Zdroj používa označenie „Implemented (Unreleased)“ a target 2.0.25, preto presný prvý tag nie je z tohto dokumentu potvrdený.

## Aktuálna interpretácia

It.47 je dokončený základ konektorovej autentifikácie. Ďalšie providery musia používať rovnaký secret, outbound, rate-limit a audit kontrakt namiesto paralelných ad-hoc implementácií.
