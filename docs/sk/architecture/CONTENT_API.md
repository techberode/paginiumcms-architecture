---
title: Content API
description: Stránky, články, search, drafty, zámky a publish lifecycle
icon: material/file-document-edit
---

# 📝 Content API

> **Stav:** Public Beta kontrakt · It.73 locale schema uzamknutá v §15  
> **SSOT:** Markdown/JSON content dokumenty; index a cache sú odvodené  
> **Súbeh:** revision/OCC + edit lock + version history

Content API je spoločná hranica pre React editor, verejný web a budúce headless integrácie. Formát na disku nie je priamy HTTP kontrakt: klient pracuje s normalizovaným resource modelom a backend vlastní validáciu, storage layout, audit, versioning aj publish orchestration.

---

## 1. Resource model

Základné typy:

| Typ | Canonical key | Typické polia |
|-----|---------------|---------------|
| page | `page:{slug}` | title, slug, content, status, navigation/SEO metadata |
| article | `article:{slug}` | title, slug, content, excerpt, tags, author, date, comment policy, SEO |

Normalizovaný response môže obsahovať:

```json
{
  "type": "article",
  "slug": "novinka",
  "title": "Novinka",
  "status": "published",
  "content": "# Text",
  "contentFormat": "markdown",
  "html": "<h1>Text</h1>",
  "revision": "<revision>",
  "createdAt": "2026-08-01T10:00:00+02:00",
  "updatedAt": "2026-08-02T12:00:00+02:00"
}
```

Rendered `html` je odvodená reprezentácia. Kanonický content a metadata zostávajú vo file SSOT. HTML sa musí generovať/sanitizovať podľa dôveryhodnosti vstupu a nesmie byť cestou k stored XSS v admin ani public UI.

---

## 2. Čítanie zoznamov

```http
GET /api/pages
GET /api/articles
```

Odporúčaný explicitný query kontrakt:

| Parameter | Default | Pravidlo |
|-----------|---------|----------|
| `page` | `1` | celé číslo ≥ 1 |
| `per_page` | settings default | max 100; `perPage` je legacy alias |
| `status` | podľa identity | anonymous je serverom pripnutý na `published` |
| `search` | — | min. dĺžka podľa validátora; title/slug/excerpt/tags |
| `sort` | `-updatedAt` | allow-list fieldov; `-` znamená descending |
| `tag` / `filter[tag]` | — | exact normalizovaný tag pre articles |
| `author` / `filter[author]` | — | dokumentovaný match, nie voľný regex |
| `date_from`, `date_to` | — | validovaný ISO dátum a jednoznačná timezone policy |
| `locale` | site default | voliteľný filter zoznamu; public `published` = ľubovoľný locale published, ak nie je pinned (It.73 §15) |

Legacy request bez `page` alebo `per_page` môže vrátiť celý zoznam bez `meta`. Nový frontend/headless klient má používať stránkovaný režim.

Príklad:

```json
{
  "success": true,
  "data": [],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 143,
    "total_pages": 8,
    "tags": ["news", "php"],
    "total_published": 120
  }
}
```

Admin-only agregácie nesmú leaknúť počet draftov anonymnému klientovi.

---

## 3. Detail resource

```http
GET /api/pages/{slug}
GET /api/articles/{slug}
```

Pravidlá:

- anonymous: iba published a public podľa ACL/policy,
- authenticated editor: statusy podľa permission a path ACL,
- staff preview sa nesmie uložiť do public cache,
- neexistujúci a nepovolený resource môže byť bezpečnostne maskovaný rovnakou 404 policy,
- slug sa dekóduje raz, normalizuje a mapuje na canonical key; nikdy priamo na filesystem cestu.

Po It.69 môže response niesť `ETag`/`Last-Modified`; klient môže poslať `If-None-Match` a dostať 304 bez tela.

---

## 4. Vytvorenie a aktualizácia

Typický kontrakt:

```http
POST /api/pages
POST /api/articles
PUT /api/pages/{slug}
PUT /api/articles/{slug}
```

```json
{
  "title": "O nás",
  "slug": "o-nas",
  "content": "# O nás",
  "contentFormat": "markdown",
  "editorProfile": "company",
  "editorMode": "markdown",
  "status": "draft",
  "baseRevision": "<revision>",
  "lockToken": "<lock-token>"
}
```

| Field | Pravidlo |
|-------|----------|
| `content` | Markdown, HTML alebo Tiptap JSON string podľa `contentFormat` |
| `contentFormat` | `markdown`, `html`, `tiptap_json`; backend validuje a normalizuje |
| `editorProfile` | UI/editor capability hint; nesmie meniť authorization |
| `editorMode` | preferovaný režim editora, nie security control |
| `status` | allow-list + permission; save draft a publish sú odlišné use-cases |
| `baseRevision` | povinná pri update podľa OCC policy |
| `lockToken` | doklad aktívneho locku, ak workflow lock vyžaduje |
| `locale` | povinné pre multi-locale editor write; scope `title`/`content`/`status`/SEO na daný locale; bulk `localizedContent` zakázané (§15.5) |

Backend ignoruje klientom poslané fields, ktoré vlastní server, napríklad owner identity, audit actor, server timestamps alebo computed revision. Preferované je schema odmietnutie neznámych write fieldov.

---

## 5. Kanonický write lifecycle

```text
authentication → authorization/path ACL → schema validation
→ lock + baseRevision check → atomic SSOT write
→ version + audit → index update → cache invalidation
→ event → optional Git/translation/AI follow-up
```

Primárny response musí rozlíšiť:

- obsah nebol uložený,
- obsah bol uložený lokálne,
- publish job čaká,
- commit vznikol,
- push zlyhal.

Voliteľný provider nesmie dostať raw session, CSRF, storage path alebo secret settings.

---

## 6. Drafty a autosave

```http
GET    /api/drafts/{type}/{slug}
PUT    /api/drafts/{type}/{slug}
DELETE /api/drafts/{type}/{slug}
```

Draft obsahuje aspoň resource key, ownera, pracovný obsah, base revision a timestamp. Autosave:

- nepublikuje,
- neobchádza permission,
- nepovažuje lokálny browser stav za serverový save,
- používa debounce a zrušenie zastaraných requestov,
- pri 409 zastaví slepé retry a otvorí conflict flow,
- po úspešnom final save vyčistí alebo označí draft podľa policy.

Draft iného používateľa alebo locale nesmie byť vrátený bez explicitného oprávnenia.

---

## 7. Edit locks

Rodina `/api/locks/*` zabezpečuje acquire, heartbeat a release. Presný route tvar sa má zjednotiť; historická dokumentácia používa viac variantov.

Lock nie je náhradou OCC. Chráni UX pred súbežnou editáciou, ale expiry, výpadok browsera alebo background write stále vyžaduje revision check.

Minimálne fields:

- canonical resource key,
- owner/actor ID,
- opaque lock token,
- acquired/heartbeat/expiry time,
- voliteľná locale vetva, iba ak zodpovedá revision modelu.

---

## 8. Konflikty a merge

Pri revision mismatch API vracia 409 `conflict`. Frontend môže ponúknuť Mine, Theirs, Both alebo ručný 3-way merge. Následný save používa najnovšiu `serverRevision`.

Zakázané správanie:

- automatický overwrite po 409,
- force save skrytý pod bežným tlačidlom,
- merge lokalizovanej vetvy, ktorý zahodí inú locale,
- leak server contentu, na ktorý actor nemá read permission.

---

## 9. Status a publish

Odporúčaný lifecycle:

```text
draft → review/approved podľa policy → published → archived
```

Aktuálny projekt môže používať menšiu množinu statusov; API musí odmietnuť neznámy enum a nemá odvodiť publish iba z prítomnosti dátumu.

Citlivý publish môže vyžadovať OTP challenge. It.70 pridá immediate alebo queued Git publish, ale local SSOT save zostáva prvá autoritatívna operácia.

UI a API rozlišujú:

```text
stored | pending_publish | committed | pushed | publish_failed
```

Retry publikuje konkrétnu revision a používa idempotency key; nesmie omylom distribuovať novší neoverený obsah.

---

## 10. Soft delete a trash

```http
DELETE /api/pages/{slug}
DELETE /api/articles/{slug}
```

Delete presunie resource do trash s metadata sidecarom; nejde o permanentné zmazanie. Restore/purge patria do chráneného admin trash API.

Soft delete musí:

- zachovať pôvodný canonical key a revision,
- auditovať actora a dôvod, ak je vyžadovaný,
- aktualizovať index/cache,
- riešiť kolíziu, ak už existuje nový resource s rovnakým slugom,
- nezmazať binárne médiá zdieľané iným obsahom bez reference policy.

---

## 11. Bulk operácie

Historické endpointy zahŕňajú bulk delete/status pre pages/articles. Bulk request je sada individuálne autorizovaných use-cases, nie jedna prefix permission.

Response má rozlíšiť úspech a chybu per item. Atomický „všetko alebo nič“ režim sa nesmie predstierať, ak flat-file backend nemá transaction journal. Pri partial success musí byť výsledok jednoznačný a bezpečne retry-nuteľný.

---

## 12. Search

### Public search

```http
GET /api/search?q=home&scope=public&types=page,article&limit=20
```

- minimum query length podľa validátora,
- iba published content,
- index je odvodený a rebuildovateľný,
- poškodený index sa nemá potichu tváriť ako nulový obsah bez diagnostiky/fallback policy.

### Admin command palette

```http
GET /api/search?q=set&scope=admin&types=page,article,media,route&limit=8
```

Vyžaduje session a vracia iba výsledky, ktoré actor smie vidieť/otvoriť. `adminPath` je navigačný hint, nie authorization. Admin search z It.43 je v checkpointe považovaný za dodaný základ, nie „unreleased“.

---

## 13. SEO a comments policy

SEO fields pre pages/articles:

| API field | Význam |
|-----------|--------|
| `seoTitle` | override title; fallback na content title |
| `seoDescription` | meta description |
| `canonical` | validovaná absolute URL alebo prázdne |
| `ogImage` | media URL podľa policy |
| `noIndex` | boolean |
| `tags` | article taxonomy |
| `featuredImage` | article card/hero derivation podľa modelu |

`GET /api/seo/{type}/{slug}` vracia iba publikovaný bezpečný slice.

Article comment fields môžu override-nuť globálnu policy, ale resolver kombinuje global settings + resource override. Klient si nemôže sám vynútiť zobrazenie nepovolených komentárov.

---

## 14. Storage formát

`content.storageFormat` môže vybrať `md` alebo `json` pre nové save operácie:

| Formát | SSOT |
|--------|------|
| `md` | YAML front matter + Markdown body |
| `json` | normalizovaný JSON objekt s content fieldom |

API model zostáva stabilný bez ohľadu na disk formát. Migrácia nesmie vytvoriť dve autoritatívne kópie rovnakého resource. Index obsahuje iba odvodené lookup metadata.

---

## 15. Locale-aware content — It.73 (locked)

> **Stav:** Doručené v `[Unreleased]` (fázy 1–2d). Táto sekcia je kanonický HTTP a on-disk kontrakt pre viacjazyčný obsah.

### 15.1 Podporované locale

| Zdroj | Pravidlo |
|-------|----------|
| Allow-list | `config/i18n/locales.json` cez `SupportedLocalesRegistry` |
| Built-in MVP | `sk`, `en` (dvojpísmenové lowercase kódy) |
| Site default | nastavenie `general.language` (fallback `sk`) |
| Nepodporovaný kód pri write | `422` s field errors |
| Nepodporovaný kód pri public read | ignorovaný; resolver padá na default/fallback |

### 15.2 Kanonický tvar dokumentu (schema v2)

Jedna identita resource (`slug`, globálne metadata) s locale slice v SSOT (Markdown front matter alebo JSON):

```json
{
  "schemaVersion": 2,
  "slug": "about-us",
  "defaultLocale": "sk",
  "localizedContent": {
    "sk": {
      "title": "O nás",
      "body": "# …",
      "seo": {
        "title": "",
        "description": "",
        "canonical": "",
        "ogImage": "",
        "noIndex": false
      }
    },
    "en": {
      "title": "About us",
      "body": "# …",
      "seo": {
        "title": "",
        "description": "",
        "canonical": "",
        "ogImage": "",
        "noIndex": false
      }
    }
  },
  "localeStatus": { "sk": "published", "en": "draft" },
  "revision": "<revision>",
  "updatedAt": "2026-08-02T12:00:00+02:00"
}
```

| Pole | Scope | Pravidlo |
|------|-------|----------|
| `schemaVersion` | global | `2` pre perzistentné multi-locale dokumenty |
| `defaultLocale` | global | Musí byť podporované; riadi flat legacy polia |
| `localizedContent.{locale}.title` | locale | Povinné na publish daného locale |
| `localizedContent.{locale}.body` | locale | Kanonické telo pre daný locale |
| `localizedContent.{locale}.seo.*` | locale | SEO slice; default locale mapuje na flat `seoTitle`, `seoDescription`, … |
| `localeStatus.{locale}` | locale | `draft`, `published`, `archived` alebo `scheduled` |
| Flat `title`, `content`, `status`, SEO | legacy compat | Sync z **default locale** slice pre index a single-locale reader |

**Legacy schema v1** (`schemaVersion` chýba alebo `< 2`) zostáva čitateľná. `LocalizedContentNormalizer` syntetizuje single-locale canonical view pri read bez mutácie disku. Voliteľný batch upgrade: `content:locale-migrate` (§15.9).

### 15.3 Public read — locale resolution

Platí pre anonymné `GET /api/pages/{slug}` a `GET /api/articles/{slug}` (a ich cacheované public varianty).

Deterministické poradie (`LocaleResolver`):

1. Explicitné `?locale=` ak je podporované a existuje na resource,
2. `Accept-Language` ak `content.localeNegotiationEnabled` je `true` (default),
3. Resource `defaultLocale` ak existuje príslušný slice,
4. Prvé dostupné locale na resource ak `content.localeFallbackEnabled` je `true` (default),
5. Inak site/resource default s `fallback: true` v `_locale` metadata.

| Nastavenie | Default | Efekt |
|------------|---------|-------|
| `content.localeNegotiationEnabled` | `true` | Zapne krok `Accept-Language` |
| `content.localeFallbackEnabled` | `true` | Zapne cross-locale read fallback |

Resolver **nikdy neperzistuje** fallback text ako hotový preklad. Fallback ovplyvňuje len vrátený slice.

Public viditeľnosť používa status **resolved locale** (aplikovaný do top-level `status`). Anonymný klient dostane `404`, ak dané locale nie je `published`.

List endpointy akceptujú voliteľné `locale`. Pri `status=published` a per-locale status matchne resource, keď je **ľubovoľné** locale published, pokiaľ `locale` nepinne filter na jeden kód.

### 15.4 Public response — locale metadata

Public detail response obsahuje `_locale` popri flatten poliach:

```json
{
  "title": "About us",
  "content": "# …",
  "status": "published",
  "_locale": {
    "requested": "en",
    "resolved": "sk",
    "fallback": true,
    "available": ["sk", "en"],
    "defaultLocale": "sk",
    "sliceFound": true
  },
  "schemaVersion": 2,
  "defaultLocale": "sk"
}
```

| `_locale` kľúč | Význam |
|----------------|--------|
| `requested` | Z `?locale=` alebo negotiated header; `null` ak nič neprišlo |
| `resolved` | Locale slice aplikovaný na top-level polia |
| `fallback` | `true` keď `requested` ≠ `resolved` alebo bol použitý cross-locale fallback |
| `available` | Locale kódy prítomné v `localizedContent` |
| `defaultLocale` | Default resource |
| `sliceFound` | `false` keď resolved slice chýba |

Autentifikovaný editor GET vracia **plný** kanonický objekt (`localizedContent`, `localeStatus`, všetky locale) bez `_locale` overlay.

HTTP cache (It.69): public response môže obsahovať `Vary: Accept-Language`. Cache key zahŕňa requested locale a `Accept-Language`.

### 15.5 Locale-scoped writes

`POST` / `PUT` akceptujú locale-scoped payload:

```json
{
  "locale": "en",
  "title": "About us",
  "content": "# About us",
  "status": "draft",
  "seoTitle": "About us",
  "seoDescription": "…",
  "baseRevision": "<revision>"
}
```

| Pravidlo | Detail |
|----------|--------|
| `locale` povinné | Pre locale-scoped write; unsupported → `422` |
| `proposalSource` | Pri `translation`, `ai` alebo `machine_translation` nesmie byť `status` = `published` → `422` |
| Bulk zakázané | Klient **nesmie** poslať `localizedContent` alebo `localeStatus` → `422` |
| Scope polí | `title`, `content`, `status`, SEO platia len pre dané locale |
| OCC | Jeden document `revision` / `baseRevision` chráni celý resource |
| Upgrade | Legacy dokument upgrade na `schemaVersion: 2` pri prvom locale-scoped save |
| Flat sync | Flat polia default locale sa aktualizujú po merge |

Write bez `locale` zachováva legacy kompatibilitu, multi-locale editor ho nepoužíva.

### 15.6 Per-locale publish

```http
PATCH /api/pages/{slug}/status
PATCH /api/articles/{slug}/status
```

```json
{ "locale": "en", "status": "published" }
```

| Payload | Správanie |
|---------|-----------|
| `locale` + `status` | Aktualizuje jeden záznam v `localeStatus` |
| len `status` na v2 doc | Aktualizuje default locale status |
| Publish validácia | Prázdny draft locale OK; `published` vyžaduje neprázdny locale `title` → `422` |
| OTP | Publish-approval OTP podľa nastavenia |

Publish jedného locale nepublikuje ostatné. Slug zostáva globálny.

### 15.7 Index metadata

Odvodené index entries obsahujú `defaultLocale`, `locales` a `localeStatus` pre admin badge a locale-aware filtre. Index je rebuildable zo SSOT.

### 15.8 Locale-specific errors

| Situácia | Response |
|----------|----------|
| Unsupported locale pri write | `422` + field errors |
| Bulk `localizedContent` v body | `422` |
| Publish locale bez title | `422` |
| Revision mismatch pri locale save | `409` conflict (celý resource) |

### 15.9 Migration CLI (voliteľné)

Read kompatibilita nevyžaduje migráciu. Operátorské príkazy:

```bash
php backend/bin/console content:locale-migrate inventory
php backend/bin/console content:locale-migrate dry-run --default-locale=sk
php backend/bin/console content:locale-migrate run --default-locale=sk --yes
php backend/bin/console content:locale-migrate rollback --migration-id=<id> --yes
```

Backup a manifest: `data/migrations/<id>/`. Automatický merge ambiguous locale-copy párov je zakázaný.

Pozri [ITERATION_73.md](../ITERATION_73.md) pre migration DoD.

### 15.10 Out of scope (It.73)

- Per-locale slug/routing,
- Locale-specific ACL,
- Automatický translation publish (It.76–77),
- Per-locale revision/OCC (MVP používa jednu document revision).

It.76–77 translation vytvára proposal/diff; Apply je samostatná autorizovaná operácia a nesmie auto-publish.

---

## 16. Headless scopes — It.74

MVP read scope `content:read` sprístupní iba publikovaný headless slice. `content:write` je explicitný opt-in a používa rovnakú schema, revision, lock, audit a publish policy ako session flow.

API key scope nikdy automaticky neotvára `/api/admin/*`. Route + method musia byť v allow-list mape.

---

## 17. Chyby

| Situácia | Status/shape |
|----------|--------------|
| neplatný filter/payload | `422` + `errors` |
| chýbajúca identita | `401` |
| chýbajúca permission/scope | `403` |
| nenájdený alebo maskovaný resource | `404` |
| revision mismatch | `409` + `conflict` |
| aktívny foreign lock | `409` + `lock` |
| upload/body limit | `413` |
| rate limit | `429` |
| maintenance | `503` podľa allow-list policy |

Frontend nemá po 401 automaticky znovu odoslať write, kým bezpečne neobnoví session a CSRF stav.

---

## 18. Frontend wiring

| Schopnosť | Client/hook | UI zodpovednosť |
|-----------|-------------|-----------------|
| lists/detail/save | central content API module | Pages/Articles manager + editor |
| search | search API | public search + Ctrl+K palette |
| draft | drafts client + autosave hook | stav „saving/saved/conflict“ |
| lock | locks client + heartbeat hook | owner/expiry banner |
| versions | versions client | history, compare, restore |
| OTP | workflows client | challenge modal bez falošného success |
| publish | content/Git client | local save vs distribution status |
| locale/translation | content API + editor locale tabs | `_locale` pri public read; plný canonical pri admin GET; locale-scoped save (§15) |

Konkrétne filenames sa môžu pri refaktore meniť; verejný kontrakt je behavior a typed interface, nie dnešný import path.

---

## 19. Testovanie

- anonymous list/detail nikdy nevráti draft,
- pagination/filter/sort bounds a deterministic order,
- slug/path traversal a double decode,
- schema pre každý content format,
- lock + OCC race a opakovaný 409,
- autosave nezverejní draft,
- soft delete/restore/collision,
- bulk partial failure,
- search ACL a adminPath bezpečnosť,
- SEO URL/media validation,
- locale fallback bez data leak/lost update,
- zlyhaný index/cache/Git/provider s bezpečným fallbackom,
- session a budúci API key write prechádzajú rovnakou doménovou policy.

---

## 20. Súvisiace dokumenty

- [API.md](./API.md) — kompletný HTTP povrch
- [API_CONTRACT.md](./API_CONTRACT.md) — envelopes a chyby
- [VERSIONING.md](./VERSIONING.md) — draft, lock, revision, merge, restore
- [STORAGE.md](./STORAGE.md) — SSOT, index, cache a atomický write
- [FRONTEND.md](./FRONTEND.md) — editor lifecycle a API client
- [ADMIN_DEEP_LINKS.md](./ADMIN_DEEP_LINKS.md) — stabilné edit/list URL
