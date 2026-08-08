---
title: Testovanie a quality gates
description: Testovacia stratégia, izolácia dát, CI matica, security regresie a release dôkazy pre PaginiumCMS
icon: material/test-tube
---

# Testovanie a quality gates

## 0. Aktuálny implementačný snapshot — 2026-08-02

Aktuálny `scripts/run-all-tests.zsh` vykonáva **21 testovacích a kontrolných krokov**. Po nich nasleduje samostatný **post-run cleanup testovacích artefaktov**, ktorý sa nepočíta ako ďalší quality gate krok.

| # | Krok | Hlavný výstup |
|---:|---|---|
| 1 | PHPUnit — backend | Passed / Failed / Errors / Skipped |
| 2 | PHPStan — Level 8 | počet errors |
| 3 | Composer Audit | dependency advisories |
| 4 | Vitest — frontend funkčné testy | test files / tests |
| 5 | Frontend security tests | test files / tests |
| 6 | TypeScript `tsc --noEmit` | TS errors |
| 7 | ESLint | errors / warnings a baseline |
| 8 | Vitest MSW | mock API contract tests |
| 9 | Produkčný build + API URL verification | build status |
| 10 | NPM Audit | severity counts a disposition |
| 11 | Content diagnose | index / pages / orphans / unreadable |
| 12 | Security regression pack | storage / SSO / plugin / hardening |
| 13 | At-rest encryption pack | EncryptionService / users / settings |
| 14 | Log injection + SSRF guard | LogSanitizer / OutboundUrlGuard |
| 15 | Path ACL pack | service / guard / integration |
| 16 | WAF POST body pack | scanner / middleware / policy |
| 17 | UserRepository index pack | UserIndexService / repository |
| 18 | OTP rate-limit pack | middleware / workflow / resend limits |
| 19 | CodePolicy pack | untrusted paths + shortcode definitions |
| 20 | XSS / ZIP / security headers pack | rendering and archive boundaries |
| 21 | Security static grep | unguarded outbound-call hygiene |

Počet 21 je aktuálny snapshot, nie večná architektonická konštanta. Pri pridaní modulu alebo bezpečnostnej hranice sa môže runner rozšíriť; autoritatívny zostáva skript a CI workflow v konkrétnom tagu.

### Referenčný lokálny beh

Beh z **2. augusta 2026 o 16:18** zaznamenal 972 úspešných PHPUnit testov, 15 preskočených testov, 285 úspešných Vitest testov a jeden PHPStan L8 nález. Tento výsledok je historický diagnostický snapshot, nie trvalá garancia budúcich počtov.

### Dvojstupňové vyhodnotenie

```text
automatický výsledok 21 krokov
→ manuálna kontrola kompletného logu
→ PASS / PASS_WITH_REVIEW / INVESTIGATION_REQUIRED / FAILED
```

Manuálny review kontroluje aj warningy, zmeny počtov, nevysvetlené skipy, dependency nálezy, neočakávané sieťové requesty, build warnings a iné anomálie, hoci nástroj skončil exit kódom 0.

### Lokálne logy a GitHub CI

- Plný lokálny log sa zapisuje do `${PAGINIUMCMS_TEST_LOG_DIR:-$HOME/projects/paginiumcms_tests}/alltests_*.log`, teda štandardne **mimo repozitára**.
- Lokálny raw log môže obsahovať diagnostické hodnoty vytvorené testami; nesmie sa commitovať ani zdieľať bez sanitizácie.
- GitHub Actions používa `.github/scripts/run-backend-tests-ci.sh`, ktorý najprv zachytí raw výstup do dočasného priestoru runnera, následne spustí `sanitize-ci-log.py` a fail-closed `verify-ci-log-redaction.sh`.
- GitHub job log ani artefakt nesmie obsahovať raw TOTP seed, QR payload, `otpauth://` URI, OTP kód, bearer token, cookie alebo secret.
- Raw CI log sa nepublikuje a nepoužíva sa `tee` do verejnej konzoly.

Pred zdieľaním lokálneho logu:

```bash
RAW="$HOME/projects/paginiumcms_tests/alltests_DDMMYY_HHMM.log"
SAFE="${RAW%.log}.sanitized.log"
python3 .github/scripts/sanitize-ci-log.py "$RAW" > "$SAFE"
.github/scripts/verify-ci-log-redaction.sh "$SAFE"
```

Maintainer šablóna: [`../../LOCAL_TEST_LOGS.md.example`](../../../LOCAL_TEST_LOGS.md.example). Incident a implementované riešenie: [ISS-120](../ISSUES.md#iss-120).

> Tento dokument je živý kontrakt pre release rodinu **`v2.1.0-beta.*`**. Presný počet testov, názvy CI jobov a počet krokov pomocných skriptov sú vlastnosťou konkrétneho tagu. Nie sú nemennou vlastnosťou architektúry.

## 1. Cieľ testovacej stratégie

Testovanie PaginiumCMS má dokazovať viac než to, že jednotlivé metódy vracajú očakávanú hodnotu. Musí chrániť hlavné invarianty projektu:

- flat-file súbory zostávajú autoritatívnym zdrojom pravdy,
- zápisy sú atomické a konflikt sa neprepíše potichu,
- index, cache, Git publish, preklady a AI výstupy sú odvodené alebo asynchrónne vrstvy,
- autentifikácia, autorizácia, Path ACL, CSRF a 2FA sa vyhodnocujú na backende,
- používateľský vstup, uploady, archívy, outbound URL a extension kód prechádzajú príslušnou bezpečnostnou bránou,
- testy nemenia produkčné dáta ani produkčné nastavenia,
- release je reprodukovateľný z čistého klonu,
- upgrade, backup, restore a rollback sú overiteľné operácie, nie iba sľub v návode.

Zelený unit test je dôkaz jednej malej vlastnosti. Zelený release gate je súbor dôkazov o celom kandidátovi.

## 2. Zdroj pravdy pre testovacie príkazy

Staršie snapshoty opisovali menšiu sadu; aktuálny runner má 21 krokov a samostatný cleanup. Takéto číslo sa pri rozšírení sady prirodzene mení. Preto platí:

1. autoritatívny je skript v kontrolovanom commit/tagu,
2. CI workflow musí používať rovnaké lockfiles a podporovanú runtime maticu,
3. report má uvádzať názvy kategórií, výsledok, trvanie a identitu buildu,
4. dokumentácia nesmie garantovať konkrétny počet krokov bez väzby na release snapshot.

Odporúčaný discovery postup:

```bash
./scripts/iteration-gate.sh --help 2>/dev/null || true
./scripts/run-all-tests.zsh --help 2>/dev/null || true
grep -nE 'TOTAL_STEPS|run_step|composer |npm run|phpunit|phpstan' \
  scripts/iteration-gate.sh scripts/run-all-tests.zsh
```

Keď skript nemá `--help`, jeho obsah a CI workflow daného tagu sú zdrojom pravdy. Číslo z README spred troch verzií ním nie je — dokumentácia tiež občas potrebuje unit test. 🙂

## 3. Testovacia pyramída

| Vrstva | Účel | Typické závislosti | Očakávaná rýchlosť |
|---|---|---|---|
| statické kontroly | syntax, typy, architektonické pravidlá a zakázané vzory | zdrojový strom | sekundy až minúty |
| unit testy | izolovaný kontrakt služby alebo policy | temp filesystem, mock/fake | rýchle |
| integračné testy | spolupráca repository, storage, middleware a controllerov | reálny bootstrap, izolovaný storage | stredné |
| HTTP kontrakt testy | status, envelope, headers, auth a konflikty | test app + session | stredné |
| frontend component testy | UI stavy, formuláre, guards a accessibility | jsdom, MSW | rýchle až stredné |
| end-to-end smoke | kritický používateľský tok cez zostavenú aplikáciu | bežiaci stack | pomalšie |
| recovery testy | backup, restore, rebuild, upgrade a rollback | disposable prostredie | pomalé |
| security regresie | zneužiteľné hranice a minulý incident | viac vrstiev | podľa scenára |

Pyramída neznamená, že najviac testov automaticky znamená najvyššiu kvalitu. Kritické sú správne vybrané kontrakty a nezávislé prostredie.

## 4. Povinné gate úrovne

### 4.1 Rýchly lokálny gate

Spúšťa sa po menšej zmene a pred commitom:

```bash
./scripts/iteration-gate.sh
```

Má pokrývať aspoň:

- syntax zmenených PHP súborov,
- PHPStan na dotknutom rozsahu alebo celom `backend/app`,
- relevantné PHPUnit testy,
- TypeScript type-check,
- ESLint,
- API barrel/wiring integritu,
- kontrolu zakázaných extension vzorov pri zmenách v tejto oblasti.

Rýchly gate nesmie predstierať, že nahrádza produkčný build, dependency audit, restore test alebo plnú regresiu.

### 4.2 Plný lokálny gate

Používa sa pred väčším merge, release kandidátom a po zásahu do storage, auth, extensions alebo publish pipeline:

```bash
./scripts/run-all-tests.zsh
```

Očakávané kategórie:

- backend PHPUnit,
- PHPStan level 8,
- Composer audit,
- frontend Vitest,
- frontend security pack,
- TypeScript,
- ESLint a API wiring,
- MSW/API kontrakty,
- produkčný frontend build,
- npm audit podľa schválenej severity policy,
- content/storage diagnose,
- extension/static security pack,
- kontrolovaný cleanup test artefaktov.

Runner má skončiť nenulovým exit kódom, ak zlyhá povinný krok. Voliteľný krok musí byť vo výsledku označený ako `skipped` s dôvodom, nie potichu vynechaný.

### 4.3 CI gate

CI je nezávislý dôkaz z čistého checkoutu. Minimálne jobs:

| Job | Povinné dôkazy |
|---|---|
| Backend | Composer install z lockfile, PHPUnit, PHPStan L8, syntax, audit |
| Frontend | `npm ci`, type-check, lint, API barrel, Vitest, produkčný build, audit |
| Integration | bootstrap, HTTP contract, auth/CSRF/RBAC, storage diagnose |
| Security | SSRF, traversal/ZIP, CodePolicy, XSS/rendering, log sanitization, secret scan |
| Docs | odkazy, front matter, code fences a SK/EN parita zmenených dokumentov |
| Artifact | zostavenie, obsah archívu, SHA-256 a voliteľný SBOM/signing dôkaz |

CI nesmie čítať lokálny `.env`, reuseovať developer storage ani závisieť od ručne pripraveného súboru na self-hosted runneri.

### 4.4 Release acceptance

Release gate rozširuje CI o:

- clean-clone first-run,
- HTTP smoke zostaveného artefaktu,
- test upgradu z podporovanej predchádzajúcej verzie,
- backup + restore na oddelenom strome,
- rollback alebo roll-forward scenár,
- kontrolu, že artefakt neobsahuje `.env`, secrets, súkromné logy ani test dáta,
- immutable commit/tag a checksum.

Podrobný checklist: [BETA_INFRA.md](BETA_INFRA.md).

## 5. Backend testy

### 5.1 Unit testy

Unit test má overovať jeden kontrakt bez produkčného storage a siete. Preferované nástroje:

- reálny dočasný adresár vytvorený testom,
- filesystem adapter/fake,
- clock/UUID/random provider injektovaný cez DI,
- explicitný stub HTTP klienta,
- malé fixtures s jasným vlastníkom.

`vfsStream` môže byť užitočný, ale nesmie zakryť správanie reálneho filesystemu, napríklad rename, `fsync`, symlinky, permissions alebo cross-device move. Pre atomické zápisy a archívy je vhodný aj test na reálnom temp filesystéme.

### 5.2 Repository a storage testy

Každý autoritatívny writer má testovať:

- vytvorenie nového dokumentu,
- update s očakávanou revision,
- konflikt pri starej revision,
- validáciu schémy pred zápisom,
- write-to-temp + rename,
- cleanup temp súboru po chybe,
- zachovanie predchádzajúcej platnej verzie,
- rebuild indexu z diskového SSOT,
- správanie pri nečitateľnom alebo poškodenom súbore,
- paralelný alebo simulovaný súbeh dvoch writerov.

Index a cache sa netestujú ako jediný zdroj obsahu. Test musí vedieť odstrániť odvodenú vrstvu a obnoviť ju zo súborov.

### 5.3 HTTP integračné testy

HTTP testy majú bootstrapovať reálne routes a middleware, ale s izolovanou session a storage. Pre mutačný endpoint over minimálne:

| Scenár | Očakávanie |
|---|---|
| bez session | `401` alebo schválený public kontrakt |
| session bez oprávnenia | `403` |
| oprávnenie bez CSRF | `403`/kanonický CSRF kód |
| neplatný payload | `422` |
| stará revision alebo lock konflikt | `409` |
| platná mutácia | 2xx + audit + konzistentný disk |
| WAF blok pred routingom | odpoveď nemusí mať JSON envelope |

Nespoliehaj sa iba na text hlášky. Kontroluj status, content type, stabilný `code`, relevantné headers a stav storage.

Kontrakt odpovedí: [API_CONTRACT.md](../architecture/API_CONTRACT.md).

### 5.4 Testovacie prostredie

`APP_ENV=testing` musí byť nastavené pred bootstrapom. Test suite nemá automaticky načítať lokálny produkčný `.env`. Hodnoty potrebné pre test musia vzniknúť v test bootstrap/fixture vrstve.

Pred každým HTTP testom resetuj alebo izoluj:

- session,
- login/OTP/rate-limit state,
- fake clock,
- queue/outbox,
- firewall incident store,
- test settings repository,
- temp storage root.

Historické regresie, kde zlyhané login testy spôsobili neskorší `429` alebo lokálny demo flag zmenil OTP správanie, patria do trvalého regression packu.

## 6. Frontend testy

### 6.1 Component a hook testy

Frontend test má overovať správanie používateľa, nie internú implementáciu React komponentu. Preferuj:

- queries podľa role, labelu a accessible name,
- `userEvent` namiesto priameho volania handlera,
- kontrolu loading/empty/error/success stavu,
- test keyboard flow a focus návratu pri dialógoch,
- stabilné assertions nad viditeľným výsledkom.

Krehké textové snapshoty alebo assertion na presný počet interných renderov sú prípustné iba vtedy, keď je to skutočný kontrakt.

### 6.2 API a MSW

MSW handlery majú zodpovedať zdokumentovanému API kontraktu. Povinné varianty pre mutácie:

- success envelope,
- `401`,
- `403` JSON,
- plain-text/prázdny WAF `403`,
- `409` revision/lock konflikt,
- `422` field errors,
- `429` s retry informáciou,
- `500` bez úniku interného stack trace.

Frontend API client musí najprv vyhodnotiť status a content type a až potom parsovať telo.

### 6.3 Produkčný build

`npm test` nestačí. Gate má spustiť aj produkčný build a overiť:

- Vite importy a lazy chunks,
- správny base path,
- absence dev-only modulov,
- source map policy,
- asset manifest,
- žiadne vložené secrets alebo privátne API URL,
- kompatibilitu frontend extension modelu.

Frontend plugin kód načítaný cez `import.meta.glob` zostáva build-time artefakt. Test aktivácie backend pluginu nesmie falošne tvrdiť, že runtime načítal nový React bundle bez rebuild/deploy kroku.

## 7. Security regression pack

Bezpečnostný test má chrániť hranicu, nie iba názov triedy. Povinné rodiny:

| Oblasť | Minimálne scenáre |
|---|---|
| Auth | session fixation, logout, lockout, reset enumeration, 2FA replay |
| Authz | USER/EDITOR/ADMIN/SUPER_ADMIN, object/path access, queue identity |
| CSRF | chýbajúci, zlý, expirovaný token; exempt routes iba podľa allow-listu |
| Filesystem | `..`, encoded traversal, absolute path, symlink, race pri rename |
| Upload/ZIP | size, MIME, content sniff, Zip-Slip, symlink, archive bomb limity |
| SSRF | private/reserved IP, DNS rebinding, redirect revalidation, timeout |
| XSS/rendering | HTML/Markdown/SVG, dangerous URL schemes, stored content |
| Extensions | forbidden calls, manifest schema, disabled-after-import, rollback |
| Logging | CR/LF/ANSI/CSV injection, secret redaction, request correlation |
| Hybrid Engine | cache bypass, stale index, duplicate job, publish retry, locale isolation |
| AI/preklad | prompt injection boundary, schema tool call, authorization pri Apply |

Pomocné static grep skripty sú vhodná lacná poistka, ale nie jediný dôkaz. Grep nevie spoľahlivo rozlíšiť bezpečný wrapper, alias, dynamické volanie ani runtime redirect.

Bezpečnostný model: [SECURITY.md](SECURITY.md).

## 8. Hybrid Engine test matrix

Schopnosti It.68–77 sa pridávajú inkrementálne. Každá sa testuje v režime **feature off** aj **feature on**.

### 8.1 Index a cache

- aplikácia funguje bez Redis,
- neúspešný capability probe nezapne Redis backend,
- cache miss neznamená content miss,
- stale index je detegovaný a rebuildnuteľný,
- invalidácia po zápise je idempotentná,
- ETag/Last-Modified zodpovedá content revision,
- cache kľúč obsahuje locale a relevantný permission scope.

### 8.2 Git publish a jobs

- local save uspeje aj keď voliteľný Git push zlyhá,
- stavy `stored`, `pending_publish`, `committed`, `pushed`, `publish_failed` sa neprepisujú nepravdivo,
- retry používa idempotency key,
- dva workery nevytvoria duplicitný commit,
- job nesie identitu a oprávnenia iniciátora,
- dead-letter/failure stav je viditeľný a auditovaný.

### 8.3 Médiá

- metadata zostávajú flat-file SSOT,
- local a S3 driver majú rovnaký doménový kontrakt,
- neúplný upload nevytvorí platný published záznam,
- signed URL neobíde ACL,
- delete/restore rieši fyzický objekt aj metadata konzistentne.

### 8.4 Lokalizácia, preklad a AI

- locale fallback je deterministický,
- preklad vytvorí draft, nie automatický publish,
- diff je viazaný na vstupnú revision,
- Apply opätovne overí oprávnenie a revision,
- nedôveryhodný obsah nie je systémová inštrukcia,
- tool call musí prejsť JSON schema/allow-list validáciou,
- AI agent nemá shell, generic filesystem ani implicitný SUPER_ADMIN prístup.

## 9. Testovacie dáta a izolácia

### 9.1 Zásada nulového kontaktu s produkčnými dátami

Testy nesmú čítať ani zapisovať produkčné:

- `data/settings.json`,
- používateľské účty,
- obsah a navigáciu,
- médiá,
- logy a audit,
- backup archívy,
- dev tokeny,
- reálny queue/outbox.

Odporúčaný model:

```text
backend/storage-test/<unique-run-id>/
├── app/
├── data/
├── cache/
├── logs/
└── tmp/
```

Každý run dostane unikátny koreň. Paralelné CI joby nesmú zdieľať jeden `settings.testing.json` alebo index.

### 9.2 Cleanup

Najbezpečnejší cleanup je odstránenie celého unikátneho test rootu, ktorý vytvoril daný run. Prefixové heuristiky ako `*@example.com` sú vhodné iba ako prechodná ochrana historického zdieľaného storage.

Cleanup musí:

- bežať v `finally`/teardown fáze,
- odmietnuť cestu mimo test rootu,
- zapísať, čo odstránil,
- pri zlyhaní zachovať diagnostický artefakt podľa CI policy,
- nikdy nemať default smerujúci na produkčný storage.

`content:diagnose --fix` je opravný nástroj pre odvodené vrstvy. Nie je univerzálny cleanup produkčných dát po testoch.

## 10. Flaky testy

Flaky test je zlyhanie gate-u, nie folklór, ktorý sa vyrieši tretím kliknutím na „Re-run“. Pri flake:

1. označ kandidáta ako neoverený,
2. ulož seed, runtime, timezone, locale, poradie a log,
3. reprodukuj opakovaným behom alebo izolovaným testom,
4. oprav globálny state, race, čas, sieť alebo krehké assertion,
5. pridaj regression test,
6. až potom odstráň quarantine.

Automatický retry môže pomôcť diagnostike, ale nesmie premeniť červený prvý beh na zelený release dôkaz bez viditeľnej poznámky.

### 10.1 HTTP integračná izolácia (`Http/TestCase`)

HTTP controller testy bootujú reálny Slim app proti zdieľanému flat-file storage. Bez explicitnej hygieny vznikajú **order-dependent flakes** v plnom suite, hoci izolovaný test prejde.

| Pravidlo | Implementácia | Incident |
|----------|---------------|----------|
| Reset login lockout | `TestStorageCleaner::purgeLoginAttempts()` + `LoginAttemptTracker::clearAll()` v `setUp` | [ISS-073](../ISSUES.md#iss-073), [ISS-134](../ISSUES.md#iss-134) |
| Unikátna IP klienta | Syntetické `REMOTE_ADDR` v každom `createJsonRequest()` | [ISS-134](../ISSUES.md#iss-134) |
| Nastavenia až po auth | Ak test mení `settings.testing.json` a volá register/login, `setGroup()` až **po** session | [ISS-134](../ISSUES.md#iss-134) |
| Nepoužívať `SettingsRepository::reset()` v HTTP `setUp` | Vnorený `flock` cez validátor → deadlock | [ISS-134](../ISSUES.md#iss-134) |

Kanonický register: [ISS-134 v `docs/ISSUES.md`](../../ISSUES.md#iss-134) (anglická synopsa).

## 11. Dependency, SCA a supply-chain kontroly

Minimálne:

```bash
composer audit
cd frontend
npm audit --audit-level=high
```

Konkrétna severity policy musí byť verzovaná. Ak projekt dočasne akceptuje advisory:

- eviduj ID advisory,
- dotknutú verziu a surface,
- dôvod dočasnej akceptácie,
- kompenzačné opatrenie,
- ownera a dátum revízie,
- fix verziu alebo migračný plán.

`npm audit` exit code sám nerozhoduje o reálnej exploatovateľnosti, no ani SPA-only tvrdenie automaticky nezneplatní open-redirect alebo client-side XSS nález. Rozhodnutie musí byť zdokumentované v [ISSUES.md](../ISSUES.md) alebo security review.

Release artefakt má byť zostavený z lockfiles. Odporúčaný cieľ je doplniť SBOM a podpis/provenance podľa release procesu.

## 12. Výsledky, logy a artefakty

Test report má obsahovať:

- commit SHA/tag,
- UTC čas,
- PHP/Node/npm/Composer verzie,
- OS/container image,
- názov test kategórie,
- pass/fail/error/skipped,
- trvanie,
- seed alebo shard,
- odkaz na log/artefakt,
- jasný finálny exit status.

Log nesmie obsahovať session cookie, CSRF token, heslo, `APP_KEY`, OAuth secret, SMTP credentials ani plný citlivý payload. Pred zverejnením issue reportu log sanitizuj.

Odporúčaný machine-readable súhrn:

```json
{
  "commit": "<sha>",
  "startedAt": "2026-08-02T12:00:00Z",
  "environment": {"php": "<version>", "node": "<version>"},
  "checks": [
    {"name": "phpunit", "status": "passed", "durationSeconds": 82},
    {"name": "content-diagnose", "status": "passed", "durationSeconds": 3}
  ],
  "status": "passed"
}
```

## 13. Manuálne a smoke testy

### 13.1 Kritický admin smoke

- login a logout,
- 2FA podľa profilu,
- vytvorenie draftu,
- editácia a save,
- konflikt z druhej session,
- publish podľa implementovaného lifecycle,
- media upload,
- RBAC/Path ACL deny,
- audit a log záznam,
- backup a restore na oddelenom strome.

### 13.2 Postman/Newman

Kolekcia v `docs/api/PaginiumCMS.postman_collection.json` je doplnkový smoke subset:

```bash
npx newman run docs/api/PaginiumCMS.postman_collection.json \
  --env-var baseUrl=http://127.0.0.1:8080
```

Nie je úplnou OpenAPI špecifikáciou ani náhradou HTTP kontrakt testov. Tajomstvá nevkladaj priamo do kolekcie.

### 13.3 Produkčný smoke

Na produkcii spúšťaj iba bezpečné post-deploy overenie:

```bash
curl --fail --silent https://example.test/api/health
php backend/bin/console content:diagnose
php backend/bin/console content:locale-migrate inventory
```

Voliteľná operátorská kontrola pred migráciou (It.73): `content:locale-migrate dry-run --default-locale=sk` pred `run --yes`. Rollback: `content:locale-migrate rollback --migration-id=<id> --yes`.

Plnú PHPUnit/Vitest sadu spúšťaj v CI alebo disposable prostredí, nie nad živým produkčným storage. Produkčný server nie je veľmi drahý test fixture.

## 14. Definition of Done pre zmenu

Zmena je hotová, keď:

- test chráni nový alebo opravený kontrakt,
- negatívna a permission cesta je pokrytá tam, kde je relevantná,
- test používa izolované dáta,
- rýchly gate je zelený,
- požadovaný plný/CI gate je zelený,
- dokumentácia a API kontrakt sú aktualizované,
- nový incident má regression test a odkaz v `ISSUES.md`,
- skip/quarantine má dôvod, ownera a expiry,
- release-impacting zmena má upgrade/rollback dôkaz.

## 15. Súvisiace dokumenty

- [Vývojársky workflow](DEVELOPMENT.md)
- [Coding standards](CODING_STANDARDS.md)
- [Beta infra a release readiness](BETA_INFRA.md)
- [Bezpečnostná architektúra](SECURITY.md)
- [API kontrakt](../architecture/API_CONTRACT.md)
- [Storage architektúra](../architecture/STORAGE.md)
- [Versioning a konflikty](../architecture/VERSIONING.md)
- [Security review](../SECURITY_REVIEW.md)
- [Záznam issues a regresií](../ISSUES.md)
