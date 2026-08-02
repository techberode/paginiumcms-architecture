---
title: Príručka používateľa
description: Vstupný bod pre nasadenie, administráciu, editovanie obsahu a beta testovanie PaginiumCMS
icon: material/book-open-page-variant
---

# Príručka používateľa PaginiumCMS

> Táto vetva dokumentácie opisuje používateľské a administrátorské workflow pre release rodinu **`v2.1.0-beta.*`**. Presný tag inštalácie vždy over v release poznámkach a v `CHANGELOG.md`.

PaginiumCMS sa vyvíja smerom k **Hybrid Headless Content Engineu**, ale povinným zdrojom pravdy zostávajú súbory. Používateľská príručka preto oddeľuje dnešné stabilné beta workflow od schopností plánovaných v It.68–77.

## 1. Ako používať príručku

| Poradie | Dokument | Pre koho |
|---|---|---|
| 1 | [Inštalácia](INSTALLATION.md) | správca servera, maintainer, beta tester |
| 2 | [Prvé kroky](FIRST_STEPS.md) | nový administrátor alebo editor |
| 3 | [Príručka administrátora](ADMIN_GUIDE.md) | každodenná správa CMS |
| 4 | [Editor obsahu](CONTENT_EDITOR.md) | editor stránok a článkov |
| 5 | [Oprávnenia a Path ACL](ACCESS_CONTROL.md) | SUPER_ADMIN a bezpečnostný správca |
| 6 | [Firewall](FIREWALL.md) a [Logy](LOGGING.md) | prevádzka a incident response |
| 7 | [Beta tester](BETA_TESTER.md) | funkčné a bezpečnostné testovanie |

Doplňujúce používateľské príručky pokrývajú [branding](BRANDING.md), [pluginy](PLUGINS.md), [témy](THEMES.md), [Code Editor](CODE_EDITOR.md) a [Developer Mode](DEVELOPER_MODE.md).

## 2. Stavové označenia

| Označenie | Význam |
|---|---|
| **Implementované** | workflow je súčasťou aktuálnej beta vetvy; konkrétny build môže obsahovať opravu alebo menší rozdiel UI |
| **Prechodné** | funkcia existuje, ale kontrakt alebo obrazovka sa ešte konsoliduje |
| **Plánované** | cieľová schopnosť It.68–77; nepovažuj ju za dostupnú bez potvrdenia v release notes |
| **Environment-gated** | zobrazí sa iba pri správnej roli, konfigurácii alebo profile nasadenia |

Ak UI a dokumentácia nesúhlasia, za rozhodujúci považuj konkrétny release, API odpoveď a serverové logy. Rozdiel nahlás ako dokumentačný bug.

## 3. Mentálny model systému

```text
prehliadač / React admin
        ↓ HTTP API
Slim/PHP adaptéry a middleware
        ↓ aplikačné služby
flat-file SSOT
        ↓ odvodené vrstvy
index, cache, audit, Git/preklad/AI joby
```

- Admin panel nie je zdroj pravdy; zobrazuje stav načítaný cez API.
- Autoritatívny obsah a konfigurácia sú v povolených storage cestách.
- Index a cache sa môžu znovu vytvoriť a nesmú nahradiť autoritatívne súbory.
- Git publish, preklady a AI sú oddelené následné kroky, nie automatická súčasť každého uloženia.

## 4. Roly a zodpovednosť

| Rola | Typické použitie | Dôležitá hranica |
|---|---|---|
| `USER` | verejný účet, profil, komentáre podľa nastavení | nemá bežný prístup do administrácie |
| `EDITOR` | stránky, články, médiá a navigácia | nemá spravovať platformové secrets ani bezpečnostnú politiku |
| `ADMIN` | používatelia, nastavenia, schránka, prevádzkové moduly | nemá automaticky obchádzať Path ACL alebo extension policy |
| `SUPER_ADMIN` | RBAC, Path ACL, extensions a kritická konfigurácia | používať iba pre úzky počet dôveryhodných účtov |

Presné oprávnenia môže SUPER_ADMIN upraviť. Názov roly preto nie je jediným dôkazom autorizácie; rozhoduje backendová permission kontrola.

## 5. Bezpečný prevádzkový základ

Pred sprístupnením inštancie mimo lokálnej siete:

1. zmeň bootstrap heslo a zapni 2FA pre staff účty,
2. nastav `APP_ENV=production` a `APP_DEBUG=false`,
3. používaj HTTPS a správne `TRUSTED_PROXIES`,
4. vytvor a otestuj zálohu autoritatívneho storage,
5. nastav cron/worker podľa aktuálneho release,
6. skontroluj firewall, logy, audit a retenciu,
7. odstráň demo/test účty a vypni `DEMO_MODE`,
8. nepoužívaj `chmod 777` ako univerzálnu opravu práv.

## 6. Kam hlásiť problém

- Bežný bug: najprv pozri [ISSUES.md](../ISSUES.md), potom vytvor verejný issue s reprodukciou.
- Bezpečnostný nález: postupuj podľa koreňového `SECURITY.md`; nezverejňuj neopravené zraniteľnosti.
- Dokumentačný rozpor: uveď cestu dokumentu, release tag, obrazovku/endpoint a očakávané správanie.

Do reportu neprikladaj `.env`, session cookie, API key, TOTP secret, privátny kľúč ani neredigovaný log s osobnými údajmi.

## 7. Súvisiaca technická dokumentácia

- [API architektúra](../architecture/API.md)
- [API kontrakt](../architecture/API_CONTRACT.md)
- [Storage](../architecture/STORAGE.md)
- [Verzovanie a konflikty](../architecture/VERSIONING.md)
- [Deployment režimy](../architecture/DEPLOYMENT_MODES.md)
- [Public Beta](../PUBLIC_BETA1.md)
