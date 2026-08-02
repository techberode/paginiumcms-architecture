---
title: Iterácia 50 – Interný mikro-firewall
description: Dodávka ľahkého PHP WAF s incidentmi, jailom, banmi a admin rozhraním.
icon: material/history
---

# Iterácia 50 – Interný mikro-firewall

> **Historický záznam dodávky.** Dokument opisuje iteráciu v stave zachytenom v zdrojovom archíve z 2. augusta 2026. Neskoršie opravy, konsolidácie a zmeny smerovania sú uvedené oddelene. Pre aktuálny kontrakt majú prednosť dokumenty v `architecture/`, `developer/`, `ISSUES.md`, `CHANGELOG.md` a Hybrid Engine vlna.

| Pole | Hodnota |
|---|---|
| Stav | ✅ Dokončené |
| Release / obdobie | 2.0.26 |
| Typ záznamu | historický security delivery record |

## Cieľ

Pridať skorú obrannú vrstvu pred Slim routingom, ktorá rozpozná bežné probe, traversal a podozrivé URI/query/UA vzory, zapíše incident a uplatní dočasný alebo trvalý ban.

## Rozsah a výsledok

Dodávka zahŕňala scenario registry, scanner, flat-file ban store, incident ring buffer, middleware, admin API a React `FirewallManager`. Obrana rozlišovala detekciu, jail a permanentný lock; whitelist a manuálny unban mali prednosť.

Settings riadili master toggle, jail čas, retry threshold, permanent threshold, response mode a retenciu. Testy pokrývali false positives, expiráciu, eskaláciu, middleware aj admin-only operácie.

## Architektonické a bezpečnostné hranice

Editor body nesmie byť skenovaný jednoduchými SQL/XSS regexmi bez kontextu. Tarpit je defaultne vypnutý, proxy IP sa rieši cez trusted proxies a verejný výrez nesmie odhaliť ban registre. Historický návrh spomínal voliteľné SQLite úložisko; to je v rozpore s dnešným No-SQL mandátom a nie je aktuálnou cestou.

## Overenie a súvisiace záznamy

Release je [2.0.26](../CHANGELOG.md#release-2-0-26). Neskorší audit odhalil medzeru v POST/JSON body skene a uzavrel ju v [ISS-056](ISSUES.md#iss-056). Používateľský kontrakt je v [user/FIREWALL.md](user/FIREWALL.md).

## Aktuálna interpretácia

It.50 je implementovaný základ, nie náhrada nginx/host firewallu, rate limitu alebo bezpečnej validácie vstupov. Aktuálny kontrakt ostáva flat-file first a fail-safe.
