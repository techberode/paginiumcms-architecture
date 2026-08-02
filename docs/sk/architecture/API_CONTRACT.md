---
title: API kontrakt odpovedí
description: Jednotné JSON envelopes, chyby a kompatibilita klientov
icon: material/code-json
---

# 📦 API response kontrakt

> **Stav:** kanonický kontrakt pre Public Beta checkpoint `v2.1.0-beta.23`  
> **Platí pre:** Slim backend, React API client, MSW fixtures a headless integrácie  
> **Výnimky:** binárne/streamované odpovede, RSS/sitemap/robots a pre-routing WAF blok

Cieľom kontraktu je, aby klient vedel spoľahlivo rozlíšiť úspech, validačnú chybu, konflikt, chýbajúcu identitu a follow-up stav bez parsovania ľudskej vety. Dokument zároveň priznáva legacy auth envelope a plain WAF 403; nepredstiera, že už boli migrované.

---

## 1. Všeobecné pravidlá

JSON endpoint vracia:

```http
Content-Type: application/json; charset=utf-8
```

Základné pravidlá:

- `success` je boolean a pri štandardnom JSON envelope je vždy prítomný,
- úspešný payload patrí do `data`, okrem zdokumentovaných legacy auth odpovedí,
- neúspech obsahuje bezpečnú ľudskú správu v `error`,
- field-level validácia používa `errors`,
- 409 používa typovaný detail `conflict` alebo `lock`,
- produkčná odpoveď nikdy neobsahuje stack trace, filesystem cestu, secret, token ani raw provider response,
- názvy strojových polí sú stabilné a nelokalizované; lokalizuje sa UI správa, nie contract key.

Odporúčaný cieľ je doplniť strojový `code` a korelačný `requestId`. Kým nie sú implementované konzistentne vo všetkých cestách, klient ich musí brať ako voliteľné.

---

## 2. Štandardný úspech

```json
{
  "success": true,
  "data": {
    "id": "page-home"
  },
  "message": "Obsah bol uložený"
}
```

| Pole | Typ | Povinné | Pravidlo |
|------|-----|---------|----------|
| `success` | boolean | áno | vždy `true` |
| `data` | ľubovoľný JSON typ | áno pri štandardnom envelope | objekt, pole, scalar alebo `null` podľa endpointu |
| `message` | string | nie | UX pomoc; klient z nej nesmie odvodzovať stav |
| `meta` | object | nie | pagination, capability alebo bezpečné transportné metadata |

`204 No Content` je povolené pre endpoint, ktorý dokumentuje prázdnu odpoveď, napríklad disabled telemetry. Taká odpoveď nemá JSON telo a klient ju nesmie posielať do JSON parsera.

---

## 3. Stránkovaný úspech

```json
{
  "success": true,
  "data": [],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 142,
    "total_pages": 8
  }
}
```

Kanonické fields sú `page`, `per_page`, `total`, `total_pages`. Doménové agregácie, napríklad `tags` alebo `total_published`, môžu byť v `meta`, ale nesmú meniť význam základných položiek.

Legacy list bez `page`/`per_page` môže vrátiť celé pole bez `meta`. Nové klienty majú vždy poslať explicitné stránkovanie. Cursor pagination nie je súčasťou aktuálneho kontraktu.

---

## 4. Štandardná chyba

```json
{
  "success": false,
  "error": "Požadovanú operáciu nebolo možné vykonať",
  "code": "operation_failed",
  "requestId": "req_..."
}
```

Garantované minimum v aktuálnom kontrakte je `success: false` a `error`. `code` a `requestId` sú cieľové voliteľné fields, kým ich implementácia nebude pokrytá kontraktnými testami na každej error path.

`error` musí byť bezpečný pre používateľa. Detail incidentu patrí do serverového logu korelovaného cez request ID, nie do browser response.

---

## 5. Validačná chyba 422

```json
{
  "success": false,
  "error": "Validácia zlyhala",
  "errors": {
    "email": ["Neplatný formát e-mailu"],
    "content.title": ["Názov je povinný"]
  }
}
```

`errors` je `Record<string, string[]>`. Field path musí byť stabilný a mapovateľný na formulár. Backend je autorita aj vtedy, keď frontend používa rovnakú schému pre okamžitú UX validáciu.

Citlivé vstupy, napríklad heslo, API key secret alebo provider token, sa nesmú echo-núť do `errors` ani logu.

---

## 6. Konflikt obsahu 409

```json
{
  "success": false,
  "error": "Obsah bol medzitým zmenený",
  "conflict": {
    "serverRevision": "<revision>",
    "serverContent": {},
    "changedAt": "2026-08-02T12:00:00+02:00"
  }
}
```

`serverRevision` je concurrency fingerprint, nie kryptografický dôkaz integrity. `serverContent` sa vracia iba v rozsahu, ktorý je používateľ oprávnený vidieť; konflikt nesmie leaknúť inú locale, draft alebo ACL-chránené fieldy.

Klient po merge ukladá voči novej server revision. Force overwrite je samostatná privilegovaná operácia, nie skrytý retry.

---

## 7. Konflikt zámku 409

```json
{
  "success": false,
  "error": "Obsah upravuje iný používateľ",
  "lock": {
    "contentType": "page",
    "slug": "about",
    "ownerId": "user-123",
    "expiresAt": "2026-08-02T12:05:00+02:00"
  }
}
```

Lock detail nesmie verejne zobraziť viac osobných údajov, než UI potrebuje. Serverový timestamp má mať jednotný ISO 8601 formát; historický epoch timestamp sa pri migrácii musí normalizovať alebo jasne typovať.

---

## 8. Auth legacy envelope

Existujúce login/register/2FA flow môže vracať fields na root úrovni:

```json
{
  "success": true,
  "user": {
    "id": "user-123",
    "email": "admin@example.com",
    "roles": ["ADMIN"]
  },
  "requires_two_factor": false
}
```

Toto je **zdokumentovaná kompatibilitná výnimka**, nie vzor pre nové endpointy. Frontend parser môže dočasne normalizovať:

```text
legacy root fields → internal data model
```

Migrácia musí byť koordinovaná backend + frontend + MSW + contract tests. Tiché presunutie `user` do `data` bez verziovania by rozbilo existujúceho klienta.

---

## 9. OTP challenge envelope

Citlivá mutácia môže vrátiť HTTP 200 s challenge namiesto dokončenej operácie:

```json
{
  "success": true,
  "requires_otp": true,
  "challenge_id": "otp_abc123",
  "message": "Overenie OTP je potrebné"
}
```

Semantika:

- request bol prijatý, ale doménová akcia ešte nie je potvrdená,
- frontend otvorí OTP flow a nezobrazí publish/save ako dokončený,
- challenge má ownera, TTL, attempt/resend limit a viazanie na konkrétnu akciu,
- `debug_code` je povolený iba v explicitnom development/testing režime a nikdy v produkcii.

Dlhodobým cieľom môže byť typovaný `data.actionState`, ale kompatibilný wire formát sa nesmie meniť bez migračného plánu.

---

## 10. Asynchrónny a odvodený follow-up

Hybrid Engine potrebuje odlíšiť primárne uloženie od publish/prekladu/AI jobu. Odporúčaný doménový payload:

```json
{
  "success": true,
  "data": {
    "resource": { "type": "article", "slug": "novinka", "revision": "<revision>" },
    "storageState": "stored",
    "publishState": "pending_publish",
    "jobId": "job_..."
  }
}
```

Stavy ako `stored`, `pending_publish`, `committed`, `pushed` a `publish_failed` patria do `data`, nie do boolean `success`. Zlyhaný Git push po úspešnom local save nemá premeniť response na tvrdenie, že obsah nebol uložený.

Presný field model sa finalizuje v implementácii It.70 a musí byť zdieľaný backendom, frontendom a event payloadom.

---

## 11. Ne-JSON výnimky

| Endpoint/trieda | Content type | Klientské pravidlo |
|-----------------|--------------|--------------------|
| RSS/sitemap | XML | neparsovať cez `ApiResponse` |
| `robots.txt` | text/plain | textový response |
| médiá/download/export | podľa súboru | stream/blob, kontrola filename/content disposition |
| `204` telemetry/no-op | bez tela | nevolať JSON parser |
| WAF jail/tarpit | často prázdny alebo textový 403 | spracovať status skôr než content type |

Pre-routing WAF môže zámerne vrátiť plain 403, pretože blokuje request pred route responderom. Klient musí zvládnuť non-JSON 401/403/5xx bez sekundárnej parser chyby.

---

## 12. HTTP status kódy

| Kód | Význam v PaginiumCMS |
|-----|----------------------|
| `200` | úspešné čítanie/mutácia alebo OTP challenge podľa legacy flow |
| `201` | vytvorený resource |
| `202` | rezervované pre prijatý async job, keď route taký kontrakt explicitne zavedie |
| `204` | úspech bez tela |
| `400` | malformed request alebo nesplnený základný precondition |
| `401` | chýbajúca/neplatná/expired identita |
| `403` | identita existuje, ale nemá permission/scope; alebo pre-routing WAF block |
| `404` | resource neexistuje alebo je podľa bezpečnostnej policy maskovaný |
| `409` | revision/lock/idempotency conflict |
| `412` | plánované použitie pre failed HTTP precondition, ak sa zavedie `If-Match` |
| `413` | request/upload príliš veľký |
| `415` | nepodporovaný content type/MIME |
| `422` | schema/field validation |
| `429` | rate limit; response nesmie prezradiť citlivé limiter internals |
| `500` | neočakávaná serverová chyba; safe message + server log |
| `503` | maintenance alebo dočasne nedostupná povinná capability |

Nedostupný voliteľný Redis/S3/provider nemá automaticky spôsobiť 503 celej aplikácie, ak existuje bezpečný Classic fallback.

---

## 13. JSON dátové konvencie

- dátumy: ISO 8601/RFC 3339 s offsetom alebo `Z`,
- boolean: skutočný JSON boolean, nie `"0"`/`"1"`,
- absent a `null` majú odlišný význam a musia byť zdokumentované,
- enumy sú malé písmená so stabilnými ASCII hodnotami,
- ID/slug sa neprekladajú,
- locale používa validovaný BCP 47-like tag podľa implementovanej allow-list schémy,
- `content` môže byť Markdown, HTML alebo serializovaný Tiptap JSON podľa `contentFormat`,
- tajný field je write-only/redacted; placeholder `********` sa nesmie omylom uložiť ako nový secret.

---

## 14. Cache a conditional requests

It.69 plánuje `ETag`/`Last-Modified` a odpoveď `304 Not Modified`. Pri 304 nie je JSON telo. Cache key musí zahŕňať všetky faktory meniace reprezentáciu, najmä public/admin scope, locale, query filtre a permission-relevant varianty.

Private/admin response nesmie skončiť vo verejnej cache. `Vary` a cache-control policy musia byť kontraktnými testami, nie iba nginx konfiguráciou „niekde bokom“.

---

## 15. Frontend parser pravidlá

Centralizovaný API client musí:

1. najprv vyhodnotiť status a content type,
2. bezpečne zvládnuť prázdne/non-JSON telo,
3. normalizovať iba zdokumentované legacy auth odpovede,
4. zachovať field errors pre formulár,
5. rozlišovať 401, 403, 409, 422, 429 a maintenance,
6. neprepisovať serverový `error` generickým JSON parse errorom,
7. redigovať tokeny a citlivé payloady z klientského telemetry logu,
8. nikdy automaticky retry-nuť non-idempotent write bez revision/idempotency ochrany.

---

## 16. Testovanie a verzovanie kontraktu

Povinné fixtures/tests:

- štandardný success a paginated success,
- standard/validation error,
- content conflict a lock conflict,
- legacy auth success + 2FA,
- OTP challenge,
- plain WAF 403 a 204 bez tela,
- binary/export response,
- 429 a maintenance,
- plánovaný stored/publish follow-up,
- prod error bez stack trace a secretov.

Breaking zmena envelope vyžaduje changelog, backend test, frontend parser test, MSW fixture update a migračné obdobie alebo API verziovanie.

---

## 17. Súvisiace dokumenty

- [API.md](./API.md) — endpoint rodiny a autentifikačná matica
- [CONTENT_API.md](./CONTENT_API.md) — resource model a OCC lifecycle
- [FRONTEND.md](./FRONTEND.md) — centralizovaný client a UX mapovanie chýb
- [CORE_HARDENING.md](./CORE_HARDENING.md) — WAF, CSRF, CORS, redaction
- [VERSIONING.md](./VERSIONING.md) — revision, locks, merge a publish state
