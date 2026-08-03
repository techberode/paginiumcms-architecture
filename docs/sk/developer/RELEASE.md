---
title: Release lifecycle a produkčný gate
description: Verzionovanie, 21-krokový testovací gate, manuálny review, CI sanitizácia, artefakty, tagging, deploy, upgrade a rollback pre PaginiumCMS
icon: material/package-variant-closed-check
---

# Release lifecycle a produkčný gate

> Tento dokument je živý release kontrakt pre rodinu **`v2.1.0-beta.*`** a jej následníkov. Historický zdroj `RELEASE.md` obsahoval desiatky copy-paste blokov pre jednotlivé verzie. Táto revízia zachováva overiteľnú kontinuitu vydaní, ale oddeľuje **proces vydania** od **historických release notes**, ktoré patria do `CHANGELOG.md`.

## 1. Účel a rozsah

Release nie je iba Git tag ani úspešný príkaz `git push`. Je to zdokumentované rozhodnutie, že konkrétny immutable commit:

- prešiel povinnými automatickými kontrolami,
- bol manuálne preskúmaný vrátane kompletného testovacieho logu,
- má vyriešené alebo formálne vyhodnotené anomálie,
- vznikol z reprodukovateľného prostredia a lockfiles,
- má pripravený artefakt, checksum a release manifest,
- má overený upgrade, backup, restore a rollback/roll-forward postup,
- nepublikuje tajomstvá v CI logu ani artefaktoch,
- obsahuje pravdivé release notes a známe obmedzenia,
- bol nasadený alebo je pripravený na nasadenie do presne pomenovaného profilu.

Dokument sa vzťahuje na:

- beta, release candidate, stabilné a hotfix vydania,
- lokálny 21-krokový testovací runner,
- GitHub Actions CI,
- GitHub Release a distribuované archívy,
- produkčné, demo a staging nasadenia,
- administrátorské self-update mechanizmy,
- release dokumentáciu a následný incident/hotfix proces.

Nasadenie nginx, Dockeru a cronu je podrobne spracované v nasledujúcej dokumentačnej iterácii. Tu sa definuje ich release kontrakt, nie kompletná serverová konfigurácia.

## 2. Zdroje pravdy

Pri rozpore platí toto poradie:

1. commit identifikovaný release tagom,
2. lockfiles, CI workflow a testovacie skripty v tomto commite,
3. release manifest a checksum artefaktu,
4. `CHANGELOG.md` a GitHub Release daného tagu,
5. tento procesný dokument,
6. staršie copy-paste bloky alebo historické poznámky.

Slovo `latest`, lokálna vetva bez commit hash alebo ručne upravený serverový strom nie sú release identita.

Kanonická identita vydania:

```text
repository + commit SHA + annotated tag + artifact SHA-256
```

Voliteľne sa pridáva:

```text
SBOM digest + provenance/signature identity
```

## 3. Typy vydaní

| Typ | Príklad | Účel | Minimálny gate |
|---|---|---|---|
| vývojový snapshot | bez verejného tagu | interné overenie vetvy | relevantné lokálne testy |
| beta | `v2.1.0-beta.24` | verejne testovateľná funkčná verzia | plný gate + manuálny review + CI |
| release candidate | `v2.1.0-rc.1` | kandidát bez plánovaných funkčných zmien | beta gate + upgrade/rollback acceptance |
| stabilné vydanie | `v2.1.0` | podporovaný produkčný release | všetky release dôkazy a schválený deploy |
| hotfix | `v2.1.1` alebo beta patch | úzka urgentná oprava | relevantná regresia + plný povinný gate |
| security release | podľa SemVer dopadu | oprava zraniteľnosti | koordinovaný security gate a advisory proces |

Beta neznamená, že je prípustný authz/CSRF bypass, poškodenie SSOT, únik secretov alebo nereprodukovateľný upgrade. Beta môže mať nedokončené schopnosti, nie ignorované kritické invarianty.

## 4. Verzionovanie a kontinuita

PaginiumCMS používa SemVer formát s prerelease suffixom. Pre aktuálnu beta rodinu:

```text
v2.1.0-beta.N
```

Pravidlá:

- `N` sa zvyšuje monotónne a tag sa nerecykluje,
- tag je annotated a smeruje na schválený release commit,
- obsah existujúceho tagu sa nikdy neprepisuje force-pushom,
- verzia v aplikácii, API health odpovedi, artefakte, release manifeste a GitHub Release musí súhlasiť,
- funkčná zmena po vydaní vytvára nový tag; neupravuje starý artefakt,
- hotfix po stabilnom vydaní zvyšuje patch; hotfix v beta vetve zvyšuje prerelease číslo, pokiaľ rozhodnutie o novej minor vetve nehovorí inak.

Pred vytvorením tagu:

```bash
git status --short
git rev-parse HEAD
git log -1 --show-signature --format=fuller
git fetch origin --tags --prune
git tag --list 'v2.1.0-beta.*' --sort=version:refname | tail
```

## 5. Roly a oddelenie zodpovedností

Aj keď projekt spravuje jeden človek, proces rozlišuje logické roly:

| Rola | Zodpovednosť |
|---|---|
| autor zmeny | implementácia, testy, migrácie a dokumentácia |
| gate owner | spustenie automatických kontrol a zachovanie výstupu |
| reviewer logu | manuálna kontrola warningov, skipov a anomálií |
| release owner | finálne rozhodnutie, verzia, tag a release notes |
| deploy owner | backup, deploy, smoke a rollback pripravenosť |
| security reviewer | audity, secrets, advisories a risk disposition |

Jedna osoba môže vykonávať všetky roly, ale jednotlivé rozhodnutia sa v release zázname nemajú zlúčiť na „skript bol zelený“. Manuálny review je samostatný dôkaz.

## 6. Stav kandidáta a rozhodovací model

Každý automatický krok a celý kandidát používajú rozšírené stavy:

| Stav | Význam |
|---|---|
| `PASS` | krok prešiel a výstup neobsahuje významnú anomáliu |
| `PASS_WITH_REVIEW` | exit je úspešný, ale výstup potrebuje zdokumentované posúdenie |
| `INVESTIGATION_REQUIRED` | výsledok nie je možné akceptovať bez preverenia |
| `FAILED` | povinný krok zlyhal alebo bol porušený release invariant |
| `SKIPPED_EXPECTED` | schválený skip s dôvodom a vlastníkom |
| `SKIPPED_UNEXPLAINED` | neakceptovateľný skip bez dôvodu |
| `NOT_APPLICABLE` | krok sa na daný typ vydania nevzťahuje a dôvod je zapísaný |

Celý release môže byť označený `READY` iba vtedy, keď:

- neexistuje `FAILED`,
- neexistuje otvorené `INVESTIGATION_REQUIRED`,
- každý `PASS_WITH_REVIEW` má rozhodnutie,
- každý skip je vysvetlený,
- CI a lokálny výsledok sú porovnateľné,
- release owner podpísal finálny checklist.

## 7. Predrelease freeze a vstupné podmienky

Pred spustením finálneho gate-u sa vytvorí release candidate commit. Počas freeze sa povoľujú iba:

- opravy nálezov z gate-u,
- testy dokazujúce opravu,
- release dokumentácia,
- nevyhnutné version metadata,
- bezpečné úpravy CI/reportingu.

Funkcia je pripravená na release gate, keď má:

- implementačný a bezpečnostný kontrakt,
- backend authorization pre všetky mutácie,
- validáciu, audit a recovery správanie,
- relevantné unit/integračné/frontend testy,
- upgrade/migration dopad,
- používateľskú a technickú dokumentáciu,
- zoznam známych obmedzení.

## 8. Lokálny 21-krokový testovací gate

Aktuálny runner z kontrolného behu **2026-08-02 16:18** obsahoval presne 21 krokov. Počet sa môže pri rozširovaní projektu meniť; názvy a výsledky konkrétneho release tagu sú autoritatívne.

| # | Kategória |
|---:|---|
| 1 | PHPUnit backend testy |
| 2 | PHPStan Level 8 |
| 3 | Composer Audit |
| 4 | Vitest frontend funkčné testy |
| 5 | frontend bezpečnostné testy |
| 6 | TypeScript `tsc --noEmit` |
| 7 | ESLint |
| 8 | Vitest MSW handlery |
| 9 | produkčný build a overenie API URL |
| 10 | NPM Audit |
| 11 | content diagnose |
| 12 | security regression pack |
| 13 | at-rest encryption pack |
| 14 | log-injection a SSRF guard pack |
| 15 | Path ACL pack |
| 16 | WAF POST body pack |
| 17 | UserRepository index pack |
| 18 | OTP rate-limit pack |
| 19 | CodePolicy pack |
| 20 | XSS/ZIP/headers pack |
| 21 | security static grep pre outbound hygiene |

Runner musí:

- zachovať exit kód každého povinného kroku,
- uviesť trvanie, výsledok a stručné metriky,
- nevydávať krok s nálezmi za bezpodmienečný `PASS`,
- uviesť konkrétnu cestu a popis chyby,
- vykonať kontrolovaný cleanup testovacích artefaktov,
- exportovať kompletný lokálny log mimo projektu na manuálnu kontrolu.

### Aktuálny pozorovaný snapshot

Kontrolný log z 2026-08-02 uvádzal:

```text
PHPUnit: 972 passed, 0 failed, 0 errors, 15 skipped
PHPStan Level 8: 1 error
Composer Audit: no advisories
Vitest: 93 files / 285 tests passed
Frontend security: 3 files / 17 tests passed
Content diagnose: index 522, pages 519, orphans 0, unreadable 0
NPM Audit: 3 high severity vulnerabilities, hoci príkaz mal critical threshold
```

Tento snapshot je release evidence pre daný beh, nie nový nemenný počet testov. Kandidát z tohto behu nie je `READY`, kým sa neuzavrie PHPStan chyba a manuálne disposition ďalších anomálií.

## 9. Manuálna kontrola kompletného logu

Kompletný lokálny log je uložený v adresári úplne mimo projektu a Git repozitára. Reviewer nekontroluje iba riadky označené `Failed` alebo `Error`.

Povinné kontrolné oblasti:

- nové warningy a deprecations,
- zmena počtu testov alebo assertions,
- nové alebo nevysvetlené `Skipped`,
- audit findings pri úspešnom exit kóde,
- reálne sieťové requesty počas izolovaných testov,
- `ECONNREFUSED`, `AbortError`, `AggregateError`, socket chyby alebo unhandled rejections,
- veľkosť produkčných bundle a build warningy,
- odlišné výsledky medzi lokálom a CI,
- cleanup pred/po a zvyšné testovacie artefakty,
- citlivé hodnoty vo výstupe,
- časové alebo výkonové regresie,
- nenulový exit zachytený wrapperom bez správneho označenia.

Každá anomália dostane:

```text
ID / zdrojový krok / závažnosť / vlastník / rozhodnutie / dôkaz uzavretia
```

Možné rozhodnutia:

- opravené pred release,
- false positive s dôkazom,
- environmentálny očakávaný stav,
- akceptované riziko s dôvodom, expiráciou a follow-up issue,
- release blocker.

## 10. Skipped testy

Skip nie je automaticky chyba, ale musí byť vysvetlený. Release report má pri každom skipe alebo skupine uviesť:

- test suite a názov,
- dôvod,
- či ide o environmentálnu závislosť,
- či je daný kontrakt krytý iným testom,
- vlastníka a plán odstránenia, ak je dočasný.

Odporúčaný výstup:

```bash
./vendor/bin/phpunit --display-skipped
```

Kritické oblasti ako backup/restore, auth, storage alebo security policy nesmú byť plošne preskočené bez release rozhodnutia.

## 11. Dependency audit policy

Exit kód auditu a závažnosť nálezov sú dve odlišné veci. Napríklad príkaz:

```bash
npm audit --audit-level=critical
```

môže úspešne skončiť aj pri `high` nálezoch. Súhrn preto musí uvádzať počty podľa severity a stav manuálneho posúdenia.

Každý nález má mať disposition:

- opravený bezpečným update,
- nereachable v používanom režime s dôkazom,
- mitigovaný konfiguráciou alebo vypnutou funkciou,
- dočasne akceptovaný s termínom,
- release blocker.

`npm audit fix --force` sa nespúšťa bez review breaking zmien, lockfile diffu a plného testovacieho gate-u.

## 12. GitHub Actions CI

CI poskytuje nezávislý čistý beh a nesmie iba reprodukovať stav lokálneho workspace. Musí používať:

- fresh checkout release commitu,
- `composer install` a `npm ci` z lockfiles,
- explicitnú runtime maticu,
- izolovaný test storage,
- žiadny lokálny `.env`,
- deterministický bootstrap,
- rovnaké povinné kategórie gate-u,
- branch protection pre povinné jobs.

Lokálny log a GitHub CI log sú dva samostatné dôkazy:

```text
lokálny kompletný log mimo projektu
+ nezávislý GitHub CI run
= release review evidence
```

Rozdiel výsledkov medzi nimi sa nesmie potichu ignorovať.

## 13. Ochrana citlivých údajov v CI logu

Hlavné pravidlo:

```text
Citlivý údaj sa nesmie dostať na STDOUT ani STDERR.
```

Testy nesmú vypisovať celé odpovede obsahujúce:

- TOTP/2FA secret,
- QR Base64 payload,
- `otpauth://` provisioning URI,
- OTP/TOTP kód,
- heslá a password confirmation,
- API, access, refresh alebo reset token,
- session ID, cookie alebo `Authorization` hlavičku,
- private key alebo provider credential.

Implementácia má tri vrstvy:

1. spoločný `SensitiveDataRedactor` v testovacej/support vrstve,
2. zachytenie raw CI výstupu do `$RUNNER_TEMP` a publikovanie iba sanitizovaného logu,
3. GitHub `::add-mask::` pre dynamické dlhšie hodnoty ako dodatočná poistka.

Raw CI výstup sa nesmie posielať cez `tee` ani uploadovať ako artefakt.

Odporúčaný workflow vzor:

```yaml
- name: Run full release gate safely
  shell: bash
  run: |
    set +e
    ./scripts/run-all-tests.zsh > "$RUNNER_TEMP/alltests.raw.log" 2>&1
    gate_exit=$?
    set -e

    python3 .github/scripts/sanitize-ci-log.py \
      "$RUNNER_TEMP/alltests.raw.log" \
      > "$RUNNER_TEMP/alltests.safe.log"

    .github/scripts/verify-ci-log-redaction.sh \
      "$RUNNER_TEMP/alltests.safe.log"

    cat "$RUNNER_TEMP/alltests.safe.log"
    exit "$gate_exit"
```

Sanitizácia musí byť fail-closed. Ak zostane `otpauth://`, secret JSON field, bearer token alebo podobný vzor, job zlyhá.

Povolený artefakt je iba sanitizovaný report s krátkou retenciou. Debug trace `set -x`, `bash -x`, `ACTIONS_STEP_DEBUG` a `ACTIONS_RUNNER_DEBUG` sa pri práci so secrets nepoužívajú.

## 14. Release evidence bundle

Každý release candidate má vytvoriť evidence adresár, napríklad:

```text
release-evidence/v2.1.0-beta.24/
├── manifest.json
├── checksums.sha256
├── test-summary.json
├── manual-review.md
├── dependency-audit.json
├── artifact-inventory.txt
├── upgrade-report.md
├── rollback-report.md
└── sanitized-ci-log.txt
```

Tento adresár nemusí byť súčasťou distribučného balíka. Môže byť uložený ako chránený release artefakt alebo interný záznam podľa citlivosti.

Minimálny manifest:

```json
{
  "version": "2.1.0-beta.24",
  "tag": "v2.1.0-beta.24",
  "commit": "FULL_COMMIT_SHA",
  "builtAt": "2026-08-02T16:18:20Z",
  "artifact": "paginiumcms-2.1.0-beta.24.zip",
  "sha256": "...",
  "gate": "READY",
  "manualReview": "approved",
  "upgradeFrom": ["v2.1.0-beta.23"]
}
```

## 15. Zostavenie release artefaktu

Artefakt musí vzniknúť z čistého checkoutu tagovaného commitu, nie zo špinavého developer workspace.

Odporúčaný postup:

```bash
git clone --no-local <repository> release-build
cd release-build
git checkout --detach <full-commit-sha>
composer install --no-dev --prefer-dist --no-interaction --classmap-authoritative
cd frontend
npm ci
npm run build:prod
cd ..
```

Presný build príkaz je autoritatívny v release skripte daného tagu.

Artefakt nesmie obsahovať:

- `.git/`, `.github/` podľa distribučnej policy,
- `.env` alebo lokálne secrets,
- test logs a raw CI logs,
- test fixtures mimo runtime potreby,
- `node_modules`, cache a temp súbory,
- používateľský content, backupy alebo uploady,
- editor swap súbory a IDE metadata,
- private security incident log.

Musí obsahovať všetko potrebné na podporovaný first-run alebo jasne deklarovať externé build/runtime závislosti.

## 16. Checksum, SBOM a provenance

Minimálna integrita:

```bash
sha256sum paginiumcms-2.1.0-beta.24.zip \
  > paginiumcms-2.1.0-beta.24.zip.sha256
sha256sum -c paginiumcms-2.1.0-beta.24.zip.sha256
```

Odporúčané rozšírenie:

- CycloneDX alebo SPDX SBOM,
- podpis artefaktu alebo attestácia,
- digest kontajnerového image,
- build provenance naviazaná na commit a workflow run,
- inventár third-party licencií.

Checksum chráni pred neúmyselnou zmenou; sám osebe nedokazuje pôvod, ak checksum publikuje rovnaký kompromitovaný kanál.

## 17. Changelog a release notes

`CHANGELOG.md` je historický záznam zmien. GitHub Release je čitateľný distribučný záznam konkrétneho tagu. Tento dokument je proces.

Release notes majú obsahovať:

- stručné zhrnutie a cieľové publikum,
- Added / Changed / Fixed / Security,
- breaking changes a migrácie,
- konfigurácie alebo environment zmeny,
- známe obmedzenia,
- upgrade a rollback poznámky,
- testovací súhrn bez tajomstiev,
- odkazy na relevantné issue a dokumentáciu,
- checksum a prípadne SBOM.

Nesmú obsahovať neoverené marketingové tvrdenie typu „enterprise-ready“ len preto, že gate prešiel.

## 18. Release commit a annotated tag

Pred tagom:

```bash
git status --porcelain
# expected: empty

git rev-parse --verify HEAD
git diff --exit-code
git diff --cached --exit-code
```

Tag:

```bash
git tag -a v2.1.0-beta.24 \
  -m "v2.1.0-beta.24 — concise release title" \
  <full-commit-sha>
git show v2.1.0-beta.24 --no-patch
git push origin v2.1.0-beta.24
```

Ak tag ukazuje na nesprávny commit a ešte nebol publikovaný, opraví sa pred pushom. Publikovaný tag sa nepresúva; vytvorí sa nový prerelease patch a incident sa zdokumentuje.

## 19. GitHub Release

GitHub Release sa vytvára až po pushi tagu a overení, že:

- CI beží na rovnakom commit SHA,
- artefakt checksum sedí,
- release notes odkazujú na správne anchors,
- artefakty neobsahujú raw log alebo secrets,
- prerelease/latest flag zodpovedá typu vydania.

Príklad s GitHub CLI:

```bash
gh release create v2.1.0-beta.24 \
  paginiumcms-2.1.0-beta.24.zip \
  paginiumcms-2.1.0-beta.24.zip.sha256 \
  --prerelease \
  --title "v2.1.0-beta.24 — release title" \
  --notes-file release-notes.md
```

Automatizačný skript nesmie publikovať release, ak evidence manifest nie je `READY`.

## 20. Prednasadzovací backup

Pred produkčným alebo demo deployom sa vytvorí a overí backup autoritatívnych dát a konfigurácie potrebnej na obnovu:

- flat-file content a metadata,
- používateľské a security dáta,
- settings a encrypted secrets,
- media metadata a podľa storage profilu binárne objekty,
- `APP_KEY`/encryption key podľa bezpečnej key management policy,
- relevantné deployment env a compose/nginx verzie bez publikovania secretov.

Backup bez restore testu je iba nádej zabalená v ZIP-e. Pred release sa musí minimálne overiť hash a čitateľnosť; pri významnom storage zásahu aj restore do disposable prostredia.

## 21. Upgrade acceptance

Upgrade test sa vykonáva z každej deklarovanej podporovanej predchádzajúcej verzie alebo z explicitne zvoleného minimálneho baseline.

Kontroluje sa:

1. backup pôvodného stavu,
2. deploy nového artefaktu/tagu,
3. schema/content migration alebo lazy migration,
4. rebuild indexu/cache z SSOT,
5. login, 2FA, RBAC a ACL,
6. čítanie a zápis existujúceho obsahu,
7. média a public routes,
8. scheduler/workery,
9. audit/logging,
10. health/version endpoint,
11. zachovanie encrypted secrets,
12. idempotentnosť opakovaného migration kroku.

Upgrade sa nesmie testovať iba nad prázdnym fresh installom.

## 22. Deploy a smoke

Deploy musí explicitne pomenovať:

- prostredie,
- predchádzajúcu a cieľovú verziu,
- commit/tag,
- backup ID,
- deploy command/workflow run,
- ownera,
- začiatok/koniec,
- výsledok smoke testu.

Minimálny smoke:

```text
health/version
public read
admin login + 2FA podľa policy
CSRF protected mutation
content create/update s revision
media read/upload podľa profilu
settings read
logs/audit
scheduler/worker health
```

Feature-specific smoke sa pridáva podľa release scope. Deploy skript musí skončiť neúspešne pri nesprávnej health verzii.

## 23. Rollback a roll-forward

Rollback plán musí existovať pred deployom. Rozlišuje:

- kódový rollback na predchádzajúci immutable tag,
- restore dát zo snapshotu,
- roll-forward opravou kompatibilnou s už zmenenými dátami.

Pri nevratnej migrácii nie je „git checkout starého tagu“ platný rollback. Release notes musia uviesť, že návrat vyžaduje restore alebo roll-forward.

Rollback trigger:

- health failure,
- login/authz/CSRF regresia,
- poškodenie alebo strata SSOT,
- nečitateľný existujúci content,
- nefunkčný scheduler/worker pre kritický tok,
- významný secret/log leak,
- neakceptovateľná chybovosť alebo výkonová regresia.

Po rollbacku sa vykoná smoke, audit konzistencie a incident záznam.

## 24. Hotfix proces

Hotfix nezačína priamou editáciou produkčného servera. Postup:

1. reprodukovať problém alebo zdokumentovať núdzovú podmienku,
2. vytvoriť fix z aktuálneho podporovaného tagu/vetvy,
3. doplniť regresný test,
4. spustiť relevantný rýchly gate,
5. spustiť všetky povinné release kroky,
6. manuálne skontrolovať log,
7. vytvoriť nový immutable tag,
8. deploynúť, smoke, monitorovať,
9. merge/cherry-pick späť do hlavnej vývojovej vetvy,
10. aktualizovať `ISSUES.md`, `CHANGELOG.md` a security záznam podľa potreby.

Urgentnosť môže skrátiť rozsah nepovinných testov, nie vypnúť auth, storage alebo security invarianty bez explicitného incidentného rozhodnutia.

## 25. Security release a zraniteľnosti

Pri security náleze sa najprv rozhodne, či je potrebná koordinovaná neveřejná oprava. Verejné issue nesmie pred fixom prezradiť exploit detaily alebo secrets.

Security release obsahuje:

- affected versions,
- opravenú verziu,
- severity a podmienky zneužitia v primeranom rozsahu,
- mitigácie,
- upgrade urgency,
- rotáciu credentials, ak mohli uniknúť,
- test dokazujúci opravu,
- advisory/CVE/GHSA odkaz, ak existuje.

Ak sa tajomstvo zobrazilo v GitHub CI logu:

1. odstrániť logy workflow runu,
2. rotovať alebo zneplatniť údaj,
3. preveriť artifacts,
4. opraviť zdroj výpisu a sanitizáciu,
5. znovu spustiť CI.

## 26. Release retention a evidencia

Zachováva sa:

- tag a commit,
- GitHub Release,
- checksum a manifest,
- release notes,
- sanitizovaný test summary,
- manuálne release rozhodnutie,
- upgrade/rollback dôkaz,
- SBOM/signature podľa policy.

Raw lokálny diagnostický log zostáva mimo projektu a má vlastnú retenciu/prístupové pravidlá. Raw CI log s tajomstvami sa nezachováva ako artifact.

## 27. Reusable checklist

### Identita

- [ ] verzia je monotónna a konzistentná,
- [ ] release commit je immutable a workspace čistý,
- [ ] annotated tag smeruje na presný commit,
- [ ] app/API/manifest/artefakt uvádzajú rovnakú verziu.

### Quality a security gate

- [ ] všetkých 21 aktuálnych krokov alebo ich nástupcov bolo spustených,
- [ ] povinné kroky majú úspešný exit,
- [ ] manuálny review kompletného logu je hotový,
- [ ] skipped testy majú dôvod,
- [ ] dependency findings majú disposition,
- [ ] CI je nezávisle zelené,
- [ ] CI log a artifacts neobsahujú secrets.

### Artefakt

- [ ] build vznikol z čistého checkoutu,
- [ ] inventár neobsahuje `.env`, content, backupy ani raw logs,
- [ ] SHA-256 bol vytvorený a overený,
- [ ] manifest je `READY`,
- [ ] SBOM/provenance boli vytvorené podľa release policy.

### Prevádzka

- [ ] backup existuje a je overený,
- [ ] upgrade test prešiel,
- [ ] rollback/roll-forward je vykonateľný,
- [ ] deploy owner a target sú pomenované,
- [ ] smoke test prešiel,
- [ ] monitoring po deployi neukazuje kritickú regresiu.

### Dokumentácia

- [ ] `CHANGELOG.md` je aktualizovaný,
- [ ] GitHub Release je pravdivý a úplný,
- [ ] upgrade/config zmeny sú popísané,
- [ ] známe obmedzenia sú zverejnené,
- [ ] relevantné issue majú odkazy na opravu/release.

## 28. Release decision record

Odporúčaná šablóna:

```markdown
# Release decision — v2.1.0-beta.24

- Commit: `...`
- Artifact SHA-256: `...`
- Local gate: `PASS_WITH_REVIEW`
- GitHub CI: `PASS`
- Manual log review: `APPROVED`
- Dependency findings: `documented`
- Upgrade baseline: `v2.1.0-beta.23`
- Backup/restore: `verified`
- Rollback: `verified`
- Decision: `READY`
- Approved by: `...`
- Date: `...`
```

## 29. Historická kontinuita vydaní

Nasledujúci index bol odvodený z pôvodného `docs/developer/RELEASE.md`. Zachováva verzie a názvy historických release sekcií. Detailné Added/Changed/Fixed záznamy sa konsolidujú v dvojjazyčnom `CHANGELOG.md` v Iterácii 14.

| Verzia | Pôvodný názov sekcie | Stav dokumentácie |
|---|---|---|
| `v2.1.0-beta.23` | It.58c Layout Switch + LayoutPreviewFrame | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.22` | It.66 security write-time gate + It.65 Phase 3 | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.21` | It.65 Feature gallery Phase 2 + SEO/logging | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.20` | It.65 Feature gallery Phase 1 + footer UX | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.19` | It.64 Footer social + Analytics SPA beacon | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.18` | It.61 Phase 5 + It.63 v2/v3 | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.17` | Newsletter Phase 4 + footer modal + cookie consent | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.16` | Newsletter v2 (It.61 Phases 1–3) + BE↔FE wiring | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.15` | Version check UX + security audit fixes (It.63 v2) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.14` | Docker admin deploy bootstrap (It.63) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.13` | AppRoot hotfix + system update UX (It.63) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.12` | Admin system update MVP (It.63) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.11` | Demo security polish (It.13 v4) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.10` | Demo sandbox full trial (It.13 v3) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.9` | Scheduler, newsletter, demo deploy (It.62 + It.61 + ISS-098) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.8` | It.58b color schemes (ISS-093) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.7` | Deps, Vitest, It.58 doc (ISS-089–092) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.6` | Security audit (ISS-086–088) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.5` | It.56 Rich navigation + session fix | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.4` | It.57 Auto tags & meta description | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.3` | Beta 1 patch (React Router GHSA + CMS info) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.2` | Beta 1 Testing (pre-push security gate) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `v2.1.0-beta.1` | Public Beta 1 (Wave 7) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.58` | Wave 6 Beta infra gate | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.57` | Wave 5f Docker onboarding + user docs | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.56` | Password confirmation (register + admin users) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.55` | Wave 5e API barrel + CONTRIBUTING (It.17 MVP) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.54` | Wave 5d hook emitters + extension policy (It.15d) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.53` | It.59 scheduled publish (editor + cron) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.52` | branding, ACL v nastaveniach, CI (ISS-072–074) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.51` | ops hotfix + maintenance + logs (ISS-063–071) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.50` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.49` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.48` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.47` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.46` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.45` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.44` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.43` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.42` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.41` | pred release kontrola (legacy commit label) | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.40` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.39` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.38` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.37` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.34` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.32` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.31` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |
| `2.0.30` | pred release kontrola | historický záznam v pôvodnom `RELEASE.md`; detail bude konsolidovaný v `CHANGELOG.md` |

## 30. Definition of Done

Release lifecycle je implementovaný, keď:

- runner reportuje všetky povinné kroky a ich exit kódy,
- aktuálny počet 21 krokov je evidovaný ako snapshot, nie večná konštanta,
- kompletný lokálny log sa ukladá mimo projektu a manuálne kontroluje,
- GitHub CI robí nezávislý čistý beh,
- testy nevypisujú TOTP, QR, tokeny alebo credentials,
- CI publikuje iba sanitizovaný log a redakcia je fail-closed,
- dependency audit findings majú explicitné disposition,
- skipped testy majú dôvod,
- artefakt vzniká z čistého checkoutu a má checksum/manifest,
- tag je immutable,
- upgrade, backup, restore a rollback sú overené,
- release notes a changelog sú konzistentné,
- rozhodnutie `READY` je samostatný, auditovateľný záznam.

## Súvisiace dokumenty

- [Testovanie a quality gates](TESTING.md)
- [Bezpečnostná architektúra vývoja](SECURITY.md)
- [Beta infra a release readiness](BETA_INFRA.md)
- [Lokálne vývojové prostredie](LOCAL_SETUP.md)
- [Architektonické verziovanie obsahu](../architecture/VERSIONING.md)
- [Režimy nasadenia](../architecture/DEPLOYMENT_MODES.md)
- [Register incidentov](../ISSUES.md)
- [Changelog](../../../CHANGELOG.md)
