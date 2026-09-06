---
title: Inštalácia
description: Bezpečné nasadenie PaginiumCMS cez Docker, natívny PHP stack alebo produkčný reverse proxy
icon: material/server
---

# Inštalácia PaginiumCMS

> **Cieľ:** spustiť backend API, produkčný frontend a autoritatívne flat-file úložisko bez vytvorenia SQL databázy. Návod je určený pre release rodinu **`v2.1.0-beta.*`**.

## 1. Vyber profil nasadenia

| Profil | Použitie | Poznámka |
|---|---|---|
| Docker / lokálny beta | rýchly smoke test a izolované hodnotenie | najmenší počet hostiteľských závislostí |
| Natívny development | vývoj backendu a frontendu | PHP server a Vite nie sú produkčný web server |
| Produkčný single-node | nginx/Apache + PHP-FPM + statický frontend | odporúčaný základ pre vlastný VPS |
| Hybrid / Git-headless | budúci alebo čiastočný profil It.68–77 | aktivuj len capability potvrdené release notes |

## 2. Požiadavky

Presné limity over v `composer.json`, `package.json`, `.env.example` a release artefakte. Aktuálna beta dokumentácia predpokladá:

| Komponent | Požiadavka |
|---|---|
| PHP | 8.5+ a rozšírenia `json`, `mbstring`, `zip`, `curl`, `fileinfo`, `openssl`/`sodium` podľa buildu |
| Composer | 2.x |
| Node.js | 22+ iba pri lokálnom buildovaní frontendu |
| Web server | nginx alebo Apache s PHP-FPM pre produkciu |
| Disk | zapisovateľné storage adresáre a priestor na verzie, médiá, logy a zálohy |
| TLS | HTTPS pre každé internetové alebo staff nasadenie |

PaginiumCMS nepoužíva SQL ako autoritatívne úložisko. Redis, index alebo budúci externý media driver sú voliteľné odvodené schopnosti a nesmú byť jedinou kópiou obsahu.

## 3. Pred inštaláciou

1. Stiahni alebo checkoutni presný release tag.
2. Over checksum/signature, ak je pri release publikovaný.
3. Skontroluj, že archív neobsahuje cudzie `.env`, runtime logy alebo používateľské dáta.
4. Rozhodni, kde budú autoritatívne dáta a zálohy.
5. Na upgrade existujúcej inštancie najprv vytvor zálohu; nespúšťaj first-run cez produkčné dáta naslepo.

## 4. Varianta A — Docker

Typický beta štart:

```bash
unzip paginiumcms-beta.zip
cd paginiumcms
chmod +x scripts/first-run.sh
./scripts/first-run.sh
docker compose up -d
curl --fail --silent http://127.0.0.1:8080/api/health
```

Vývojový frontend spúšťaj iba cez profil deklarovaný v konkrétnom `docker-compose.yml`, napríklad:

```bash
docker compose --profile dev up -d
```

Porty sa môžu medzi release artefaktmi meniť. Za autoritatívne považuj Compose mapovanie a `.env.example`, nie starý screenshot alebo hardcoded URL v issue.

## 5. Varianta B — natívny development

```bash
composer install
chmod +x scripts/first-run.sh
./scripts/first-run.sh
```

Backendový vývojový server:

```bash
php -S 127.0.0.1:8080 -t backend/public
```

Frontend:

```bash
cd frontend
npm ci
npm run dev
```

`php -S` a Vite dev server nepoužívaj ako internetový produkčný stack. Nemajú nahradiť správne TLS, PHP-FPM procesy, limity uploadu, security headers a reverse-proxy pravidlá.

## 6. Produkčný build

Backend závislosti:

```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

Frontend:

```bash
cd frontend
npm ci
npm run build:prod
```

Produkčný web server má:

- servírovať frontend `dist/` ako SPA s fallbackom na `index.html`,
- smerovať `/api` na PHP front controller,
- bezpečne sprístupniť iba podporované media/storage URL,
- smerovať feed, sitemap a robots endpointy podľa release kontraktu,
- zakázať priamy webový prístup k `data`, logom, zálohám, verziám, secrets a zdrojovým súborom.

Pozri [deployment dokumentáciu](../deploy/NGINX_API.md) a [Storage kontrakt](../architecture/STORAGE.md).

## 7. First-run a bootstrap účet

Od **`v2.1.0-beta.62`** môžeš čistú inštanciu dokončiť v prehliadači na **`/setup`** (It.25 setup wizard). **`v2.1.0-beta.65`** pridáva krok **Server** (preflight) a **Infra** (port, storage driver) — pozri [ITERATION_25.md](../sk/ITERATION_25.md).

### Wizard v prehliadači (odporúčané)

1. Otvor koreň webu; ak neexistuje administrátor, SPA presmeruje na `/setup`.
2. **Server** — read-only kontrola PHP ≥8.5, rozšírení, zapisovateľného `storage/`, voliteľne git/composer/vendor. Pri **hard** chybách oprav podľa zobrazených príkazov (Ubuntu/Debian) a klikni **Obnoviť kontrolu**. Wizard **neinštaluje** balíky automaticky.
3. **Administrátor** — prvý **SUPER_ADMIN** (e-mail, meno, heslo).
4. **Web** — názov webu a jazyk administrácie.
5. **Infra** — backend health port (predvolene `8089`) a storage driver médií (`local`).
6. Dokončenie zapíše `general.installed = true`, voliteľne `systemUpdate.backendPort` a `media.storageDriver`, prihlási ťa a presmeruje na dashboard.

Zmeň heslo a zapni 2FA pred sprístupnením hosta.

### Setup API (pre-auth)

| Metóda | Cesta | Účel |
|--------|-------|------|
| GET | `/api/setup/status` | Stav inštalácie |
| GET | `/api/setup/preflight` | Kontrola servera + návod inštalácie |
| POST | `/api/setup/complete` | Jednorazový bootstrap |

Trasy `/api/setup/*` sú **CSRF-exempt** len pre počiatočný bootstrap. Existujúce inštancie s účtami sa považujú za nainštalované aj bez príznaku `installed` (legacy CLI bootstrap).

### CLI fallback (voliteľné / pokročilé)

`first-run.sh` môže podľa release:

- vytvoriť `.env` z `.env.example`,
- vygenerovať `APP_KEY`,
- pripraviť storage adresáre,
- vytvoriť bootstrap administrátora,
- spustiť diagnostiku a bezpečné migrácie.

Vlastné bootstrap údaje odovzdaj cez podporované premenné prostredia:

```bash
export FIRST_ADMIN_EMAIL='admin@example.test'
export FIRST_ADMIN_PASSWORD='replace-with-a-unique-password'
export FIRST_ADMIN_NAME='Primary administrator'
./scripts/first-run.sh
unset FIRST_ADMIN_PASSWORD
```

Neponechávaj exportované heslo v shell history alebo CI logu. Ak release vytvorí známe vývojové heslo, zmeň ho pri prvom prihlásení ešte pred sprístupnením hosta.

## 8. Kritické premenné `.env`

| Premenná | Produkčné pravidlo |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | kanonická HTTPS URL |
| `APP_KEY` | vygenerovať bezpečne, zálohovať ako secret, po zašifrovaní dát nemeniť bez migrácie |
| `TRUSTED_PROXIES` | iba skutočné proxy adresy/CIDR podporované implementáciou |
| `TWO_FACTOR_REQUIRED` | zapnúť pre staff podľa policy |
| `DEMO_MODE` | `false` mimo vyhradenej demo inštancie |
| session/cookie nastavenia | Secure, HttpOnly a vhodný SameSite profil pre topológiu |

Nekopíruj `.env` do verejného ZIP-u ani do screenshotu.

## 9. Práva súborov

Web/PHP používateľ potrebuje zápis len do povolených runtime a storage ciest. Zdrojový kód a konfigurácia majú zostať read-only všade, kde runtime zápis nie je potrebný.

Odporúčaný model je vlastník deploy používateľ, skupina PHP-FPM a adresárové group-write oprávnenie. `chmod -R 777` je bezpečnostná chyba, nie oprava.

## 10. Cron a workery

Scheduled publish, plánované zálohy, notifikácie alebo queue joby fungujú iba vtedy, ak ich konkrétny release vyžaduje a worker je nastavený. Typický príklad:

```cron
* * * * * cd /var/www/paginiumcms && php backend/bin/console scheduler:run >/dev/null 2>&1
* * * * * cd /var/www/paginiumcms && php backend/bin/console worker:process >/dev/null 2>&1
```

Príkazy nepoužívaj slepo. Over ich pomocou `php backend/bin/console list`, deployment dokumentácie a logov. Zabráň paralelnému spusteniu, ak príkaz nemá vlastný lock.

## 11. Overenie po nasadení

```bash
curl --fail --show-error https://cms.example.test/api/health
php backend/bin/console content:diagnose
```

Potom over:

- login a CSRF/session flow,
- povinné 2FA,
- vytvorenie draftu a publikovanie testovacieho obsahu,
- upload a verejné zobrazenie média,
- zápis auditu a request logu,
- vytvorenie a čitateľnosť zálohy,
- 404/403 pre zakázané storage cesty,
- cron/worker heartbeat, ak je zapnutý.

## 12. Upgrade a rollback

1. prečítaj release notes a breaking changes,
2. urob konzistentnú zálohu obsahu, nastavení, keys a extension dát,
3. zastav write traffic alebo zapni maintenance,
4. nasadzuj nový kód mimo aktívneho release adresára,
5. spusti podporované migrácie/diagnostiku,
6. buildni frontend z rovnakého release,
7. vykonaj smoke test,
8. až potom prehoď symlink alebo traffic.

Rollback kódu nemusí byť bezpečný po dátovej migrácii. Obnovenie storage rob iba z overenej kompatibilnej zálohy.

## 13. Riešenie problémov

| Symptóm | Kontrola |
|---|---|
| `/api/*` vracia HTML | chybné proxy alebo SPA fallback zachytil API |
| login končí 401/CSRF chybou | cookie domain/SameSite/Secure, proxy scheme, session storage |
| verejné obrázky sú 404 | media route/proxy a povolená public cesta |
| prázdne zoznamy alebo index chyba | `content:diagnose`, práva, autoritatívne súbory; cache/index môžeš rebuildnúť |
| zapisovanie končí Permission denied | vlastník/skupina runtime ciest; neopravovať cez 777 |
| nesprávna IP v logoch alebo WAF | `TRUSTED_PROXIES` a hlavičky proxy |
| plánované úlohy nebežia | cron, worker, lock, working directory a PHP binary |

Po úspešnej inštalácii pokračuj cez [Prvé kroky](FIRST_STEPS.md).
