---
title: Lokálne vývojové prostredie
description: Reprodukovateľný Docker a natívny setup PaginiumCMS s bezpečným bootstrapom a troubleshootingom
icon: material/docker
---

# Lokálne vývojové prostredie

> Návod je určený pre release rodinu **`v2.1.0-beta.*`**. Presné verzie runtime, porty a skripty vždy over v `composer.json`, `package.json`, `docker-compose.yml`, `.env.example` a CI konkrétneho tagu.

## 1. Podporované workflow

| Profil | Vhodné pre | Nie je určené pre |
|---|---|---|
| Docker Compose | rýchly clone-to-login, beta smoke, izolované závislosti | predstieranie plného produkčného HA |
| Natívny backend + Vite | každodenný PHP/React vývoj a debugging | internetovo dostupnú produkciu |
| Produkčný lokálny build | overenie nginx/PHP-FPM a statického SPA bundle | editovanie cez Vite HMR |

## 2. Predpoklady

Odporúčaný hostiteľ:

- Git,
- Docker Engine + Compose plugin pre kontajnerový profil,
- alebo PHP 8.5+, Composer 2 a Node.js 22+ pre natívny profil,
- `curl`, `unzip`, shell kompatibilný so skriptmi,
- dostatok miesta na `vendor`, `node_modules`, storage, test temp a build.

Overenie:

```bash
git --version
docker version
docker compose version
php -v
composer --version
node --version
npm --version
```

Nepotrebuješ SQL server. Redis je voliteľná budúca/odvodená capability a nie podmienka základného lokálneho vývoja.

## 3. Clone a bezpečný first-run

```bash
git clone <repository-url> paginiumcms
cd paginiumcms
git switch <release-tag-or-branch>
chmod +x scripts/first-run.sh
```

Pred spustením nastav vlastný bootstrap účet:

```bash
export FIRST_ADMIN_EMAIL='developer@example.test'
export FIRST_ADMIN_NAME='Local Developer'
export FIRST_ADMIN_PASSWORD='Use-A-Unique-Strong-Password-Here'
./scripts/first-run.sh
```

Ak konkrétny artefakt podporuje inštaláciu frontendu cez first-run:

```bash
INSTALL_FRONTEND=1 ./scripts/first-run.sh
```

Nikdy nepouži bootstrap heslo zo screenshotu alebo starého návodu na verejnej inštancii. Po prvom prihlásení heslo zmeň a pri staff workflow otestuj 2FA.

## 4. Čo má first-run vykonať

Konkrétny skript je zdroj pravdy. Očakávaný bezpečný kontrakt:

1. vytvorí `.env` z example iba ak chýba,
2. vygeneruje reálny `APP_KEY`, ak nie je nastavený,
3. vytvorí povolený storage strom,
4. nainštaluje backend dependencies,
5. vytvorí prvého `SUPER_ADMIN` iba ak používateľ ešte neexistuje,
6. spustí content diagnostiku/rebuild odvodených vrstiev,
7. voliteľne nainštaluje frontend dependencies,
8. nevypíše secret do dlhodobého logu.

`APP_KEY` po vzniku šifrovaných dát nemeň. Strata kľúča môže znamenať stratu TOTP/settings secrets.

## 5. Docker Compose profil

Typický štart backendu:

```bash
docker compose up -d
docker compose ps
docker compose logs --tail=100 nginx php
curl --fail --silent http://127.0.0.1:8080/api/health
```

Backend + vývojový frontend, ak compose definuje profil `dev`:

```bash
docker compose --profile dev up -d
curl --fail --silent http://127.0.0.1:3025/
```

Produkčný frontend build, ak je profil dostupný:

```bash
docker compose --profile build run --rm frontend-build
```

Porty `8080` a `3025` sú konvencia zdrojového snapshotu, nie večná garancia. Konflikt rieš mapovaním v lokálnom override súbore, nie úpravou tracked compose iba pre svoj notebook.

### Lokálny override

Príklad necommitovaného `docker-compose.override.yml`:

```yaml
services:
  nginx:
    ports:
      - "18080:80"
  frontend:
    ports:
      - "13025:3025"
```

Potom:

```bash
docker compose up -d
curl http://127.0.0.1:18080/api/health
```

## 6. Natívny vývoj

Backend dependencies a bootstrap:

```bash
composer install
./scripts/first-run.sh
```

Terminál 1 — backend development server:

```bash
cd backend/public
php -S 127.0.0.1:8080
```

Terminál 2 — frontend:

```bash
cd frontend
npm ci
npm run dev
```

Vite proxy má smerovať `/api` na lokálny backend podľa aktuálnej konfigurácie. `php -S` a Vite sú iba development servery; nepoužívaj ich ako verejnú produkciu.

## 7. Minimálny lokálny `.env`

Kľúčové skupiny:

```dotenv
APP_ENV=development
APP_DEBUG=true
APP_KEY=<generated-secret>
DEMO_MODE=false
TWO_FACTOR_REQUIRED=false
SESSION_STRICT=false
```

Poznámky:

- `APP_DEBUG=true` iba lokálne,
- `TWO_FACTOR_REQUIRED=false` je vývojová výnimka; staff produkcia má vyžadovať 2FA,
- `SESSION_STRICT=false` môže pomôcť pri lokálnom reverse proxy, ale neznamená automaticky bezpečný produkčný profil,
- `DEMO_MODE=true` používaj iba v izolovanej demo inštancii a nikdy nie s reálnymi dátami,
- `.env` necommituj.

Presné názvy a defaults over v `.env.example`.

## 8. Health a smoke test

Po štarte:

```bash
curl -i http://127.0.0.1:8080/api/health
curl -i http://127.0.0.1:8080/api/settings/public
```

Manuálne over:

1. login bootstrap účtu,
2. dashboard bez `500`,
3. získanie CSRF tokenu a jedna bezpečná mutácia,
4. vytvorenie draft stránky a opätovné načítanie,
5. logout a odmietnutie chráneného endpointu,
6. log bez secretov a bez neočakávaného stack trace.

## 9. Quality gate lokálneho clone

Backend:

```bash
composer test
composer stan
composer cs
composer audit
```

Frontend:

```bash
cd frontend
npm ci
npm run type-check
npm run lint
npm run lint:api-barrel
npm test -- --run
npm run build
npm audit --audit-level=critical
```

Projektový gate:

```bash
cd ..
./scripts/iteration-gate.sh
```

Pred prvou zmenou spusti baseline. Inak nevieš, či si chybu priniesol ty alebo už bola vo vetve.

## 10. Content diagnostika

Pri prázdnom obsahu, poškodenom indexe alebo po migračnom teste:

```bash
php backend/bin/console content:diagnose
php backend/bin/console content:diagnose --json
php backend/bin/console content:diagnose --fix
```

`--fix` používaj až po prečítaní diagnostiky a pri dôležitých lokálnych dátach po zálohe. Index/cache sú rebuildovateľné; autoritatívny content nemaž ako prvý troubleshooting krok.

## 11. Reset lokálneho prostredia

Najprv si ujasni, čo chceš resetovať:

| Cieľ | Bezpečný postup |
|---|---|
| Kontajnery | `docker compose down` |
| Kontajnery + ephemeral volumes | `docker compose down -v` iba ak volume neobsahuje potrebný SSOT |
| Frontend dependencies | odstrániť `frontend/node_modules`, potom `npm ci` |
| Backend dependencies | odstrániť `vendor`, potom `composer install` |
| Index/cache | použiť diagnostický/rebuild príkaz |
| Celý lokálny content | manuálne až po zálohe a overení ciest |

Nedávaj `rm -rf backend/storage` do všeobecného „fix everything“ aliasu. Také aliasy majú zvláštny talent spustiť sa presne v nesprávnom termináli.

## 12. Časté problémy

### Port je obsadený

```bash
ss -ltnp | grep -E ':8080|:3025'
```

Použi lokálny compose override alebo iný Vite port.

### `401` po úspešnom login requeste

Skontroluj cookie domain/path, `SameSite`, origin, proxy, session volume a systémový čas. Pri rozdelenom FE/API porte over CORS/credentials a CSRF flow.

### `403` pri mutácii

Rozlíš:

- CSRF token chýba alebo je stale,
- role/permission deny,
- Path ACL deny,
- WAF/reverse proxy block.

Najprv skontroluj `content-type` a telo odpovede; nemusí ísť o JSON.

### Storage nie je zapisovateľný

```bash
ls -ld backend/storage backend/storage/*
id
```

V Dockeri over UID/GID kontajnera. Oprav ownership a minimálny mód, nie `777`.

### Frontend nevie volať API

Skontroluj Vite proxy, `VITE_API_URL`, same-origin očakávanie a to, či backend počúva na správnom interface/porte.

### Plugin frontend sa po importe nezobrazil

PHP runtime import/aktivácia a Vite frontend bundle sú rozdielne. Zdrojový frontend plugin môže vyžadovať rebuild a redeploy.

## 13. Lokálna bezpečnosť

- nepoužívaj reálne produkčné secrets,
- test webhooky a OAuth smeruj na lokálne/falošné providery,
- neotváraj development porty na WAN,
- nepridávaj `0.0.0.0` bez potreby,
- rediguj logy pred priložením k issue,
- nepoužívaj production backup na notebooku bez šifrovania a právneho dôvodu,
- pravidelne spúšťaj dependency audit.

## 14. Ďalšie kroky

Po úspešnom clone-to-login pokračuj:

1. [DEVELOPMENT.md](DEVELOPMENT.md) — denný workflow,
2. [CODING_STANDARDS.md](CODING_STANDARDS.md) — pravidlá kódu,
3. [TESTING.md](TESTING.md) — testovacia architektúra,
4. [CONTRIBUTING.md](CONTRIBUTING.md) — PR kontrakt,
5. [BETA_INFRA.md](BETA_INFRA.md) — clean-clone a release readiness.
