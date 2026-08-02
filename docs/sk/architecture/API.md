---
title: API referencia
description: Kanonický prehľad HTTP API PaginiumCMS
icon: material/api
---

# 🔌 API referencia PaginiumCMS

> **Stav dokumentu:** Public Beta · checkpoint `v2.1.0-beta.23` · 2. august 2026  
> **Backend:** PHP 8.4+ · Slim 4 · JSON REST API  
> **Kontrakt odpovedí:** [API_CONTRACT.md](./API_CONTRACT.md)

Tento dokument je **prehľad verejného HTTP povrchu**, nie automaticky generovaný OpenAPI súbor. Rozlišuje endpointy potvrdené v dodanom dokumentačnom balíku, prechodné legacy správanie a plánované schopnosti Hybrid Engineu. Pred vydaním musí byť inventár porovnaný s aktuálnym route registrom a kontraktnými testami; historický dokument alebo frontend klient sám osebe nie je dôkazom, že route stále existuje.

---

## 1. Pravidlá API

1. React administrácia a externí headless klienti používajú tie isté aplikačné služby a doménové pravidlá.
2. HTTP controller nesmie zapisovať priamo do súborov ani obchádzať RBAC, path ACL, revision alebo lock kontrolu.
3. Session admin flow zostáva cookie-based a pri mutáciách používa CSRF. API keys/JWT z It.74 sú plánovaná aditívna schopnosť, nie náhrada admin session.
4. Verejný klient nikdy automaticky nezíska drafty, privátne médiá, tajné settings alebo admin metadata.
5. Primárny save a voliteľný Git/preklad/AI follow-up majú oddelený stav. `stored` neznamená automaticky `pushed`.
6. API musí fungovať v Classic profile bez Redis, S3, Git remote, LLM alebo cloud prekladu.
7. Endpoint, payload alebo enum sa nepovažuje za stabilný iba preto, že je spomenutý v historickej iterácii.

### Stavové značky

| Značka | Význam |
|--------|--------|
| ✅ | potvrdené ako implementované v dokumentačnom checkpointe |
| 🟡 | implementované, ale s legacy/prechodným kontraktom alebo potrebou konsolidácie |
| ⏳ | plánované v It.68–77; klient sa na to zatiaľ nesmie spoliehať |
| 🧪 | diagnostické alebo environment-gated správanie |

---

## 2. Základ URL, formáty a hlavičky

Typický backend base URL je rovnaký origin ako administrácia, napríklad:

```text
https://cms.example.com/api
```

JSON requesty používajú:

```http
Content-Type: application/json
Accept: application/json
```

Multipart upload používa `multipart/form-data`. RSS, sitemap, `robots.txt`, binárne médiá a niektoré WAF bloky nie sú JSON endpointy.

Odporúčané request hlavičky podľa flow:

| Hlavička | Použitie |
|----------|----------|
| `X-CSRF-Token` | session-auth mutácie |
| `Authorization: Bearer …` | ⏳ API key/JWT flow podľa It.74; nie admin SPA localStorage |
| `If-None-Match` | ⏳ podmienené čítanie po It.69 |
| `If-Match` alebo `baseRevision` v payload | OCC write; presný wire kontrakt musí zostať jednotný |
| `Idempotency-Key` | ⏳ publish/job mutácie, ktoré môžu byť bezpečne opakované |
| `Accept-Language` alebo explicitný `locale` | ⏳ locale-aware content po It.73; fallback nesmie obísť ACL |

API používa UTF-8 a ISO 8601/RFC 3339 dátumy s časovou zónou. Klient nemá parsovať lokalizované dátumy ako strojový kontrakt.

---

## 3. Autentifikačná matica

| Klient | Mechanizmus | CSRF | Oprávnenia | Stav |
|--------|-------------|------|------------|------|
| anonymný browser/headless | bez identity | nie | iba public slice | ✅ |
| React admin SPA | secure PHP session cookie | áno pri mutácii | RBAC + path ACL + voliteľná 2FA/OTP | ✅ |
| serverová integrácia/CI | scope-limited API key | nie, ak nepoužíva cookie autoritu | route/method allow-list + scopes | ⏳ It.74 |
| krátka delegovaná úloha | krátko žijúce JWT | nie | issuer/audience/scopes/expiry | ⏳ It.74 |
| background job | service/actor identity | nie | minimálny explicitný scope | cieľový kontrakt |

Neplatný Bearer token nesmie potichu fallbacknúť na session alebo anonymný prístup. Browser admin token v `localStorage` je zakázaný smer.

---

## 4. Verejné a systémové endpointy

| Metóda | Endpoint | Účel | Stav |
|--------|----------|------|------|
| `GET` | `/api/health` | základný health check bez citlivých detailov | ✅ |
| `GET` | `/api/settings/public` | allow-listovaný verejný settings slice | ✅ |
| `GET` | `/api/validation/rules` | zdieľané validačné pravidlá pre UX; backend zostáva autorita | ✅ |
| `GET` | `/feed.xml` | RSS 2.0 publikovaných článkov | ✅ |
| `GET` | `/sitemap.xml` | sitemap publikovaných stránok a článkov | ✅ |
| `GET` | `/robots.txt` | crawler policy + sitemap odkaz podľa nastavení | ✅ |
| `GET` | `/storage/{path}` | verejne povolené médiá/statické súbory; path traversal musí byť blokovaný | ✅ |
| `POST` | `/api/debug/client-event` | klientská diagnostika; bezpečne vypnutá mimo povoleného režimu | 🧪 |

`/api/health` nemá anonymne vracať stack trace, filesystem cesty, credentials, interné hostnames ani kompletnú konfiguráciu providerov. Rozšírené health/APM dáta patria do chráneného admin API.

---

## 5. Verejný obsah a komunikácia

| Metóda | Endpoint | Účel | Stav |
|--------|----------|------|------|
| `GET` | `/api/pages` | zoznam stránok; anonymne iba published | ✅ |
| `GET` | `/api/pages/{slug}` | detail stránky | ✅ |
| `GET` | `/api/articles` | zoznam článkov s filtrami a stránkovaním | ✅ |
| `GET` | `/api/articles/{slug}` | detail článku | ✅ |
| `GET` | `/api/search` | public search alebo chránený admin palette search | ✅ |
| `GET` | `/api/seo/{type}/{slug}` | normalizované SEO metadata publikovaného obsahu | ✅ |
| `GET` | `/api/navigation` | verejný navigačný strom | ✅ |
| `GET` | `/api/comments` | verejne schválené komentáre podľa policy | ✅ |
| `POST` | `/api/comments` | odoslanie komentára s rate limitom/moderáciou | ✅ |
| `POST` | `/api/contact` | kontaktný formulár s anti-abuse ochranou | ✅ |
| `POST` | `/api/analytics/pageview` | privacy-aware pageview ingest, ak je analytics povolená | ✅ podľa modulu |
| `POST` | `/api/newsletter/subscribe` | newsletter opt-in podľa nasadenej funkcionality | ✅ podľa modulu |
| `GET` | `/api/gallery/public` | public gallery slice, ak je modul povolený | ✅ podľa modulu |

Presné query parametre, public pravidlá a write lifecycle sú v [CONTENT_API.md](./CONTENT_API.md).

---

## 6. Autentifikácia a účet

| Metóda | Endpoint | Účel | Stav |
|--------|----------|------|------|
| `POST` | `/api/auth/login` | login; môže pokračovať 2FA challenge | ✅ · legacy envelope |
| `POST` | `/api/auth/register` | registrácia podľa settings a OTP policy | ✅ · environment policy |
| `POST` | `/api/auth/logout` | ukončenie session | ✅ |
| `GET` | `/api/auth/me` | aktuálna session identity a klientom potrebné capability | ✅ |
| `GET` | `/api/auth/csrf-token` | CSRF synchronizer token | ✅ |
| `POST` | `/api/auth/change-password` | zmena hesla | ✅ |
| `POST` | `/api/auth/reset-password` | vytvorenie reset workflow bez user enumeration | ✅ |
| `POST` | `/api/auth/verify-reset-token` | dokončenie resetu | ✅ |
| `GET` | `/api/auth/sso/providers` | povolení SSO provideri bez secretov | ✅ podľa config |
| `GET` | `/api/auth/sso/{provider}/start` | OAuth/OIDC začiatok s validovaným state/redirect | ✅ podľa config |
| `GET` | `/api/auth/sso/{provider}/callback` | callback a vytvorenie session | ✅ podľa config |

Auth endpointy majú historicky plochý úspešný envelope. Nový kód nemá tento legacy tvar šíriť do ďalších domén; migračné pravidlá sú v [API_CONTRACT.md](./API_CONTRACT.md).

---

## 7. Authenticated content workflow

Nasledujúce rodiny používajú session + CSRF + permission/path ACL, neskôr prípadne explicitné It.74 write scopes:

| Rodina | Typické operácie | Ochrana |
|--------|------------------|---------|
| `/api/pages`, `/api/articles` | create, update, status/publish, soft delete, bulk operácie | `content:*`, revision, lock, schema |
| `/api/media/*` | upload, list, folder/metadata, delete, stock import | `media:*`, MIME/size/path policy |
| `/api/drafts/{type}/{slug}` | read/save/delete autosave draft | owner/editor policy + base revision |
| `/api/locks/*` | acquire/heartbeat/release | actor identity + expiry |
| `/api/workflows/otp/*` | verify/resend citlivej akcie | challenge owner, TTL, attempt limit |

Citlivá mutácia môže najprv vrátiť OTP challenge. Klient nesmie po challenge predstierať, že publish prebehol; až verify response potvrdí výsledok.

---

## 8. Admin API rodiny

Väčšina `/api/admin/*` endpointov vyžaduje minimálne ADMIN a podľa policy aj nedávnu 2FA. Presná permission musí byť route-level allow-list, nie iba kontrola prefixu.

| Oblasť | Prefix / príklad | Poznámka |
|--------|------------------|----------|
| settings | `/api/admin/settings` | schema-driven, secret hodnoty redacted/write-only |
| users a prístup | `/api/admin/users`, `/api/admin/security/*` | SUPER_ADMIN-only operácie musia byť explicitné |
| trash | `/api/admin/trash/*` | restore, purge, backup; permanent delete je oddelená akcia |
| backups | `/api/admin/backups/*` | create/list/verify/restore/import podľa policy |
| versions a conflicts | `/api/admin/versions/*`, `/api/admin/conflicts` | compare/restore/cleanup |
| audit a logy | `/api/admin/audit/*`, `/api/admin/logs*` | export rediguje tajomstvá a formula injection |
| dashboard/counts | `/api/admin/dashboard/overview`, `/api/admin/counts` | agregované UI dáta, nie security autorita |
| analytics | `/api/admin/analytics/*` | chránené metriky a retention |
| firewall | `/api/admin/firewall/*` | bans, whitelist, incidents, stats |
| content utility | `/api/admin/content/*` | napr. SEO audit/suggest; nesmie auto-publish bez policy |
| comments/messages | `/api/admin/comments/*`, `/api/admin/messages/*` | moderation a bulk workflow |
| navigation | `/api/admin/navigation` | validovaný strom a audit |
| notifications | `/api/admin/notifications/*` | test connectora bez leaknutia credentials |
| jobs | `/api/admin/jobs/*` | scheduler/queue; service identity a idempotency |
| code editor/developer | `/api/admin/code-editor/*`, `/api/admin/developer/*` | developer gate, code policy, path allow-list |
| extensions | `/api/admin/extensions/*` | ZIP policy scan, manifest, enable/disable |
| blueprints | `/api/admin/blueprints/*` | schema CRUD a validate sample |
| demo | `/api/admin/demo/*` | iba demo capability; produkčný fallback je bezpečne vypnutý |
| Git/publish | `/api/admin/git/*` | 🟡 existujúci sync; cieľ immediate/queued publish v It.70 |

Táto tabuľka je rodinný prehľad. Pre release-grade referenciu je potrebný generovaný route inventory/OpenAPI alebo test, ktorý porovná dokumentáciu s registrom.

---

## 9. Hybrid Engine API rozšírenia

| Iterácia | Očakávaný API povrch | Stav |
|----------|----------------------|------|
| It.68 | capability/schema/storage diagnostika bez vystavenia interných ciest | ⏳ |
| It.69 | cache status/rebuild + HTTP validators; Redis zostáva voliteľný | ⏳ |
| It.70 | `/api/admin/git/publish`, status/job detail, retry | ⏳ konsolidácia |
| It.71 | chránené APM metriky, napr. `/api/admin/metrics/apm` | ⏳ |
| It.72 | media driver capability a migrácia local/S3 | ⏳ |
| It.73 | locale-aware content read/write, explicitný fallback a revision | ⏳ |
| It.74 | API key lifecycle a krátko žijúce JWT | ⏳ |
| It.75 | AI proposal/tool workflow; human Apply, bez autonomous publish | ⏳ |
| It.76–77 | translate proposal/diff/Apply, provider status/quota | ⏳ |

Názov plánovanej route v historickej iterácii nie je automaticky finálny. Pred implementáciou musí prejsť threat modelom, naming review a kontraktným testom.

---

## 10. Request a response príklady

### Public list

```bash
curl --fail-with-body \
  'https://cms.example.com/api/articles?page=1&per_page=20&status=published'
```

### Session mutácia

```bash
curl --fail-with-body \
  --cookie cookies.txt \
  -H 'Content-Type: application/json' \
  -H 'X-CSRF-Token: <csrf-token>' \
  -X PUT \
  --data '{"title":"O nás","content":"...","baseRevision":"<revision>"}' \
  'https://cms.example.com/api/pages/o-nas'
```

### Plánovaný API key read

```bash
curl --fail-with-body \
  -H 'Authorization: Bearer pgk_<id>_<secret>' \
  'https://cms.example.com/api/articles?status=published'
```

Reálne tokeny, session cookies ani CSRF tokeny nepatria do Git repozitára, issue reportu alebo dokumentačného screenshotu.

---

## 11. Kompatibilita a deprecations

- Legacy auth envelope je podporovaný iba kvôli existujúcemu frontend flow; nový klient musí používať centralizovaný parser.
- Legacy zoznam bez `page/per_page` môže vrátiť všetky položky bez `meta`; nové UI má používať explicitné stránkovanie.
- Query aliasy, napríklad `perPage` a `per_page`, sa nesmú rozširovať donekonečna. Kanonický názov sa dokumentuje a alias dostane deprecation plán.
- Breaking zmena payloadu vyžaduje verziovanie alebo riadenú migráciu klienta, kontraktné testy a changelog.
- Neznámy field v response má klient ignorovať, pokiaľ nemení bezpečnostný význam. Neznámy write field má backend podľa schémy odmietnuť alebo explicitne ignorovať — nie potichu uložiť.

---

## 12. Testovanie a release gate

Minimálny gate:

1. route inventory alebo OpenAPI diff proti predchádzajúcemu release,
2. contract tests pre success/error/validation/409/auth/OTP/WAF výnimky,
3. session + CSRF + RBAC/path ACL testy,
4. public endpoint test, že nevracia drafty ani secret settings,
5. file upload abuse, path traversal a oversized body testy,
6. Postman/Newman smoke pre reprezentatívny public flow,
7. frontend MSW/Vitest testy nad rovnakými fixtures,
8. Classic profile test bez voliteľných providerov.

Súčasná Postman kolekcia je len malý smoke subset a nesmie byť vydávaná za úplný API opis.

---

## 13. Súvisiace dokumenty

- [API_CONTRACT.md](./API_CONTRACT.md) — response envelopes, chyby a status kódy
- [CONTENT_API.md](./CONTENT_API.md) — obsah, search, drafty, locks a publish lifecycle
- [BACKEND.md](./BACKEND.md) — route/controller/application hranice
- [FRONTEND.md](./FRONTEND.md) — API klient, session flow a UI architektúra
- [ADMIN_DEEP_LINKS.md](./ADMIN_DEEP_LINKS.md) — stabilný admin URL kontrakt
- [CORE_HARDENING.md](./CORE_HARDENING.md) — bezpečnostné invarianty
- [ITERATION_74.md](../ITERATION_74.md) — plán API keys a JWT
