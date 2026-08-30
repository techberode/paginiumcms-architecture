---
title: Bezpečnostný a release readiness checklist
description: Praktická kontrola implementácie, testov, CI, deployu, backupu a podmienených Hybrid Engine schopností
icon: material/check-decagram
---

# Bezpečnostný a release readiness checklist

> Tento checklist je living gate pre release rodinu **`v2.1.0-beta.*`**. Nie je náhradou za [FEATURE_OVERVIEW.md](FEATURE_OVERVIEW.md), [TESTING.md](developer/TESTING.md) ani [RELEASE.md](developer/RELEASE.md). Položka je hotová iba s dôkazom pre konkrétny SHA.

## 1. Legenda

| Symbol/stav | Význam |
|---|---|
| ✅ `PASS` | overené automaticky aj podľa potreby manuálne |
| 🟡 `PASS_WITH_REVIEW` | neblokujúca anomália má disposition, ownera a termín |
| 🔍 `INVESTIGATION_REQUIRED` | nejednoznačný výstup, nový warning alebo nevysvetlený skip |
| ❌ `FAILED` | hard gate alebo bezpečnostný/recovery blocker |
| ➖ `NOT_APPLICABLE` | schopnosť v tage nie je implementovaná alebo sa profilu netýka |

Do checklistu sa nevpisuje „zelené podľa pocitu“. Každá kritická položka má príkaz, test, log, screenshot, checksum alebo review záznam.

## 2. Identita kandidáta

- [ ] Repository a remote sú správne.
- [ ] Pracovný strom je čistý alebo sú všetky lokálne zmeny zdokumentované.
- [ ] Je známy commit SHA.
- [ ] Tag je annotated a ukazuje na správny SHA.
- [ ] Lockfiles patria rovnakému commitu.
- [ ] Release artefakt má SHA-256.
- [ ] GitHub CI run patrí rovnakému SHA.
- [ ] Verzia v UI/API/docs/release notes je konzistentná.

## 3. 21-krokový gate a manuálny review

- [ ] PHPUnit: 0 failed, 0 errors.
- [ ] Každý skipped test má dôvod a kategóriu.
- [ ] PHPStan Level 8: 0 errors.
- [ ] Composer Audit: advisories vyhodnotené.
- [ ] Vitest: všetky súbory/testy passed.
- [ ] Frontend security pack passed.
- [ ] TypeScript: 0 errors.
- [ ] ESLint: warning count neprekročil limit a trend je skontrolovaný.
- [ ] MSW: žiadny neošetrený request.
- [ ] Production build a API URL verification passed.
- [ ] NPM Audit: počty podľa severity + disposition.
- [ ] Content diagnose: 0 orphans, 0 unreadable alebo schválený recovery plán.
- [ ] Security packs 12–20 passed.
- [ ] Static grep má jednoznačný exit a allowlist report.
- [ ] Kompletný lokálny log je mimo repozitára.
- [ ] GitHub CI log je sanitizovaný.
- [ ] Manuálne boli prečítané warningy, stack traces, network errors a timing anomálie.

## 4. CI log a secrets

- [ ] Testy nevypisujú heslo, TOTP seed, QR, provisioning URI ani OTP.
- [ ] Redaktor pokrýva vnorený JSON, URL, Base64 a auth headers.
- [ ] Raw CI output ide iba do `$RUNNER_TEMP`.
- [ ] Nepoužíva sa `tee` na raw výstup.
- [ ] Publikovaný je iba sanitizovaný log.
- [ ] Fail-closed grep/scanner nenájde secret patterns.
- [ ] Pôvodný test exit code zostane zachovaný.
- [ ] Raw log sa neuploaduje ako artifact.
- [ ] `set -x`, `ACTIONS_STEP_DEBUG` a runner debug sú pri secrets vypnuté.

## 5. Autentifikácia a session

- [ ] Bootstrap credentials sú unikátne a po prvom prihlásení zmenené.
- [ ] Argon2id a password policy sú aktívne.
- [ ] Login regeneruje session ID.
- [ ] Session cookie má bezpečné flags pre daný profil.
- [ ] Login/reset odpovede neumožňujú enumeráciu.
- [ ] Login lockout/rate limit funguje.
- [ ] Reset token je hashovaný, expirovaný a single-use.
- [ ] 2FA seed je šifrovaný at rest.
- [ ] OTP rate limit a resend limit fungujú.
- [ ] Produkčný response/log neobsahuje debug OTP.

## 6. Autorizácia, RBAC a Path ACL

- [ ] Každá mutujúca route má authn + authz alebo explicitnú anonymnú výnimku.
- [ ] USER nemôže zapisovať content/media.
- [ ] EDITOR má iba deklarované permissions.
- [ ] ADMIN nevykoná SUPER_ADMIN operáciu.
- [ ] Draft, lock, bulk, trash, restore a import majú rovnakú policy ako hlavný CRUD.
- [ ] Path ACL používa kanonickú cestu.
- [ ] Worker/job/API key má vlastnú identitu a minimálne scopes.
- [ ] Frontend guard nie je jediná ochrana.

## 7. CSRF, CORS, WAF a proxy

- [ ] Mutujúci browser request bez CSRF → `403`.
- [ ] Exempt prefix má path boundary.
- [ ] Anonymous POST má rate limit a abuse policy.
- [ ] Production CORS povoľuje iba explicitné origins.
- [ ] `TRUSTED_PROXIES` obsahuje iba reálne proxy hops.
- [ ] Spoofed forwarding headers sa ignorujú.
- [ ] WAF body scan má size limit.
- [ ] Multipart a Code Editor majú explicitnú scan policy.
- [ ] WAF block môže byť non-JSON a frontend to zvládne.

## 8. Storage, obsah a médiá

- [ ] Web docroot neobsahuje interný storage.
- [ ] Verejná storage route povoľuje iba media podstrom.
- [ ] `data/`, `logs/`, `backups/` a indexy vracajú `404`.
- [ ] Traversal, encoded traversal a absolútna cesta sú odmietnuté.
- [ ] Symlink/hardlink archív je odmietnutý.
- [ ] Zápis používa temp + atomic rename.
- [ ] OCC/revision konflikt vracia `409` alebo merge flow.
- [ ] Index je odvodený a rebuildnuteľný zo SSOT.
- [ ] SVG/HTML/XML response má bezpečné headers.
- [ ] Upload limituje MIME, veľkosť, počet a compression ratio.
- [ ] Content diagnose je zdravý.

## 9. Extensions, témy a Developer Mode

- [ ] Manifest má schema version a kompatibilitu.
- [ ] ZIP sa kontroluje pred extrakciou.
- [ ] Import ide do quarantine/staging.
- [ ] Code Policy blokuje zakázané konštrukcie.
- [ ] Code Editor používa allowed roots a containment.
- [ ] Developer unlock má TOTP/token, TTL a fail-closed secret.
- [ ] Import neznamená automatickú aktiváciu.
- [ ] Aktivácia/deaktivácia/rollback sú auditované.
- [ ] Frontend extension dokumentuje build/redeploy požiadavku.
- [ ] Nedôveryhodný kód nie je označený za sandboxovaný.

## 10. Outbound a integrácie

- [ ] Admin-configured URL prejde OutboundUrlGuard.
- [ ] Private/loopback/link-local/metadata IP sú blokované podľa profilu.
- [ ] Redirect sa revaliduje.
- [ ] Timeout, response-size a content-type limit sú nastavené.
- [ ] OAuth state/provider/redirect sú viazané a timing-safe.
- [ ] SMTP/ntfy/webhook/Git tokeny sú redigované.
- [ ] Provider secret nie je v URL ani frontend bundle.
- [ ] Proxy a DNS správanie sú otestované.

## 11. Logging, audit a monitoring

- [ ] CR/LF/ANSI log injection je sanitizovaná.
- [ ] CSV export chráni formula injection.
- [ ] Secrets, cookies a auth headers sú redigované.
- [ ] Request/job correlation ID je dostupné.
- [ ] `401/404` šum nie je zamieňaný za server error.
- [ ] Security udalosti majú správnu severity.
- [ ] Log delete/archive vyžaduje oprávnenie a audit.
- [ ] Monitoring sleduje 5xx, auth, WAF, joby, disk, backup a upstream.
- [ ] Čas/NTP a UTC politika sú konzistentné.

## 12. Backup, restore a recovery

- [ ] Backup je mimo web rootu.
- [ ] Backup je mimo primárneho failure domain alebo má off-host kópiu.
- [ ] Obsahuje potrebný SSOT a inventár.
- [ ] `APP_KEY` recovery je zabezpečené oddelene.
- [ ] Restore bol vykonaný na izolovanom profile.
- [ ] Po restore prejde login, 2FA, public content a index rebuild.
- [ ] RTO/RPO sú zaznamenané.
- [ ] Rollback kódu neprepíše novší content.
- [ ] Recovery postup rieši disk-full, corrupt file a chýbajúci key.
- [ ] Posledný restore drill nie je starší než interná policy.

## 13. Deploy a nginx

- [ ] `docker compose config --quiet` passed.
- [ ] `nginx -t` passed na aktívnej konfigurácii.
- [ ] Static aj API majú security headers.
- [ ] `/.well-known/security.txt` je dostupný ako text.
- [ ] `expose_php=Off`.
- [ ] Produkcia a demo majú oddelené storage, secrets, porty a project name.
- [ ] Docker služby majú `restart: unless-stopped` podľa profilu.
- [ ] Cron/worker používa správnu identitu a `flock`.
- [ ] Pred deployom vznikol backup.
- [ ] Health, login, authz, public content a dotknutá funkcia prešli smoke.
- [ ] Rollback/roll-forward príkazy sú pripravené.

## 14. Public a anonymous endpoints

- [ ] Route inventory je vygenerovaný z konkrétneho tagu.
- [ ] Public settings neobsahujú secrets.
- [ ] Demo mode failuje closed v production.
- [ ] Login/register/reset/contact/comments/newsletter majú abuse controls.
- [ ] Public content vracia iba publikované položky.
- [ ] Debug endpoint je no-op alebo disabled mimo development.
- [ ] Storage media delivery rešpektuje ACL a MIME policy.

## 15. Hybrid Engine — podmienené gates

Pri neimplementovanej schopnosti označ `NOT_APPLICABLE`.

- [ ] Redis capability probe a bezpečný fallback.
- [ ] Cache/index nikdy nie sú SSOT.
- [ ] Git publish job je idempotentný a má scoped credential.
- [ ] S3 driver má scoped bucket policy a recovery reconciliation.
- [ ] API key je secret-at-create-only, revokovateľný a scoped.
- [ ] JWT je krátko žijúci a nie je náhradou admin session/CSRF.
- [ ] Preklad vytvorí draft/diff; Apply je autorizovaný.
- [ ] AI tools sú allow-listované a schémované.
- [ ] AI Apply znovu kontroluje user permission.
- [ ] AI nemá shell, generic filesystem ani autonomous publish.

## 16. Release rozhodnutie

- [ ] Všetky `FAILED` sú uzavreté.
- [ ] Security `INVESTIGATION_REQUIRED` má opravu alebo schválenú disposition.
- [ ] `PASS_WITH_REVIEW` má ownera a expiry.
- [ ] Release evidence bundle obsahuje gate summary, safe log, checksums a smoke.
- [ ] Changelog a release notes uvádzajú known issues bez zamlčania.
- [ ] Security fixy majú regresné testy.
- [ ] Incidenty sú pripravené na prelinkovanie do `ISSUES.md`.
- [ ] Release decision record je podpísaný/odsúhlasený maintainerom.

## 17. Súvisiace dokumenty

- [Bezpečnostná politika](../../SECURITY.md)
- [Audit report](../../AUDIT_REPORT.md)
- [Odporúčania](../../RECOMMENDATIONS.md)
- [Security Review Guide](SECURITY_REVIEW.md)
- [Testovanie](developer/TESTING.md)
- [Release lifecycle](developer/RELEASE.md)
- [Deploy](deploy/DEPLOY.md)
- [Register incidentov](ISSUES.md)

## 18. Origin manifest slices (2026-08-30)

Strojovo čitateľný SSOT: [`docs/manifest/project-catalog.json`](../manifest/project-catalog.json) + [`docs/manifest/implementation-checklist.json`](../manifest/implementation-checklist.json). Origin Panel spája tieto súbory s runtime probes a `AppVersion::current()` pre deploy badge.

### It.83 — Theme runtime a marketing (`since: 2.1.0-beta.59`)

- [x] `ThemeRuntimeService` activate/deactivate + nastavenie `themes.activeTheme`
- [x] Bundled témy `clean-journal`, `terminal-breach` + PublicShell wiring
- [x] Landing seed `paginium-cms-landing.sk.md`
- [x] Probes `it.83.theme_runtime`, `it.83.theme_packages` registrované
- [ ] Produkčný deploy na `2.1.0-beta.59+` (Origin ukáže **live**, keď verzia inštancie ≥ tag)

### It.86 — Admin UX polish (`targetVersion: 2.1.0-beta.60`)

- [x] Admin command palette session auth ([ISS-158](ISSUES.md#iss-158), [ISS-159](ISSUES.md#iss-159))
- [x] Nastavenie tlače `content.articlePrintEnabled` + tlačidlo na webe
- [x] Bulk counter „X z Y“ na stránkach/článkoch, správach, komentároch
- [x] `./scripts/iteration-gate.sh` zelený pred tagom
- [x] Tag `v2.1.0-beta.60` (2026-08-30)
- [ ] Produkčný deploy (Origin **live** keď inštancia ≥ tag)

### Origin automatizácia

- [x] `CatalogDeployStatusResolver` — `live` / `pending_deploy` / `partial_live` z `since` / `targetVersion`
- [x] `implementation-checklist.json` slices v Origin Paneli **Release slices**
- [x] `./scripts/validate-project-catalog.sh` validuje probeIds + checklist iteration refs

## 19. Plánovaná iterácia 87 (post-stable)

Špecifikácia: [`docs/en/ITERATION_87.md`](../en/ITERATION_87.md) · SK: [`docs/sk/ITERATION_87.md`](../sk/ITERATION_87.md)

- [x] Špecifikácia It.87 (Plánovač projektu + UX audit)
- [ ] MVP plánovača (`87e`–`87h`) v plnej verzii CMS
- [ ] UX audit (`87a`–`87d`) alebo explicitný defer v ISSUES
- [ ] Voliteľný Track C: allow-list JS v `themes/{id}/assets/` + SRI + CSP (`87k`–`87m`) — default vypnuté
