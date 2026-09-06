---
title: Prvé kroky
description: Prvé prihlásenie, bezpečnostný baseline a vytvorenie prvého obsahu
icon: material/rocket-launch
---

# Prvé kroky po inštalácii

> Predpoklad: inštancia prešla [inštaláciou](INSTALLATION.md), `/api/health` odpovedá a máš bootstrap staff účet.

## 1. Prvé prihlásenie

**Setup v prehliadači (odporúčané):** po [inštalácii](INSTALLATION.md) otvor na čistej inštancii `/setup`. Dokonči kroky (**Server → Administrátor → Web → Infra**), pri hard chybách preflightu oprav podľa zobrazených príkazov — potom ťa presmeruje na **`/login`** a musíš sa prihlásiť novým admin účtom.

**CLI bootstrap:** ak si použil `first-run.sh` / `bootstrap-admin.php`:

1. Otvor produkčnú cestu `/login` na kanonickej HTTPS URL.
2. Prihlás sa bootstrap účtom vytvoreným počas first-run.
3. Okamžite zmeň známe alebo dočasné heslo.
4. Dokonči 2FA onboarding, ak ho systém vyžaduje.
5. Ulož recovery kódy mimo servera a mimo rovnakého password manager záznamu ako heslo.

Na produkcii nevypínaj 2FA len preto, aby onboarding „prešiel“. Ak QR alebo TOTP nefunguje, skontroluj čas servera, session cookie, proxy HTTPS hlavičky a logy.

## 2. Over identitu inštancie

Pred úpravou reálnych dát si over:

- hostname a environment banner,
- release/verziu z dostupného health/about endpointu alebo release artefaktu,
- či nejde o demo alebo staging,
- či zapisuješ do očakávaného storage adresára,
- či je vytvorená prvá záloha.

Tým sa vyhneš klasickému „upravil som produkciu, myslel som si, že je to staging“ momentu. Starý adminský evergreen. 🙂

## 3. Dashboard a navigácia

Viditeľné moduly závisia od roly, permissions, feature flags a profilu nasadenia. Typicky:

| Sekcia | Moduly |
|---|---|
| Prehľad | dashboard, rýchle akcie, stavové panely |
| Pracovný priestor | stránky, články, médiá, navigácia |
| Schránka | komentáre a kontaktné správy |
| Platforma | nastavenia, používatelia, notifikácie, plánovač, účet |
| Bezpečnosť | firewall, logy, audit, bezpečnostný audit |
| Prevádzka | zálohy, kôš, Git integrácia podľa konfigurácie |
| Vývoj | extensions, blueprints, Code Editor a Developer Mode podľa roly |

Ak modul chýba, najprv over oprávnenie a environment gate. Neopravuj sidebar ručným odblokovaním frontendu; backend musí akciu autorizovať tiež.

## 4. Bezpečnostný baseline prvého dňa

- vytvor druhý dôveryhodný recovery admin účet, ak to prevádzkový model vyžaduje,
- zapni 2FA pre všetky staff účty,
- skontroluj `APP_DEBUG=false` a HTTPS,
- over firewall a správnu klientsku IP,
- nastav retenciu logov a záloh,
- odstráň testovacích používateľov a nepoužívané API/extension secrets,
- over, že storage, logs a backups nie sú verejne dostupné,
- vykonaj test obnovy aspoň na izolovanej kópii.

## 5. Základné nastavenia webu

V **Nastaveniach** nastav minimálne:

| Oblasť | Kontrola |
|---|---|
| Identita | názov, základná URL, jazyk, timezone |
| Branding | logo, favicon a fallback pre light/dark režim |
| SEO | predvolené meta, indexovanie a kanonická URL |
| Účty | registrácia, politika hesiel, 2FA |
| Email/notifikácie | provider, šifrované secret polia a test odoslania |
| Logovanie | minimum severity, request logging, retencia |
| Firewall | enabled, jail profil, whitelist iba pre odôvodnené IP |

Po zmene secrets nevkladaj ich do issue reportu. Nastavenia môžu vyžadovať reload služby alebo nový login podľa konkrétneho kontraktu.

## 6. Vytvor prvú stránku

1. Otvor **Stránky** a zvoľ novú položku.
2. Zadaj názov a bezpečný slug.
3. Vyber editor Markdown alebo WYSIWYG.
4. Doplň obsah, meta popis a prípadne OG obrázok.
5. Najprv ulož ako `draft`.
6. Skontroluj náhľad a odkazy.
7. Publikuj a over verejnú URL v anonymnom okne.

Draft nie je verejne publikovaný obsah. Adminský náhľad môže používať staff session; preto verejnú dostupnosť vždy kontroluj aj bez prihlásenia.

## 7. Vytvor prvý článok

Článok pridáva tagy, excerpt, featured/OG obrázok a podľa nastavení komentáre. Po publikovaní over:

- kartu v blog zozname,
- detail článku,
- obrázok cez verejnú media URL,
- title/meta/OG hodnoty,
- feed alebo sitemap iba ak ich release podporuje a máš ich zapnuté.

## 8. Médiá a alternatívny text

Nahraj testovací obrázok cez Media Manager, nie ručným kopírovaním do storage. Over typ, veľkosť, náhľad a verejnú cestu. Doplň zmysluplný `alt` text; dekoratívny obrázok označ podľa UI pravidiel.

Externé URL môžu prezrádzať IP návštevníka tretej strane. Pre branding a obsah preferuj lokálne spravované médiá, ak nie je dôvod na externý asset.

## 9. Navigácia

Pridaj stránku do navigácie, nastav poradie a cieľ. Externé odkazy otvárané v novom okne musia používať bezpečný `rel` kontrakt implementácie. Po uložení over desktop aj mobilné menu.

## 10. Vytvor editor účet a otestuj least privilege

1. Vytvor účet s rolou `EDITOR`.
2. Prihlás sa v oddelenom browser profile.
3. Over, že môže upravovať povolený obsah.
4. Over, že nevidí alebo nemôže vykonať platformové a bezpečnostné akcie.
5. Ak používaš Path ACL, testuj povolenú aj zakázanú cestu.

Frontendové skrytie tlačidla nie je autorizácia. Zakázaná mutácia musí zlyhať aj pri priamom API requeste.

## 11. Skontroluj audit, logy a zálohu

Po prvých zmenách by si mal vedieť nájsť:

- login a bezpečnostné udalosti,
- vytvorenie/úpravu obsahu v audite,
- HTTP request záznam bez secrets,
- firewall incident iba pri reálnom scenári,
- úspešne vytvorenú zálohu s overením integrity.

**Obnova (odporúčané na dev):** vytvor zálohu → vymaž jeden článok (soft delete) → obnov zálohu → článok sa musí vrátiť. Ak nie, pozri [BACKUP_RESTORE.md](../../en/developer/BACKUP_RESTORE.md) ([ISS-163](../../ISSUES.md#iss-163)).

## 12. Čo ešte nie je automatické

V Hybrid Engine smerovaní nepredpokladaj, že **Uložiť** automaticky znamená:

```text
Git commit/push
preklad do ďalšieho jazyka
AI schválenie
vyčistenie každej cache
build frontendu
nasadenie na CDN
```

Každý z týchto krokov má vlastný stav, oprávnenie a failure režim. Dostupnosť over v release notes.

## 13. Ďalší krok

- každodenná administrácia: [ADMIN_GUIDE.md](ADMIN_GUIDE.md)
- editovanie do hĺbky: [CONTENT_EDITOR.md](CONTENT_EDITOR.md)
- oprávnenia: [ACCESS_CONTROL.md](ACCESS_CONTROL.md)
- firewall a logy: [FIREWALL.md](FIREWALL.md), [LOGGING.md](LOGGING.md)
