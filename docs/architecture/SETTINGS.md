# PaginiumCMS – Iterácia 4: Settings, Error Handler, Validácia

> Doplnok k `API.md`. Flat-file nastavenia, jednotný JSON error obal, zdieľané validačné pravidlá FE↔BE.

---

## 1. Settings engine

### Úložisko

- Súbor: `backend/storage/app/content/data/settings.json`
- Ukladajú sa **iba odchýlky** od predvolieb v `SettingsSchema`
- Súbežnosť: `flock(LOCK_EX)` (`SettingsRepository`)

### Endpointy

| Metóda | Endpoint | Auth | Popis |
|---|---|---|---|
| `GET` | `/api/settings/public` | prihlásený | Verejný výrez (general/content/editor) pre celú aplikáciu |
| `GET` | `/api/admin/settings` | ADMIN | Schéma + efektívne hodnoty všetkých skupín |
| `GET` | `/api/admin/settings/{group}` | ADMIN | Schéma + hodnoty jednej skupiny |
| `PUT` | `/api/admin/settings/{group}` | ADMIN | Validácia + uloženie skupiny |
| `DELETE` | `/api/admin/settings` | ADMIN | Reset na predvolené hodnoty |

### Skupiny schémy

- `general` – siteName, siteUrl, adminEmail, language, timezone, maintenanceMode
- `content` – itemsPerPage, defaultStatus, autoSaveInterval, lockTtl
- `editor` – defaultEditor, spellcheck, tabSize

### Frontend

| Súbor | Rola |
|---|---|
| `api/settings.ts` | Typované volania |
| `components/backend/SettingsView.tsx` | Generický formulár riadený schémou |
| `context/SettingsContext.tsx` | Globálny prístup k nastaveniam |
| `hooks/useSettings.ts` | Skrátený hook |
| `hooks/useAutoSave.ts` | Interval z `content.autoSaveInterval` |

---

## 2. Jednotný Error Handler

Registrovaný v `bootstrap/app.php` cez `ApiErrorHandler`.

| Výnimka | HTTP | JSON obal |
|---|---|---|
| `ValidationException` | 422 | `{ success: false, error, errors: { pole: [správy] } }` |
| Slim `HttpException` | kód výnimky | `{ success: false, error }` |
| ostatné | 500 | `{ success: false, error }` (+ detaily v debug režime) |

404 catch-all tiež vracia `{ success: false, error, path }`.

---

## 3. Zdieľaná validácia

### Backend

- `Core/Validation/Validator.php` – bezstavový validátor pravidiel
- `Core/Validation/ValidationRules.php` – katalóg (login, password, content, user)
- `ValidationException` → 422 cez Error Handler

### Endpointy

| Metóda | Endpoint | Auth | Popis |
|---|---|---|---|
| `GET` | `/api/validation/rules` | verejný | Celý katalóg pravidiel |
| `GET` | `/api/validation/rules/{context}` | verejný | Jedna sada (login, password, content, user) |

### Frontend

- `utils/validation.ts` – zrkadlo backendového Validatora
- `validatePasswordPolicy()` – politika hesiel (zosúladená s PasswordPolicy)
- `api/validation.ts` – stiahnutie pravidiel z API

---

## 4. Testy

| Balík | Súbory |
|---|---|
| PHPUnit | `SettingsRepositoryTest`, `ValidatorTest`, `ValidationRulesTest`, `ApiErrorHandlerTest` |
| Vitest | `validation.test.ts` (validate + validatePasswordPolicy) |

Spustenie:

```bash
./vendor/bin/phpunit --filter 'SettingsRepositoryTest|ValidatorTest|ValidationRulesTest|ApiErrorHandlerTest'
cd frontend && npm test
```
