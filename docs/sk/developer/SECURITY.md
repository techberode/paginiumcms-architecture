---
title: Bezpečnostná architektúra vývoja
description: Threat model, trust boundaries, middleware, secrets, extensions, supply chain a bezpečnostné gates PaginiumCMS
icon: material/shield-lock
---

# Bezpečnostná architektúra vývoja

## 0. Aktuálny implementačný snapshot — Beta 1

Táto sekcia pomenúva konkrétne ochrany potvrdené v aktualizovanom implementačnom stave. Neskoršie kapitoly zostávajú širším bezpečnostným kontraktom a cieľovou architektúrou.

| Hranica | Aktuálny mechanizmus |
|---|---|
| Session a login | HttpOnly session, SameSite, lockout, password policy, 2FA/TOTP |
| Autorizácia | `RoleMiddleware`, `PermissionMiddleware`, `PermissionCatalog`, Path ACL |
| CSRF | synchronizer token, `X-CSRF-TOKEN`, `hash_equals`, úzko definované výnimky |
| Tajomstvá na disku | `EncryptionService`, 32-byte `APP_KEY`, libsodium alebo AES-256-GCM |
| Verejný storage | allow-list iba pre mediálne prefixy; data/logs/backups/dev/cache sú zakázané |
| Outbound/SSRF | `OutboundUrlGuard`, HTTPS v produkcii, DNS/IP kontrola, blok private/reserved rozsahov |
| WAF a abuse | scenárový WAF, bounded JSON body scan, rate limity a samostatné login/OTP limity |
| Rozšírenia | Zip-Slip ochrana, manifest/policy scan, kontrolovaný extension adresár, write-time `validateUntrusted` |
| Logy a exporty | `LogSanitizer`, CSV injection ochrana, security/audit rozlíšenie |
| CI výstup | sanitizovaný PHPUnit výstup a fail-closed redaction gate podľa ISS-120 |

### CI log hygiene — ISS-120

GitHub Actions nesmie publikovať raw backend test output. Implementačný kontrakt je:

```text
run-backend-tests-ci.sh
→ raw output iba v runner temp
→ sanitize-ci-log.py
→ verify-ci-log-redaction.sh
→ až potom GitHub console/artifact
```

Lokálny kompletný log zostáva mimo repozitára. Verejná šablóna postupu je [`../../LOCAL_TEST_LOGS.md.example`](../../../LOCAL_TEST_LOGS.md.example); raw log, lokálny checklist ani sanitizovaný pracovný súbor sa necommitujú.

### Dependency disclosure

React Router advisories publikované po `v2.1.0-beta.2` boli riešené v `v2.1.0-beta.3`. Auditný exit kód sa nesmie interpretovať bez kontroly severity threshold a úplného výstupu; nález pod nakonfigurovaným prahom stále môže vyžadovať `PASS_WITH_REVIEW` alebo `INVESTIGATION_REQUIRED`.

> Tento dokument opisuje bezpečnostné invarianty release rodiny **`v2.1.0-beta.*`** a cieľového Hybrid Headless Content Engineu. Konkrétna implementácia sa vždy overuje voči kódu a testom daného tagu. Verejný reporting zraniteľností upravuje koreňový [SECURITY.md](../../../SECURITY.md).

## 1. Bezpečnostné zásady

1. **Flat-file SSOT neznamená dôverovaný filesystem.** Každá cesta, názov súboru, archív a metadata sa validujú.
2. **Backend rozhoduje.** Frontend guard, skryté tlačidlo ani route redirect nie sú autorizácia.
3. **Fail closed na bezpečnostnej hranici.** Neznámy permission, neplatná revision, neoverený provider alebo nečitateľný manifest sa neinterpretuje optimisticky.
4. **Minimálne oprávnenia.** Používateľ, worker, API key, plugin a AI tool dostávajú iba potrebný scope.
5. **Autoritatívne a odvodené vrstvy sa nemiešajú.** Cache, index, Git remote ani AI výstup nesmú potichu prepísať SSOT.
6. **Secrets nie sú obsah.** Neukladajú sa do URL, logov, repozitára, frontend bundle ani promptu bez explicitnej potreby.
7. **Security gate je viacvrstvový.** Static scan, runtime kontrola, konfigurácia, audit a prevádzkové monitorovanie sa dopĺňajú.
8. **Recovery je bezpečnostná funkcia.** Backup bez restore testu a rollback bez zachovania kľúčov nie sú spoľahlivá ochrana.

## 2. Rozsah a predpoklady

Aktuálny základ predpokladá jednu CMS inštanciu na deployment. Plnohodnotná multi-tenant izolácia nie je implicitná vlastnosť Public Beta. Ak jeden proces obsluhuje viac zákazníkov alebo nedôveryhodných tímov, treba osobitný tenant model, namespacing, keys, quotas, audit a isolation testy.

Dokument pokrýva:

- verejný web a public API,
- staff/admin SPA,
- flat-file content a settings,
- uploady a media delivery,
- pluginy, témy, Code Editor a Developer Mode,
- externých OAuth/SMTP/ntfy/S3/Git/prekladových/AI providerov,
- scheduler, workery a asynchrónne joby,
- build, CI, release a deploy supply chain.

## 3. Chránené aktíva

| Aktívum | Riziko pri kompromitácii | Hlavné kontroly |
|---|---|---|
| používateľské účty a session | prevzatie účtu, eskalácia | heslá, 2FA, session policy, rate limit, audit |
| `APP_KEY` a encrypted secrets | dešifrovanie provider credentials | file permissions, secret management, backup separation |
| content SSOT | defacement, strata, supply-chain publish | RBAC/ACL, revision, atomic write, versions, backup |
| médiá | malware, XSS, únik private assetu | upload policy, content disposition, ACL, storage driver |
| settings | SSRF, mail abuse, auth bypass | schema, secret encryption, permission, capability probe |
| extension kód | RCE, data exfiltration, persistence | staged import, CodePolicy, allow-list, isolation limits |
| audit a logy | zahladenie stopy, log injection | append policy, sanitization, access control, export integrity |
| Git/release artefakty | malicious update | immutable refs, checksum, review, CI, optional signing/SBOM |
| queue/jobs | confused deputy, duplicate action | actor identity, scope, idempotency, retry/dead-letter audit |
| AI/preklad kontext | prompt injection, data leak, unauthorized Apply | minimal context, tool schema, authorization, human review |

## 4. Aktéri a hrozby

| Aktér | Typický cieľ | Povinná obrana |
|---|---|---|
| anonymný klient | abuse formulárov, scanning, XSS payload | rate limit, WAF, validácia, output encoding |
| registrovaný USER | získať editor/admin schopnosť | RBAC, object/path authorization |
| EDITOR | prístup mimo povoleného stromu | permission + Path ACL + audit |
| ADMIN | neúmyselná riziková konfigurácia | schema, SSRF guard, capability test, warnings |
| SUPER_ADMIN | legitímna silná mutácia alebo kompromitovaný účet | 2FA, re-auth, audit, recovery, minimálny počet účtov |
| škodlivý plugin/archív | RCE, persistence, exfiltrácia | import quarantine, scanner, policy, runtime boundary |
| kompromitovaný provider | škodlivá odpoveď/redirect | timeout, TLS, response limits, schema, redirect revalidation |
| sieťový útočník | hijack alebo downgrade | HTTPS, Secure cookies, proxy trust, HSTS rollout |
| file reader na hoste | krádež content/secrets | Unix permissions, secrets encryption, host hardening |
| kompromitovaný CI účet | podvrhnutý release | branch protection, least privilege, immutable artifact evidence |
| prompt injection v obsahu | prinútiť AI k tool akcii | content/instruction separation, tool allow-list, Apply auth |

## 5. Trust boundaries a dátové toky

Zjednodušený mutačný tok:

```text
client
→ reverse proxy/TLS
→ security headers + request limits
→ WAF/rate limit
→ session/API authentication
→ CSRF (session mutácie)
→ RBAC/permission/Path ACL/2FA
→ payload + path + revision validation
→ atomic SSOT write
→ version/audit/event
→ index/cache invalidation
→ voliteľný job (Git, preklad, AI, provider)
```

Každá šípka je hranica. Job spustený po úspešnom save nesmie spätne zmeniť fakt, že local write uspel; musí mať vlastný stav a retry model.

## 6. HTTP a middleware pipeline

Slim middleware poradie závisí od LIFO vykonávania a musí byť kryté integračným testom. Dokumentačný logický poriadok:

1. trusted proxy a canonical request metadata,
2. request ID a bezpečné log context,
3. security headers/CORS,
4. body/size/content-type limity,
5. firewall/WAF,
6. global a route-specific rate limit,
7. maintenance policy,
8. session alebo API authentication,
9. CSRF pre session mutácie,
10. role/permission/Path ACL/2FA,
11. controller/application service,
12. error normalization a audit.

Dôležité pravidlá:

- WAF môže request zastaviť pred JSON responderom; klient musí zvládnuť aj textovú/prázdnu `403` odpoveď.
- CORS nie je autorizácia. `Access-Control-Allow-Origin` nechráni endpoint pred server-to-server klientom.
- `APP_ENV=testing` výnimky nesmú byť aktivovateľné v produkcii iba request headerom.
- Proxy forwarded headers sa dôverujú iba zo zoznamu `TRUSTED_PROXIES`.
- Body scan má explicitné limity a bypass iba pre presne definované multipart/code-editor flows, nie wildcard podľa názvu URL.

## 7. Autentifikácia a session

### 7.1 Heslá a login

- používaj moderný `password_hash`/`password_verify` podľa podporovaného PHP,
- pri úspešnom login regeneruj session ID,
- používaj generickú chybovú odpoveď bez account enumeration,
- rate limit kombinuj podľa IP a identity s bezpečným fallbackom,
- lockout nesmie umožniť lacný trvalý DoS cudzieho účtu,
- citlivá zmena môže vyžadovať opätovné potvrdenie hesla alebo 2FA.

### 7.2 Session cookie

Produkčný profil má nastaviť:

- `HttpOnly`,
- `Secure` pri HTTPS,
- `SameSite=Lax` alebo prísnejšiu hodnotu podľa SSO topológie,
- obmedzenú lifetime a idle timeout,
- server-side invalidáciu pri logout/password reset/role downgrade,
- primeraný cookie path/domain.

Session ID, cookie ani CSRF token sa nezapisujú do bežného logu.

### 7.3 2FA a OTP

- secret sa šifruje at rest,
- recovery workflow je auditovaný,
- OTP má replay a rate-limit ochranu,
- debug OTP sa v produkcii nevydáva,
- SUPER_ADMIN/staff policy sa uplatňuje konzistentne na UI aj API,
- zníženie role alebo deaktivácia účtu invaliduje relevantné session.

### 7.4 SSO

OAuth/OIDC integrácia musí overiť:

- presný redirect URI kontrakt,
- náhodný a session-bound `state` cez timing-safe porovnanie,
- issuer/audience/nonce podľa použitého protokolu,
- TLS, timeout a response size,
- JIT rolu najviac podľa konfigurácie; nikdy implicitný SUPER_ADMIN,
- mapping identity bez kolízie e-mailu alebo provider subjectu.

## 8. API autentifikácia It.74

API keys a krátko žijúce JWT sú **implementované** ako aditívna headless vrstva; nenahrádzajú admin session model.

| Plocha | Auth | Poznámka |
|--------|------|----------|
| Admin SPA | Session + CSRF + RBAC + 2FA | bez zmeny |
| `/api/headless/*` | Bearer API key alebo krátke JWT | CSRF exempt; allow-list cez `ApiScopePolicy` |
| `/api/admin/platform/api-keys` | Session + `api-keys:manage` | create/list/revoke/rotate/audit; copy-once secret |

Povinné vlastnosti (vynútené):

- key secret sa zobrazí iba pri vytvorení/rotácii a ukladá sa ako HMAC verifier (`API_KEY_PEPPER`),
- metadata v `data/api-keys.json`,
- rotácia atomicky zruší starý kľúč; audit v `SecurityAuditStore`,
- žiadny key v query stringu,
- JWT: samostatný `API_JWT_KEY`, len HS256, max TTL 900s, povinné claims, voliteľný `jti` deny-list,
- neplatný managed Bearer na ľubovoľnej trase → `401` bez session fallbacku,
- log/audit nikdy neobsahuje secret.

CSRF sa nevzťahuje na `/api/headless` bearer mutácie. Dlhodobé tokeny neukladaj do browser localStorage.

## 9. Autorizácia, RBAC a Path ACL

Autorizácia sa vykonáva pri každej chránenej operácii a nad konkrétnym objektom/cestou.

Poradie:

```text
authenticated actor
→ account active/session valid
→ role/permission
→ Path ACL alebo object policy
→ 2FA/re-auth podmienka
→ revision/lock
→ mutácia
```

- SUPER_ADMIN bypass musí byť explicitný a auditovaný.
- Používateľ nesmie prideliť vyššiu rolu, než môže spravovať.
- Batch operácia validuje každú položku alebo transakčne zlyhá podľa kontraktu.
- Search/list endpoint nesmie vracať objekty, ktoré detail endpoint následne skryje.
- Signed media URL nesmie obísť Path ACL.
- Worker/job dedí minimálny snapshot identity iniciátora alebo technický scope; nie implicitný root.

Používateľská príručka: [ACCESS_CONTROL.md](../user/ACCESS_CONTROL.md).

## 10. CSRF a browser hranica

Session mutácie používajú synchronizer token:

1. server vytvorí token viazaný na session,
2. SPA ho posiela v hlavičke,
3. middleware používa timing-safe porovnanie,
4. po invalidácii session sa token zneplatní,
5. klient môže vykonať najviac kontrolovaný refresh/retry.

Exempt routes musia byť presný allow-list. Verejný formulár bez session CSRF potrebuje alternatívne kontroly: origin/content-type policy, rate limit, anti-automation, input validation a abuse monitoring.

`SameSite` je doplnok, nie jediná CSRF ochrana.

## 11. Validácia, encoding a bezpečný rendering

- vstup sa validuje podľa typu, rozsahu, enumu, formátu a doménového pravidla,
- canonicalizácia cesty prebehne pred allow/deny rozhodnutím,
- output sa encoduje podľa HTML/attribute/URL/JSON kontextu,
- Markdown/HTML preview používa allow-list sanitizer,
- URL schémy povoľ explicitne; `javascript:` a podobné varianty odmietni,
- SVG/HTML/XML upload sa doručuje podľa bezpečnej policy, často attachment + sandbox CSP,
- error odpoveď neobsahuje internú cestu, stack trace alebo secret.

WAF pattern nie je náhrada validácie a output encodingu.

## 12. Flat-file storage a verejné doručenie

Autoritatívne dáta majú zostať mimo web rootu. Verejný storage controller povoľuje iba explicitné media prefixy a kontroluje:

- normalizovanú relatívnu cestu,
- prefix/registry záznam,
- existenciu a typ súboru,
- oprávnenie/private flag,
- bezpečný `Content-Type`, `Content-Disposition` a CSP,
- range/cache správanie podľa media kontraktu.

Zakázané sú priame public cesty k users, settings, logs, backups, cache, dev tokenom a interným metadátam.

Zápis používa temp súbor, validáciu a atomický rename. Lock/revision chráni logickú konzistenciu; filesystem permission chráni host vrstvu.

Detail: [STORAGE.md](../architecture/STORAGE.md).

## 13. Secrets a encryption at rest

`APP_KEY` musí mať požadovanú entropiu a dĺžku. Slúži pre application-level šifrovanie citlivých polí, napríklad 2FA secret a provider credentials.

Pravidlá:

- `.env` nie je v repozitári ani release archíve,
- secret sa neposiela späť do UI po uložení,
- prázdny masked field neprepíše existujúci secret,
- key rotation má verzovaný formát ciphertextu a migračný plán,
- backup dát bez príslušného kľúča nemusí byť obnoviteľný,
- backup dát spolu s kľúčom znižuje ochranu; kľúč drž oddelene,
- log, audit, exception context a telemetry používajú redaction.

Šifrovanie at rest nechráni pred kompromitovaným bežiacim procesom, ktorý má kľúč. Preto sa dopĺňa host hardeningom a minimálnymi oprávneniami.

## 14. Outbound komunikácia a SSRF

Každá admin-konfigurovateľná URL pre OAuth, webhook, ntfy, S3-compatible endpoint, Git, preklad alebo AI provider prechádza spoločným outbound guardom.

Povinné kontroly:

- povolené schémy, v produkcii typicky HTTPS,
- parse a canonical host,
- DNS resolution všetkých výsledkov,
- blok private, loopback, link-local, multicast a reserved rozsahov,
- revalidácia každého redirectu,
- timeout, connect timeout, max redirects a response size,
- zákaz userinfo v URL,
- bezpečná proxy policy,
- audit provider testu bez secretu.

DNS sa môže zmeniť medzi validáciou a pripojením. Preferuj HTTP klient/network vrstvu, ktorá vie spojiť validáciu s reálnym cieľovým IP alebo revalidovať po connect/redirect. Jednorazový regex na URL nie je SSRF obrana.

## 15. Uploady, archívy a médiá

Upload pipeline:

```text
request/body limit
→ extension + MIME + content sniff
→ temp quarantine
→ decoder/scanner/policy
→ bezpečný názov a metadata
→ atomický move alebo storage-driver commit
→ registry/audit
```

Pre ZIP/import:

- limit počtu entries, komprimovanej aj rozbalenej veľkosti a pomeru,
- odmietnutie absolute path, `..`, NUL a nekanonického názvu,
- odmietnutie symlink/hardlink entries podľa policy,
- žiadne extrahovanie priamo do aktívneho runtime adresára,
- manifest/schema a code policy pred registráciou,
- importovaný extension zostáva disabled,
- rollback odstráni iba vlastné staged artefakty.

Antivírus je doplnok. Negatívny ClamAV výsledok neznamená, že SVG alebo PHP kód je bezpečný na inline vykonanie.

## 16. Pluginy, témy a Code Editor

`CodePolicyEngine::validateUntrusted` je write/import gate pre nedôveryhodné extension/theme/layout stromy. Zakazuje alebo obmedzuje vysoko rizikové konštrukcie podľa [EXTENSION_CODE_POLICY.md](EXTENSION_CODE_POLICY.md).

Dôležité hranice:

- scanner nie je plnohodnotný PHP sandbox,
- `include`, `require`, dynamické callable a autoload môžu vytvoriť execution surface,
- backend plugin beží v procese aplikácie, ak nie je oddelený process/container boundary,
- filesystem jail musí používať canonical path a kontrolovať symlink race,
- save v Code Editore neznamená registráciu, aktiváciu, frontend build, Git push ani deploy,
- Vite `import.meta.glob` je build-time mechanizmus,
- Developer Mode má krátky unlock, 2FA/dev secret, audit a produkčný fail-closed profil.

Pre nedôveryhodný komunitný ekosystém je cieľom silnejšia izolácia procesu/kontajnera alebo deklaratívny extension model s obmedzenými capability API.

## 17. Firewall, rate limiting a abuse protection

Aplikačný WAF je doplnková vrstva. Nenahrádza reverse proxy limity, host firewall, bezpečné routy ani validáciu.

- pravidlá majú scenáre, severity a testy proti false positives,
- incident log sanitizuje vstupy,
- whitelist je úzky, auditovaný a časovo obmedziteľný,
- rate limiter používa bezpečnú IP deriváciu cez trusted proxy,
- login/OTP/public forms majú samostatné budgety,
- multipart a code-editor body scan výnimky sú minimálne,
- memory/file limiter na multi-node deployi nemusí byť globálne konzistentný; distributed backend je capability, nie automatický predpoklad.

## 18. Logging, audit a monitoring

Rozlišuj:

| Tok | Účel |
|---|---|
| access/request log | prevádzková stopa requestu |
| application log | chyba a diagnostika |
| security event | WAF, lockout, policy deny, suspicious provider |
| domain audit | kto zmenil content, settings, usera alebo permission |

Povinné vlastnosti:

- UTC timestamp a request/job correlation ID,
- actor ID/role a target, nie credential,
- CR/LF/ANSI sanitization,
- bezpečný CSV export proti formula injection,
- redaction secrets a citlivých payloadov,
- oprávnenia a retencia,
- atomická rotácia bez poškodenia aktívneho writera,
- alert na opakované authz deny, provider failure, queue backlog a integrity problém.

Audit má byť ťažšie meniteľný než bežný aplikačný log. Public Beta nemusí mať kryptografický append-only ledger, ale dokumentácia nesmie označiť editovateľný JSON za nezvratný dôkaz.

## 19. Dependency a release supply chain

Release gate zahŕňa:

- Composer/npm lockfile install,
- SCA audit s verzovanou severity policy,
- secret scan,
- review GitHub Actions permissions,
- pinning alebo kontrolu third-party actions,
- immutable release commit/tag,
- checksum artefaktu,
- kontrolu obsahu archívu,
- oddelenie build a deploy credentials,
- ideálne SBOM a provenance/signing.

Advisory sa nesmie stratiť iba preto, že CI blokuje až `high`, kým nález je `moderate`. Ak je dočasne akceptovaný, patrí do `ISSUES.md` s ownerom, dôvodom a termínom revízie.

CI pull request z forku nesmie dostať produkčné secrets. Deploy environment vyžaduje explicitnú ochranu a minimálne token scopes.

## 20. Scheduler, workery a queue

Asynchrónny job je samostatná security principal hranica.

Job payload má obsahovať iba potrebné identifikátory, nie plaintext secrets alebo celý session objekt. Pri vykonaní:

- načítaj aktuálny target/revision,
- over job type a schema,
- uplatni service capability a podľa kontraktu actor permission,
- používaj idempotency key,
- obmedz retry/backoff,
- zlyhanie pošli do viditeľného failure/dead-letter stavu,
- audituj enqueue aj finálny výsledok,
- nepovoľ arbitrary class/method deserializáciu.

Queue worker nemá byť SUPER_ADMIN iba preto, že beží cez CLI.

## 21. Git publish

Git je distribučná/publish vrstva, nie náhrada local SSOT.

- repo URL a credentials sú secrets/outbound surface,
- branch/ref má allow-list a bezpečný názov,
- commit obsah vzniká z validovaného snapshotu/revision,
- shell command sa neskladá konkatenáciou user inputu,
- push failure nemení `stored` na false,
- retry nevytvára duplicitné commity,
- audit rozlišuje commit created a remote pushed,
- checkout/worktree je izolovaný od aktívneho content storage.

Webhook alebo remote sync späť do CMS potrebuje podpis, replay ochranu a konflikt policy.

## 22. Lokalizácia, preklad a AI

### 22.1 Preklad

- provider dostáva iba potrebný obsah a metadata,
- secrets/PII sa neposielajú bez oprávnenia a dokumentovanej policy,
- response má size/schema limity,
- výstup je návrh/draft,
- Diff je viazaný na source revision,
- Apply znovu overí actor permission a revision,
- publish je samostatná ľudská alebo explicitne autorizovaná operácia.

### 22.2 AI agent

Systémový prompt nie je bezpečnostná hranica. Ochranu tvoria:

- oddelenie system instructions od nedôveryhodného contentu,
- minimálny autorizovaný context retrieval,
- allow-listované tools s JSON schema,
- žiadny shell ani generic filesystem tool,
- per-tool permission check,
- preview/diff pred mutáciou,
- nová autorizácia pri Apply,
- limity tokenov, času, nákladov a outbound dát,
- audit requestu, toolov a výsledku bez citlivého prompt dumpu.

Autonómny publish a „AI superuser“ sú mimo bezpečného základného rozsahu.

## 23. Backup, restore a incident recovery

Backup musí pokrývať autoritatívny content, users, settings metadata, media metadata/objekty podľa drivera a potrebné verzie. Kľúče a credentials majú vlastnú bezpečnú recovery cestu.

Pravidelne testuj:

- restore do prázdneho oddeleného koreňa,
- validáciu archívu proti traversal/symlink,
- kompatibilitu schema/migration,
- index/cache rebuild,
- zachovanie alebo obnovu `APP_KEY`,
- rotáciu kompromitovaných secrets,
- návrat služby bez otvorenia mutácií pred integrity checkom.

Incident response minimálne:

1. obmedziť mutácie/maintenance,
2. zachovať logy a relevantné artefakty,
3. identifikovať commit, release a rozsah,
4. rotovať zasiahnuté credentials,
5. opraviť a pridať regression test,
6. obnoviť z overeného zdroja,
7. zdokumentovať issue/advisory podľa disclosure policy.

## 24. Security gates a release blockers

Release blokuje najmä:

- auth alebo authz bypass,
- CSRF bypass na session mutácii,
- traversal/Zip-Slip/symlink únik z jailu,
- možnosť stiahnuť users/settings/logs/backups cez web,
- korupcia alebo tichý overwrite content SSOT,
- únik secretu do logu/bundle/artefaktu,
- neopravená exploatovateľná dependency podľa schválenej policy,
- extension import vedúci k nekontrolovanému kódu,
- neautorizovaný AI/tool Apply alebo publish,
- nereprodukovateľný upgrade/rollback pri release-impacting zmene.

Security testy a quality gates: [TESTING.md](TESTING.md).

## 25. Reporting a verejný incident log

Citlivú zraniteľnosť neposielaj najprv do verejného issue. Použi postup v koreňovom [SECURITY.md](../../../SECURITY.md).

`docs/ISSUES.md` je verejný technický záznam opravených alebo bezpečne zverejnených problémov. Pri jeho dvojjazyčnom spracovaní v Iterácii 13 bude:

- číslo chyby v prehľade klikateľný odkaz,
- detail mať stabilný explicitný anchor, napríklad `<a id="iss-078"></a>`,
- detail obsahovať symptóm, príčinu, dopad, riešenie a overenie,
- podľa dostupnosti odkazovať na commit, changelog a release.

Súkromný `SECURITY_ISSUES.md` môže obsahovať detailný interný incident workflow, ale nesmie byť commitnutý ani obsahovať plaintext secrets.

## 26. Prevádzkový checklist

- [ ] HTTPS a správne trusted proxy nastavenie,
- [ ] `APP_ENV=production`, debug vypnutý,
- [ ] public docroot iba `backend/public/`,
- [ ] staff 2FA podľa policy,
- [ ] reálny `APP_KEY` a chránený recovery postup,
- [ ] CSRF/RBAC/Path ACL regression testy zelené,
- [ ] WAF/rate limit a log sanitization overené,
- [ ] storage, logs, backups a dev dáta neprístupné cez web,
- [ ] Composer/npm advisory review zdokumentovaný,
- [ ] backup restore test vykonaný,
- [ ] worker/cron beží s minimálnymi oprávneniami,
- [ ] outbound providery prešli SSRF/capability testom,
- [ ] release artefakt má checksum a neobsahuje secrets,
- [ ] incident/disclosure kontakt je funkčný.

## 27. Súvisiace dokumenty

- [Koreňová security policy](../../../SECURITY.md)
- [Security review](../SECURITY_REVIEW.md)
- [Testovanie a quality gates](TESTING.md)
- [Core hardening](../architecture/CORE_HARDENING.md)
- [Storage](../architecture/STORAGE.md)
- [API kontrakt](../architecture/API_CONTRACT.md)
- [Access control](../user/ACCESS_CONTROL.md)
- [Firewall](../user/FIREWALL.md)
- [Logging](../user/LOGGING.md)
- [Extension Code Policy](EXTENSION_CODE_POLICY.md)
- [Beta infra](BETA_INFRA.md)
- [Issues](../ISSUES.md)
