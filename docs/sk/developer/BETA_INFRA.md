---
title: Beta infra a release readiness
description: Clean-clone acceptance, CI gate, security baseline, prevádzkové dôkazy a rollback pre verejnú betu
icon: material/rocket-launch
---

# Beta infra a release readiness

> Tento checklist platí pre release rodinu **`v2.1.0-beta.*`**. Nie je to historický zoznam jedného servera ani náhrada súkromného ops runbooku. Privátne adresy, credentials a incident dáta zostávajú mimo verejného repozitára.

## 1. Cieľ gate

Beta kandidát je pripravený až keď nový tester alebo maintainer dokáže:

```text
clean clone
→ overenie release artefaktu
→ first-run
→ login + 2FA podľa profilu
→ základný content workflow
→ quality/security gate
→ backup + restore dôkaz
→ zdokumentovaný rollback
```

„Funguje na hlavnom vývojovom serveri“ nie je acceptance kritérium. Potrebujeme reprodukovateľnú cestu bez skrytého lokálneho súboru a bez ručnej opravy databázy — čo je našťastie jednoduchšie, keď žiadnu SQL databázu nemáme. 😄

## 2. Release identita

Pred gate zaznamenaj:

| Pole | Hodnota |
|---|---|
| commit SHA | presný commit kandidáta |
| tag/version | plánovaný tag alebo immutable build ID |
| build timestamp | UTC |
| backend runtime | PHP + Composer lock |
| frontend runtime | Node/npm + lockfile |
| deployment profil | Docker, single-node, demo, split test |
| artifact checksum | SHA-256 release ZIP/tar/image digest |
| migration range | z ktorej verzie sa testoval upgrade |

Nepoužívaj slovné „latest“ ako dôkaz. Kandidát musí byť identifikovateľný aj o tri mesiace.

## 3. Clean-clone acceptance

Na čistom hoste alebo disposable VM:

```bash
git clone <repository-url> paginiumcms
cd paginiumcms
git switch <candidate-tag-or-sha>
export FIRST_ADMIN_EMAIL='beta-admin@example.test'
export FIRST_ADMIN_PASSWORD='Unique-Temporary-Beta-Password'
export FIRST_ADMIN_NAME='Beta Administrator'
./scripts/first-run.sh
```

Acceptance:

- [ ] `.env` vznikne bez prepisu existujúceho súboru,
- [ ] `APP_KEY` je reálny, nie placeholder,
- [ ] storage strom má správneho ownera a mód,
- [ ] prvý admin vznikne iba raz,
- [ ] diagnose/rebuild skončí bez poškodenia SSOT,
- [ ] `/api/health` odpovie úspešne,
- [ ] admin login a dashboard fungujú bez `500`,
- [ ] mutácia bez CSRF je odmietnutá,
- [ ] neautorizovaný účet nevykoná admin mutáciu,
- [ ] logy neobsahujú bootstrap heslo ani secret.

## 4. Povinný quality gate

Minimálny backend:

```bash
composer test
composer stan
composer cs
composer audit
```

Minimálny frontend:

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
# rozšírený runner, ak ho kandidát obsahuje
./scripts/run-all-tests.zsh
```

CI workflow má byť mirrorom release minima. Lokálny „green“ bez CI nie je release dôkaz, ak runner používa iný lockfile, PHP verziu alebo environment.

## 5. CI matica

Odporúčané jobs:

| Job | Povinné kontroly |
|---|---|
| Backend | Composer install, PHPUnit, PHPStan L8, syntax, dependency audit |
| Frontend | `npm ci`, type-check, ESLint, API barrel, Vitest, production build, audit policy |
| Integration | bootstrap test, HTTP smoke, auth/CSRF/RBAC, storage diagnostics |
| Security | extension policy packs, traversal/ZIP, SSRF, log sanitization, secret scan |
| Docs | Markdown links, code fences, SK/EN path/heading parity pre zmenené docs |
| Artifact | build, checksum/SBOM podľa release procesu, archive contents check |

Newman/Postman smoke kolekcia môže byť doplnok. Nie je kompletnou API špecifikáciou ani náhradou PHPUnit kontrakt testov.

## 6. Beta smoke scenár

### 6.1 Autentifikácia a oprávnenia

- [ ] login success/failure a rate limit,
- [ ] session regeneration a logout,
- [ ] staff 2FA v produkčnom profile,
- [ ] reset hesla bez account enumeration,
- [ ] USER/EDITOR/ADMIN/SUPER_ADMIN hranice,
- [ ] Path ACL allow/deny a recovery z chybného pravidla.

### 6.2 Obsah

- [ ] vytvor draft,
- [ ] edituj a publikuj podľa implementovaného lifecycle,
- [ ] reload zachová dáta,
- [ ] druhý editor vyvolá lock/revision konflikt,
- [ ] konflikt sa neprepíše potichu,
- [ ] trash/restore alebo delete workflow podľa verzie,
- [ ] media upload a bezpečné doručenie.

### 6.3 Prevádzka

- [ ] firewall/WAF event je viditeľný,
- [ ] audit zachytí významnú mutáciu,
- [ ] log rotation/retention nepoškodí aktívny writer,
- [ ] backup vznikne a restore sa overí na oddelenom strome,
- [ ] scheduler/worker spracuje povolený job,
- [ ] retry nevytvorí duplicitu,
- [ ] maintenance/recovery cesta je zdokumentovaná.

### 6.4 Extensions

- [ ] validný plugin sa importuje ako disabled,
- [ ] traversal/symlink/forbidden PHP balík sa odmietne,
- [ ] enable/disable zmení iba očakávaný runtime stav,
- [ ] uninstall/rollback nestratí cudzie dáta bez potvrdenia,
- [ ] frontend extension dokumentuje build/redeploy požiadavku.

## 7. Security baseline

| Oblasť | Release požiadavka |
|---|---|
| Transport | HTTPS na verejnom/staff deployi; HSTS podľa domény a rollout plánu |
| Docroot | iba `backend/public/`; autoritatívne dáta mimo verejného rootu |
| Environment | `APP_ENV=production`, `APP_DEBUG=false`, demo fail-closed |
| Session | Secure/HttpOnly/SameSite podľa topológie; trusted proxy explicitne |
| CSRF | synchronizer token na session mutáciách |
| RBAC/ACL | backend permission + Path ACL testy |
| Secrets | reálny `APP_KEY`, encrypted secret fields, žiadne secrets v logu/UI |
| Upload/ZIP | size/type/content/path limits, SVG/HTML policy, Zip-Slip/symlink deny |
| Outbound | SSRF guard, HTTPS, timeout, redirect revalidation |
| Dependencies | Composer/npm audit podľa schválenej severity policy |
| Logging | CR/LF/ANSI/CSV sanitization, retention a prístupové práva |
| Backup | šifrovaný/obmedzený prístup, restore test, kópia mimo primárneho hosta |

Security review detail: [SECURITY_REVIEW.md](../SECURITY_REVIEW.md), koreňový `SECURITY.md` a [developer/SECURITY.md](SECURITY.md).

## 8. Scheduler a worker

Konkrétny release musí uviesť:

- názov scheduler príkazu,
- názov worker príkazu,
- interval alebo queue trigger,
- lock, max runtime a stale lock recovery,
- retry/backoff a dead-letter/failure stav,
- identitu/permission kontext jobu,
- log/audit a monitoring,
- bezpečný deploy/restart postup.

Príklad cron wrappera čerpaj z [deploy/CRON.md](../deploy/CRON.md), nie zo starej IP adresy v historickom issue. Worker nesmie dostať implicitný SUPER_ADMIN kontext iba preto, že beží na serveri.

## 9. Deployment smoke

Po deployi:

```bash
curl --fail --silent https://example.test/api/health
curl -I https://example.test/
curl -I https://example.test/storage/app/content/data/users/
```

Očakávania:

- health je úspešný,
- SPA assety majú správny content type/cache policy,
- citlivá storage cesta je `404`/neprístupná,
- security headers zodpovedajú aktuálnemu profilu,
- staré assety nezostali miešané s novým manifestom,
- PHP-FPM/opcache a workery používajú nový release,
- migrácia/rebuild je dokončená pred otvorením mutácií.

## 10. Backup, upgrade a rollback gate

Pred tagom over aspoň:

1. backup autoritatívneho content/config/user storage,
2. zachovanie `APP_KEY` a potrebných secrets oddelene od dátového archívu,
3. upgrade zo schválenej predchádzajúcej beta verzie,
4. idempotentné opakovanie migrácie,
5. rebuild index/cache,
6. rollback aplikácie,
7. restore dát do oddeleného testovacieho rootu,
8. kontrolu loginu a jedného content záznamu po restore.

Rollback nie je „vráť Git commit“ pri zmene schémy dát. Musí definovať kompatibilitu formátu alebo restore bod.

## 11. Release evidence balík

Kandidát má priložiť alebo archivovať:

- checksum artefaktu,
- CI run URL/ID,
- test summary bez nafúknutých historických počtov,
- dependency audit summary a prijaté výnimky,
- migration/rollback výsledok,
- clean-clone smoke záznam,
- security review delta,
- známe limitácie,
- release notes a changelog,
- zoznam otvorených blockerov s ownerom.

Dôkaz nesmie obsahovať `.env`, cookies, TOTP QR, API keys, privátne URL alebo osobné dáta testerov.

## 12. Severity a release rozhodnutie

| Stav | Rozhodnutie |
|---|---|
| Kritický/high exploitable finding bez mitigácie | stop release |
| Strata alebo korupcia SSOT | stop release |
| Authz/CSRF bypass | stop release |
| Neopakovatelný first-run/upgrade | stop release |
| Flaky test v bezpečnostnej ceste | stop a opraviť deterministicky |
| Dokumentačný rozdiel bez bezpečnostného dopadu | môže ísť s explicitným known issue podľa rozhodnutia maintainerov |
| Kozmetická UI chyba | triage podľa dopadu a beta cieľa |

„Beta“ neznamená, že môžeme akceptovať známy bypass oprávnení. Znamená, že rozhranie a niektoré neblokujúce schopnosti sa môžu meniť transparentným spôsobom.

## 13. Incident a hotfix

Pri regresii po vydaní:

1. zastav ďalší rollout,
2. identifikuj presný artefakt a rozsah,
3. aktivuj rollback alebo maintenance podľa dopadu,
4. zachovaj redigované logy/audit,
5. vytvor `ISS-xxx` detail s príčinou a riešením,
6. pridaj regresný test,
7. vydaj hotfix s novým immutable tagom,
8. aktualizuj changelog/security advisory podľa potreby.

V Iterácii 13 budú čísla v `ISSUES.md` klikateľné na stabilné explicitné anchory detailov.

## 14. Beta onboarding cesta

| Poradie | Dokument |
|---|---|
| 1 | [LOCAL_SETUP.md](LOCAL_SETUP.md) |
| 2 | [user/INSTALLATION.md](../user/INSTALLATION.md) |
| 3 | [user/FIRST_STEPS.md](../user/FIRST_STEPS.md) |
| 4 | [user/BETA_TESTER.md](../user/BETA_TESTER.md) |
| 5 | [TESTING.md](TESTING.md) |
| 6 | [deploy/DEPLOY.md](../deploy/DEPLOY.md) a [CRON.md](../deploy/CRON.md) |

## 15. Finálny release checklist

- [ ] immutable commit/tag a checksum,
- [ ] clean-clone acceptance,
- [ ] backend/frontend/CI gate green,
- [ ] security baseline green,
- [ ] content concurrency smoke,
- [ ] extension policy smoke,
- [ ] backup + restore dôkaz,
- [ ] upgrade + rollback dôkaz,
- [ ] production config review,
- [ ] docs, changelog a known issues aktuálne,
- [ ] žiadny otvorený blocker bez explicitného rozhodnutia,
- [ ] monitoring a incident owner pripravení.

Súvisiace: [PUBLIC_BETA1.md](../PUBLIC_BETA1.md), [CONTRIBUTING.md](CONTRIBUTING.md), [TESTING.md](TESTING.md), [SECURITY_REVIEW.md](../SECURITY_REVIEW.md).
