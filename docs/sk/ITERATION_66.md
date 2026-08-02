---
title: Iterácia 66 – Write-time security gate a test balíky
description: Fail-closed kontrola ne-dôveryhodných zápisov a rozšírenie 21-krokového security test gate-u.
icon: material/history
---

# Iterácia 66 – Write-time security gate a test balíky

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.1.0-beta.22 |
| Typ záznamu | historický security hardening record |

## Cieľ

Posilniť bezpečnosť pri save/import/CI bez pridania ťažkých kontrol na anonymnú public GET cestu.

## Rozsah a výsledok

Code Editor začal volať `validateUntrusted` pre untrusted paths a tieto cesty ostali fail-closed aj pri vypnutom `codePolicy.enabled`. Iterácia zdokumentovala povinný kontrakt pre budúce 58d shortcode saves.

`run-all-tests.zsh` sa rozšíril na 21 krokov; vznikli `security-regression.sh` a `security-static-grep.sh` pre CodePolicy, XSS/ZIP/headers, outbound hygiene a frontend security. Ops checklist pokrýval HTTPS, APP_ENV/CORS a dependency disposition.

## Architektonické a bezpečnostné hranice

Scanner nie je sandbox. Gate musí bežať pred zápisom/aktiváciou, error skončí bez artifact write a public path ostáva iba deterministic render/cache. Deferred media re-encode a HMAC manifest seals neboli súčasťou dokončenej fázy.

## Overenie a súvisiace záznamy

Release: [v2.1.0-beta.22](../CHANGELOG.md#release-2-1-0-beta-22). Zdroj odkazuje na [ISS-008](ISSUES.md#iss-008), [ISS-014](ISSUES.md#iss-014) a [ISS-089](ISSUES.md#iss-089). Neskoršia ochrana secrets v CI logoch je samostatný [ISS-120](ISSUES.md#iss-120), nie pôvodný rozsah It.66.

## Aktuálna interpretácia

It.66 je dokončený write-time/test baseline. Produktové zapojenie shortcode/theme/module surfaces ostáva v It.67 a nesmie sa označiť za hotové iba preto, že security helper a test pack už existujú.
