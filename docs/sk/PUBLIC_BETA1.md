# PaginiumCMS — Public Beta 1

> **Release rodina:** `v2.1.0-beta.*`  
> **Odporúčaný tag v tomto dokumentačnom snapshot-e:** **`v2.1.0-beta.23`**  
> **Stav:** verejná beta pre technických testerov, early adopters a bezpečnostnú revíziu  
> **Dátový model:** bez SQL databázy; súbory sú zdroj pravdy

`v2.1.0-beta.1` označuje začiatok Public Beta. Neskoršie beta tagy sú kumulatívne opravy a funkčné rozšírenia. Pre nový test nepoužívaj starý `beta.1` iba preto, že sa volá „Beta 1“; použi najnovší overený beta tag projektu.

---

## 1. Čo beta overuje

Public Beta overuje, že PaginiumCMS je možné:

- nainštalovať z čistého klonu,
- bezpečne nakonfigurovať bez SQL databázy,
- používať na správu stránok, článkov, médií a navigácie,
- prevádzkovať s cron jobmi, zálohami a logmi,
- aktualizovať a diagnostikovať podľa dokumentácie,
- testovať mimo vývojového prostredia autora.

Beta nie je sľub API stability ani garancia bezchybnosti. Pred produkčným nasadením treba urobiť vlastnú bezpečnostnú a prevádzkovú revíziu.

---

## 2. Funkcie zahrnuté v `beta.23`

| Oblasť | Stav |
|--------|------|
| Session auth, 2FA, RBAC/ACL | ✅ |
| Pages/articles, Markdown a Tiptap | ✅ |
| Locks, drafts, verzie a konflikty | ✅ |
| SEO, blog, feedy a SK/EN i18n | ✅ |
| DAM, navigácia, komentáre a kontakt | ✅ |
| Newsletter a feature gallery | ✅ |
| Scheduler, plánovaná publikácia, zálohy a koš | ✅ |
| WAF, audit, logy a security hardening | ✅ priebežne |
| Plugin runtime, Code Policy, Developer Mode | ✅ základ |
| System update a demo sandbox | ✅ |
| Layout Switch It.58c | ✅ |
| Hybrid Engine It.68–77 | ⏳ nie je súčasťou beta.23 |

Kompletný inventár: [FEATURE_OVERVIEW.md](FEATURE_OVERVIEW.md).

---

## 3. Rýchly štart testera

1. Prečítaj [user/INSTALLATION.md](user/INSTALLATION.md).
2. Spusť `./scripts/first-run.sh` podľa zvoleného profilu.
3. Spusť Docker stack alebo lokálny PHP/Vite vývojový režim.
4. Over `GET /api/health`.
5. Prihlás sa, zmeň bootstrap heslo a nastav 2FA.
6. Prejdi [user/FIRST_STEPS.md](user/FIRST_STEPS.md).
7. Vykonaj beta checklist v [user/BETA_TESTER.md](user/BETA_TESTER.md).

Príklad:

```bash
git checkout v2.1.0-beta.23
./scripts/first-run.sh
docker compose up -d
curl -s http://localhost:8080/api/health
```

Porty a príkazy over podľa aktuálneho compose/env súboru; dokumentačný príklad nemusí zodpovedať vlastnému deploy profilu.

---

## 4. Minimálny smoke test

- login, logout a 2FA,
- vytvorenie konceptu stránky a článku,
- súbežná editácia alebo simulácia revision konfliktu,
- upload obrázka a vloženie do editora,
- publish + zobrazenie na verejnom webe,
- komentár/kontaktná správa/newsletter podľa zapnutých modulov,
- vytvorenie a overenie zálohy,
- presun do koša a restore,
- manuálne spustenie scheduler jobu,
- prehľad logov, audit trail a WAF modulu,
- prepnutie jazyka a appearance/layout nastavení.

Zapisuj presnú verziu, commit a deploy režim.

---

## 5. Prevádzkové predpoklady

| Téma | Požiadavka |
|------|------------|
| HTTPS | povinné pre verejnú/produkčnú inštanciu |
| `APP_KEY` | musí byť stabilný, tajný a zálohovaný podľa bezpečnostnej dokumentácie |
| Permissions | storage musí byť zapisovateľné používateľom PHP kontajnera/procesu |
| Cron | `scheduler:run` a worker podľa [deploy/CRON.md](deploy/CRON.md) |
| Backup | overiť restore, nie iba vytvorenie archívu |
| Mail/connectors | testovať s reálnym, ale bezpečným testovacím cieľom |
| Reverse proxy | správne trusted proxies, headers a body limits |
| Logs | `display_errors=Off` na produkcii, chyby do logu |

---

## 6. Známe hranice, ktoré nie sú regresiou

- **It.25 setup wizard** je dodaný (`beta.62`–`beta.65`): onboarding cez `/setup` s preflight serverom; `first-run.sh` zostáva CLI cesta.
- Full Hybrid/Git-headless engine It.68–77 je plán, nie beta.23 feature.
- Redis, S3, cloud translation a AI agent nie sú povinné ani aktívne.
- Niektoré integrácie sú použiteľné až po administrátorskej a infra konfigurácii.
- Cron-dependent workflow nefunguje automaticky bez host cron/systemd/worker nastavenia.
- Theme/runtime model je čiastočný a zostáva predmetom It.67 a neskorších vĺn.

---

## 7. Hlásenie chýb

Pri bežnom bug reporte uveď:

- tag/verziu a commit,
- OS, PHP/Node verziu a deploy profil,
- kroky reprodukcie,
- očakávané a skutočné správanie,
- relevantný log bez secrets a osobných údajov,
- výsledok health/diagnose príkazu,
- informáciu, či problém vznikol na čistom inštalle alebo po update.

### Bezpečnostné nálezy

Nezverejňuj neopravenú zraniteľnosť ako bežný verejný Issue. Použi postup v koreňovom [`SECURITY.md`](../../SECURITY.md). Pred zdieľaním odstráň heslá, tokeny, cookies, osobné údaje a obsah produkčných súborov.

---

## 8. Čo testovať najviac

1. čistá inštalácia a permissions,
2. auth/CSRF/2FA a role boundaries,
3. súbežné zápisy a recovery po zlyhaní,
4. backup/restore a update rollback,
5. cron-dependent joby,
6. plugin/theme/import hranice,
7. nginx/reverse proxy headers,
8. dokumentácia — či príkazy fungujú na inom stroji než maintainerovom.

---

## 9. Po Public Beta

Najbližší smer po dokončení dokumentácie:

- It.68 Hybrid Engine foundation,
- It.69 cache/Redis/HTTP validators,
- It.67 untrusted surfaces hardening,
- It.58d layout polish,
- komunitné beta opravy,
- It.25 onboarding/update UX — ✅ dodané (`beta.62`–`beta.65`, vrátane M1+ preflight).

Roadmapa: [ROADMAP.md](ROADMAP.md) · Aktívny handoff: [CONTINUATION.md](CONTINUATION.md).
