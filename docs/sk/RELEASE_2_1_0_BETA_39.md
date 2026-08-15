# Release `v2.1.0-beta.39` — It.80 komplet: CLI toolkit a import z WordPressu

> **Dátum:** 2026-08-13  
> **Tag:** `v2.1.0-beta.39`  
> **Predchádzajúci míľnik:** [`beta.38`](../../CHANGELOG.md#release-2-1-0-beta-38) · **Súhrn série:** [CLANEK_BETA_35_45_SUHRN.md](CLANEK_BETA_35_45_SUHRN.md)  
> **Typ vydania:** prelomové — uzatvára Iteráciu 80 (80a–80g)

---

## Zhrnutie jednou vetou

PaginiumCMS dostáva **plnohodnotný operátorský CLI** a **prvú fázu migrácie z WordPressu** — bez nového úložiska, na existujúcom flat-file modeli.

---

## Prečo je beta.39 prelomová

Od **beta.32** (redirect manager) tím postupne doručoval checklist It.80: 404 report, spam, webhooks, GDPR, API hardening. **Beta.39** je bod, kde operátor vie:

1. **Exportovať/importovať obsah** bez admin session.
2. **Bootstrapnúť používateľov** zo shellu.
3. **Importovať WordPress WXR** do stránok a článkov.

To mení onboarding (presun z iného CMS) aj prevádzku (backup, staging, CI pipeline).

---

## Nové CLI príkazy (It.80f)

Spustenie z koreňa backendu:

```bash
cd backend && php bin/console list
```

| Príkaz | Účel |
|--------|------|
| `content:export` | JSON export stránok/článkov (`--type=page\|article\|all`) |
| `content:import` | Import z JSON bundle alebo WordPress WXR |
| `user:create` | Vytvorenie operátorského účtu |
| `user:list` | Zoznam používateľov |
| `user:reset-password` | Reset hesla |
| `redirect:validate` | Lint redirect mapy (doručené v beta.38, súčasť toolkitu) |

### Import — dry-run default

`content:import` **defaultne nič nezapíše**. Pre skutočný zápis:

```bash
php bin/console content:import --format=wordpress --file=/path/export.xml --run
```

Slug kolízie → prefix `import-{slug}`.

---

## WordPress WXR import (It.80g — fáza 1)

- Príspevky → **articles**, stránky → **pages**.
- Médiá sa **nestiahnu** — URL ostávajú v tele (bezpečné, predvídateľné).
- Jekyll/Ghost import ostáva na budúcu iteráciu.

Typický workflow migrácie:

1. Export WXR z WordPress adminu.
2. `content:import --format=wordpress --file=… --run` na stagingu.
3. Manuálna kontrola slugov a médií.
4. Deploy / Git publish podľa vášho procesu.

---

## Čo už bolo v predchádzajúcich beta (kontext It.80)

| Sub | Verzia | Funkcia |
|-----|--------|---------|
| 80a | beta.32 | Redirect manager |
| 80b | beta.35 | 404 tracking |
| 80c | beta.35 | Comment spam heuristics |
| 80d | beta.36 | Outbound webhooks |
| 80e | beta.37 | GDPR export/anonymize |
| 80f | beta.38–39 | API limity + CLI completion |
| 80g | beta.39 | WordPress WXR import |

Dokumentácia: [ITERATION_80.md](../en/ITERATION_80.md) — status **complete**.

---

## Dôležité upozornenie: prejdite na beta.40+

Beta.39 pridala `BodyParsingMiddleware`. Na produkcii to rozbilo **System Update deploy** a niektoré JSON endpointy, ak controller čítal len raw stream.

→ **Minimálne nasaďte [`beta.40`](../../CHANGELOG.md#release-2-1-0-beta-40)** alebo rovno aktuálnu `beta.45`.

Incident: [ISS-141](../ISSUES.md#iss-141).

---

## Deploy

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.39 APP_ROOT=/var/www/paginiumcms.com \
  STACK_DIR=/var/lib/docker/compose/paginiumcms BACKEND_PORT=8089 \
  ./scripts/deploy-instance-update.sh
```

**Odporúčané:** `GIT_REF=v2.1.0-beta.45` (zahŕňa beta.40 hotfix + It.58 shortcodes).

---

## Overenie po nasadení

```bash
# verzia
curl -s http://127.0.0.1:8089/api/health | jq .data.version

# CLI v PHP kontajneri
docker compose exec php php bin/console content:export --type=page | head
```

---

## Ďalšie kroky v produkte

- **It.58** (beta.41+) — layout shortcodes pre marketingové stránky.
- **It.81** — redakčný workflow (duplikácia, bulk tagy, kalendár).

---

## Odkazy

- [CHANGELOG — beta.39](../../CHANGELOG.md#release-2-1-0-beta-39)
- [TESTING.md §13.4](../en/developer/TESTING.md) — CLI príklady
- [Súhrn beta 35–45](CLANEK_BETA_35_45_SUHRN.md)
