# PaginiumCMS – API Response Contract

> **Version:** 2.0.9 · **Iteration:** 21  
> Jednotný tvar JSON odpovedí pre backend aj frontend (`api/client.ts`).

---

## 1. Základný princíp

Všetky HTTP endpointy vracajú **JSON** s hlavičkou:

```
Content-Type: application/json; charset=utf-8
```

Každá odpoveď obsahuje pole **`success`** (`true` | `false`).

---

## 2. Typy obalov (envelopes)

### 2.1 Štandardný úspech (CRUD, zoznamy)

Používa `JsonResponder::success()` / `paginated()`.

```json
{
  "success": true,
  "data": { },
  "message": "Voliteľná správa"
}
```

| Pole | Typ | Povinné | Popis |
|------|-----|---------|-------|
| `success` | `boolean` | áno | Vždy `true` |
| `data` | `mixed` | áno | Payload (objekt, pole, `null`) |
| `message` | `string` | nie | Ľudská správa (delete, update, …) |
| `meta` | `object` | nie | Len pri stránkovaní (pozri §3) |

### 2.2 Chyba (4xx / 5xx)

Používa `JsonResponder::error()` alebo `ApiErrorHandler`.

```json
{
  "success": false,
  "error": "Popis chyby"
}
```

| Pole | Typ | Povinné |
|------|-----|---------|
| `success` | `boolean` | áno (`false`) |
| `error` | `string` | áno |

### 2.3 Validačná chyba (422)

Používa `JsonResponder::validation()` alebo `ValidationException` v error handleri.

```json
{
  "success": false,
  "error": "Validácia zlyhala",
  "errors": {
    "email": ["Neplatný formát e-mailu"],
    "password": ["Heslo musí mať aspoň 12 znakov"]
  }
}
```

| Pole | Typ | Povinné |
|------|-----|---------|
| `errors` | `Record<string, string[]>` | áno pri 422 |

Frontend mapuje cez `ApiResponse.errors` v `api/client.ts`.

### 2.4 Konflikt obsahu (409)

Používa `JsonResponder::conflict()` s kontextom `conflict`.

```json
{
  "success": false,
  "error": "Obsah bol medzitým zmenený",
  "conflict": {
    "serverRevision": "abc123",
    "serverContent": { }
  }
}
```

### 2.5 Konflikt zámku (409)

Rovnaký tvar ako §2.4, ale s kľúčom **`lock`** namiesto `conflict`.

```json
{
  "success": false,
  "error": "Obsah je zamknutý iným používateľom",
  "lock": {
    "contentType": "page",
    "slug": "about",
    "ownerId": "…",
    "expiresAt": 1710000000
  }
}
```

### 2.6 Auth / legacy plochý obal

Auth endpointy (`POST /api/auth/login`, register, 2FA) používajú `JsonResponder::respond()` – **`user` a ďalšie polia sú na root úrovni**, nie v `data`:

```json
{
  "success": true,
  "user": {
    "id": "…",
    "email": "admin@example.com",
    "roles": ["ADMIN"]
  },
  "requires_two_factor": false
}
```

Frontend `ApiResponse` podporuje oba tvary (spätne kompatibilné).

---

## 3. Stránkovanie (Iterácia 19)

Request: `?page=1&per_page=20&search=&status=`

```json
{
  "success": true,
  "data": [ ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 142,
    "total_pages": 8
  }
}
```

**Legacy režim:** ak chýba `page` / `per_page`, API vráti celé pole v `data` **bez** `meta`.

---

## 4. HTTP status kódy

| Kód | Význam | Typický tvar |
|-----|--------|--------------|
| 200 | OK | `{ success: true, data }` |
| 201 | Vytvorené | `{ success: true, data, message? }` |
| 400 | Zlý request | `{ success: false, error }` |
| 401 | Neautentifikovaný | `{ success: false, error }` |
| 403 | Zakázané (RBAC) | `{ success: false, error }` |
| 404 | Nenájdené | `{ success: false, error }` |
| 409 | Konflikt (obsah/zámok) | `{ success: false, error, conflict\|lock }` |
| 422 | Validácia | `{ success: false, error, errors }` |
| 503 | Maintenance mode | `{ success: false, error }` |
| 500 | Server error | `{ success: false, error }` (+ `exception` v debug) |

---

## 5. Implementácia (backend)

| Súbor | Úloha |
|-------|--------|
| `Http/Support/JsonResponder.php` | `success`, `paginated`, `error`, `validation`, `conflict`, `respond` |
| `Http/Support/ApiErrorHandler.php` | Neošetrené výnimky → JSON |
| `Http/Support/PaginationMeta.php` | `meta` objekt |

Controllery **nemajú** vlastné `jsonSuccess` / `jsonError` – injektuje sa `JsonResponder`.

---

## 6. Frontend kontrakt

| Súbor | Úloha |
|-------|--------|
| `frontend/src/api/client.ts` | `ApiResponse<T>`, interceptors, 422/409 handling |
| `frontend/src/mocks/handlers.ts` | MSW handlery (dev + Vitest) |
| `frontend/src/mocks/browser.ts` | Zapnutie cez `VITE_MSW=true` |

---

## 7. Testovanie

| Test | Cesta |
|------|--------|
| JsonResponder unit | `backend/tests/Http/Support/JsonResponderTest.php` |
| Response shape (HTTP) | `backend/tests/Http/Contract/ApiResponseShapeTest.php` |
| Postman smoke | `docs/api/PaginiumCMS.postman_collection.json` |
| Newman (voliteľne) | `npm run test:api-smoke` (root) |
| MSW contract | `frontend/src/mocks/handlers.test.ts` |

---

## 8. Výnimky (legacy)

Všetky HTTP controllery používajú `JsonResponder`. Auth endpointy používajú `respond()` pre plochý obal (`user` na root úrovni) — dokumentované v §2.6.

---

## Súvisiace dokumenty

- [API.md](./API.md) – zoznam endpointov
- [CONTENT_API.md](./CONTENT_API.md) – content CRUD + soft-delete
- [CORE_HARDENING.md](./CORE_HARDENING.md) – RBAC, maintenance, trash
