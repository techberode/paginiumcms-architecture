---
title: Oprávnenia a Path ACL
description: RBAC, obsahové cesty, least privilege a overenie autorizácie
icon: material/shield-account
---

# Oprávnenia a Path ACL

> PaginiumCMS používa globálne permissions a voliteľné obmedzenia podľa cesty. Zmenu policy má vykonávať iba dôveryhodný `SUPER_ADMIN` so zapnutou 2FA.

## 1. Dve vrstvy autorizácie

| Vrstva | Účel |
|---|---|
| RBAC / permissions | povoľuje doménovú akciu, napríklad `content:edit` alebo `media:upload` |
| Path ACL | zužuje povolenie na konkrétny strom flat-file obsahu |

Používateľ musí prejsť oboma vrstvami, ak je Path ACL aktívne. Frontendový route guard nie je tretia bezpečnostná vrstva; je iba UX doplnok.

## 2. Roly

| Rola | Predvolený zámer |
|---|---|
| `USER` | verejné a profilové capability |
| `EDITOR` | tvorba a úprava obsahu a médií |
| `ADMIN` | správa platformy a používateľov |
| `SUPER_ADMIN` | plný administratívny a policy prístup |

SUPER_ADMIN bypass je silná výnimka. Účet používaj iba na policy/extension úlohy, nie na bežné písanie článkov.

## 3. Permission katalóg

Aktuálny build môže obsahovať napríklad:

| Permission | Význam |
|---|---|
| `content:view` | čítanie povoleného obsahu |
| `content:create` | vytvorenie obsahu |
| `content:edit` | editácia, drafty, locky |
| `content:delete` | soft/permanent delete podľa endpointu |
| `content:manage` | umbrella capability pre obsah |
| `media:upload` | upload média |
| `media:delete` | mazanie média |
| `media:manage` | umbrella capability pre médiá |
| `user:manage` | správa používateľov |
| `settings:manage` | správa povolených nastavení |
| `logs:view` | čítanie prevádzkových logov |
| `profile:edit` | vlastný profil |

Za kanonický zoznam považuj backend `PermissionCatalog`/API metadata konkrétneho release. Dokumentácia nesmie byť jediným zdrojom názvov.

## 4. Predvolená policy

Predvolené mapovanie má byť least-privilege bootstrap. Po prvej inštalácii ho skontroluj a neudeľuj editorovi `settings:manage` iba kvôli jednej chýbajúcej obrazovke.

Pri zmene mapovania:

1. exportuj alebo zálohuj aktuálne nastavenia,
2. zmeň jednu rolu,
3. ulož a over runtime reload,
4. otestuj novú session danej roly,
5. over priamy API request,
6. skontroluj audit.

## 5. Path ACL rozsah

Path ACL je určené pre obsahové cesty, typicky:

```text
content/pages/{slug}
content/blog/{slug}
content/media/{folder-or-file}
```

Nesmie sa používať ako univerzálny filesystem firewall pre `.env`, logy, backupy alebo zdrojový kód. Tie chráni deployment a storage allow-list.

## 6. Normalizácia cesty

Pred matchom musí backend kanonikalizovať separátory, odstrániť nepovolené segmenty, vyriešiť príponu podľa kontraktu a vynútiť povolený prefix. Používateľský vstup nesmie umožniť `../`, dvojité dekódovanie alebo obídenie Unicode variantom.

Príklady logického vstupu:

| Vstup | Kanonická cesta |
|---|---|
| `pages/finance/budget.md` | `content/pages/finance/budget` |
| `content/blog/internal/*` | `content/blog/internal/*` |
| `media/team/logo.png` | `content/media/team/logo` |

## 7. Match pravidlá

Podporuj iba explicitne dokumentované tvary, napríklad exact path a prefix s koncovým `*`. Regex, `**`, `?` alebo hviezdička uprostred cesty nemajú byť prijaté, ak ich backend nepodporuje.

Poradie pravidiel musí byť deterministické. Ak používa „first match wins“, píš špecifické pravidlá pred všeobecnými a UI musí poradie zachovať.

## 8. Default allow alebo default deny

Súčasný prechodný model môže byť opt-in a default allow pre cesty bez matchu. To je jednoduchšie na migráciu, ale nie je vhodné na všetky citlivé deploymenty.

Administrátor musí vedieť, ktorý model používa konkrétny release. Budúci default-deny profil by mal byť explicitný, s diagnostikou a recovery účtom; nesmie sa zapnúť tichou migráciou.

## 9. Príklady

Finančná sekcia pre editorov a vyššie roly:

```text
path: content/pages/finance/*
roles: EDITOR, ADMIN
```

Interné médiá iba pre adminov:

```text
path: content/media/internal/*
roles: ADMIN
```

Permission-based pravidlo:

```text
path: content/blog/team/*
permissions: content:edit
```

Prázdne role aj permissions nemajú znamenať nejasný „deny všetkých“. UI musí explicitne vysvetliť výsledok alebo pravidlo odmietnuť.

## 10. HTTP správanie

| Operácia | Odporúčaná odpoveď |
|---|---|
| čítanie zakázanej/skrytej položky | 404, ak je cieľom skryť existenciu |
| mutácia bez permission/ACL | 403 |
| neautentifikovaný staff endpoint | 401 |
| stale revision | 409, nie maskovaný ACL error |

Zoznam endpoint musí položky filtrovať rovnakou policy ako detail. Inak názov alebo metadata uniknú v liste aj keď detail vracia 404.

## 11. Médiá

ACL média sa musí vyhodnotiť pri liste, detaili, upload cieli, move/rename, delete a pri generovaní podpísanej URL. Verejná statická URL môže obísť aplikačnú ACL; private media preto nesmie byť uložené v priamo verejnom strome.

## 12. Testovací scenár

1. vytvor pravidlo pre testovací prefix,
2. vytvor položku ako SUPER_ADMIN,
3. over anonymný GET,
4. over EDITOR list a detail,
5. over EDITOR write,
6. over ADMIN podľa policy,
7. over API aj UI,
8. pravidlo odstráň a skontroluj recovery.

Testuj v oddelených session, pretože stará session alebo frontend cache môže dočasne zobrazovať neaktuálny menu stav.

## 13. Lockout recovery

Pred zapnutím prísnej policy maj:

- druhý SUPER_ADMIN účet,
- serverový prístup k zálohe nastavení,
- zdokumentovanú cestu na validáciu/obnovu ACL súboru,
- audit zmeny,
- maintenance okno pri produkcii.

Ručný zásah do JSON vykonávaj iba po zastavení write trafficu a s validáciou syntaxe. Po oprave spusti reload/diagnostiku.

## 14. Bezpečnostné invarianty

- deny rozhodnutie robí backend,
- path sa kanonikalizuje pred autorizáciou aj pred filesystem operáciou,
- permission z request body sa nikdy nepovažuje za dôveryhodnú,
- batch operácia kontroluje každú položku,
- job/queue nesie identitu a autorizovaný kontext pôvodnej akcie,
- audit rediguje secrets, ale zaznamená actor, action, target a výsledok.

## 15. Súvisiace dokumenty

- [Príručka administrátora](ADMIN_GUIDE.md)
- [Core hardening](../architecture/CORE_HARDENING.md)
- [Content API](../architecture/CONTENT_API.md)
- [Nastavenia](../architecture/SETTINGS.md)
- [ISSUES.md](../ISSUES.md)
