# Code Editor — používateľská príručka

> **Cesta v admin paneli:** `/code-editor`  
> **Úroveň rizika:** vysoká — mení sa PHP kód, nie obsah stránok  
> **Posledná aktualizácia:** júl 2026

---

## ⚠️ Dôležité upozornenie

Code Editor **nie je** nástroj na úpravu textov stránok alebo článkov. Chybný PHP súbor môže **znefunkčniť celý CMS** (HTTP 500, biela obrazovka).

| Chceš upraviť… | Použi namiesto Code Editora |
|----------------|----------------------------|
| Text podstránky | **Podstránky** → editor |
| Blogový článok | **Články** |
| Nastavenia webu | **Nastavenia** |
| Obrázky | **Médiá** |
| PHP modul, tému, konfiguráciu | Code Editor (len ak vieš, čo robíš) |

Ak Code Editor nepotrebuješ, na produkcii nastav `DEVELOPER_MODE=false` a `APP_DEBUG=false` v `.env` na PHP backend serveri.

---

## Ako to funguje (3 vrstvy ochrany)

```
1. Prihlásený admin (ADMIN / SUPER_ADMIN) + 2FA pri login
2. Developer Mode unlock (TOTP alebo dev token) — TTL 8 hodín
3. Code Editor — len povolené adresáre + policy + syntax check + záloha pred zápisom
```

**Metafora:** CMS admin je vstup do reštaurácie. Developer Mode unlock je kľúč od kuchyne. Code Editor je nôž pre šéfkuchára — hosť (bežný editor obsahu) ho nepotrebuje.

---

## Predpoklady

### 1. Dvojfaktorové overenie (2FA)

1. Admin → **Bezpečnosť účtu** (`/account/security`)
2. Naskenuj QR kód v Google Authenticator / Authy
3. Over 6-miestny kód → stav **Aktívne**

Bez aktivnej 2FA **nie je možné** odomknúť Developer Mode cez TOTP.

### 2. Konfigurácia servera (PHP backend)

Premenné musia byť na **PHP backend hoste** (napr. Docker `192.168.10.20:8080`), nie len na nginx SPA (`:8081`):

```env
DEVELOPER_MODE=true
# alebo APP_DEBUG=true
# alebo APP_ENV=development

DEV_UNLOCK_SECRET=change-me-local-dev-secret
APP_URL=http://192.168.10.26:8081
TRUSTED_PROXIES=127.0.0.1,::1,192.168.10.26
```

Po zmene `.env` reštartuj PHP/Docker. Detail: [deploy/NGINX_API.md](../deploy/NGINX_API.md).

---

## Odomknutie Code Editora

1. Otvor **Code Editor** v bočnom menu
2. Zadaj **aktuálny TOTP kód** z autentifikátora (6 číslic, obnovuje sa každých 30 s)  
   **alebo** registrovaný **dev token** (`pagdev_…`)
3. Klikni **Odomknúť Developer Mode**

### Dev token (alternatíva k TOTP)

Na serveri alebo v CI (mimo produkcie):

```bash
php backend/bin/dev-token.php --label=moj-notebook
php backend/bin/dev-token-register.php   # zaregistruje hash tokenu v CMS
```

Token vlož do poľa „Dev token“ pri unlock. Viď [DEVELOPER_MODE.md](DEVELOPER_MODE.md).

---

## Zamknutie editora (odporúčané po práci)

Po skončení úprav stlač **Zamknúť editor** (červené tlačidlo v hlavičke Code Editora).

- Zavolá sa `POST /api/admin/developer/lock`
- Session odomknutia sa zruší
- Zobrazí sa znova unlock obrazovka
- **Bez nového TOTP** nie je možné načítať ani uložiť súbory

Toto **nie je** odhlásenie z CMS — stále si prihlásený v administrácii. Ide o bezpečné „zamknutie kuchyne“.

Pri neuložených zmenách sa zobrazí potvrdenie pred zamknutím.

---

## Povolené adresáre (whitelist)

Code Editor zobrazí a dovolí upravovať **iba** tieto korene:

| Adresár | Účel |
|---------|------|
| `backend/app/Modules` | Moduly CMS |
| `backend/app/Http/Extensions` | HTTP rozšírenia |
| `backend/resources/views/themes` | Šablóny tém |
| `backend/config` | Konfiguračné súbory |

**Zakázané** (nikdy nie sú v strome): `backend/app/Core`, `backend/bootstrap`, `backend/vendor`.

Pri otvorení sa načítajú **všetky existujúce súbory** z povolených koreňov (hierarchický strom). Neexistujúce povolené adresáre sa preskočia.

---

## Bezpečnostné mechanizmy pri ukladaní

| Mechanizmus | Popis |
|-------------|--------|
| **Code Policy** | Blokuje nebezpečné PHP (napr. `eval`) |
| **Syntax check** | PHP syntax pred zápisom |
| **Záloha súboru** | Pred prepísaním → `storage/backups/code/` |
| **Potvrdenie Save** | Dialóg pred uložením |
| **Bezpečnostný banner** | Varovanie + odkazy na Podstránky / Zálohy |

---

## Obnova po chybe

1. **Admin → Zálohy** — obnov celý snapshot CMS  
2. **Zálohy súborov Code Editora** — `storage/backups/code/` (hash cesty + timestamp)  
3. **Obnova v editore** — pri otvorenom súbore zvoľ zálohu v paneli **Súbor / zálohy** a klikni **Obnoviť** (pred obnovou sa vytvorí záloha aktuálneho obsahu)  
4. **Git revert** na serveri (ak používaš git deploy)

---

## Vytvorenie a zmazanie súboru

Panel **Súbor / zálohy** (pod stromom súborov):

| Akcia | Popis |
|-------|--------|
| **Nový súbor** | Zadaj cestu v povolenom koreni (napr. `backend/app/Modules/MojModul/Service.php`). Súbor sa vytvorí s PHP šablónou. |
| **Zmazať súbor** | Vyžaduje potvrdenie. Pred zmazaním sa uloží záloha do `storage/backups/code/`. |
| **Obnoviť** | Zoznam záloh pre aktuálny súbor; obnova prepíše editor (Save ešte treba potvrdiť). |

Cesta musí byť v [whiteliste](#povolené-adresáre-whitelist). Zakázané segmenty (`Core`, `bootstrap`, `vendor`) API odmietne.

---

## API (pre integráciu)

| Metóda | Route | Poznámka |
|--------|-------|----------|
| GET | `/api/admin/developer/status` | Stav gate |
| POST | `/api/admin/developer/unlock` | `{ "totp_code": "123456" }` alebo `{ "token": "pagdev_…" }` |
| POST | `/api/admin/developer/lock` | Zamknutie editora |
| GET | `/api/admin/code-editor/directories` | Povolené korene |
| GET | `/api/admin/code-editor/files?directory=all` | Všetky súbory z whitelistu |
| GET | `/api/admin/code-editor/file?path=…` | Obsah súboru |
| POST | `/api/admin/code-editor/save` | `{ "path", "content" }` |
| POST | `/api/admin/code-editor/file` | `{ "path", "content?" }` — vytvorenie |
| DELETE | `/api/admin/code-editor/file?path=…` | zmazanie (+ záloha) |
| POST | `/api/admin/code-editor/restore` | `{ "path", "backup_file" }` |

Všetky code-editor routy vyžadujú: auth + 2FA session + **odomknutý** Developer Mode.

---

## Frontend (vývojári)

| Súbor | Úloha |
|-------|--------|
| `frontend/src/components/CodeEditor/CodeEditor.tsx` | Hlavný layout, Save, lock |
| `frontend/src/components/CodeEditor/DeveloperUnlockGate.tsx` | Unlock / lock brána |
| `frontend/src/components/CodeEditor/CodeEditorSafetyBanner.tsx` | Varovný banner |
| `frontend/src/components/CodeEditor/FileTree.tsx` | Strom povolených súborov |
| `frontend/src/components/CodeEditor/CodeEditorFileActions.tsx` | Nový / zmazať / obnova zálohy |
| `frontend/src/components/CodeEditor/MonacoCodeEditor.tsx` | Monaco editor |
| `frontend/src/api/developer.ts` | unlock / lock API |
| `frontend/src/api/codeEditor.ts` | files / save API |

---

## Vypnutie Code Editora na produkcii

```env
DEVELOPER_MODE=false
APP_DEBUG=false
APP_ENV=production
```

Reštart backendu. Unlock obrazovka zobrazí „Developer Mode nie je povolený“.

---

## Súvisiace dokumenty

- [DEVELOPER_MODE.md](DEVELOPER_MODE.md) — dev tokeny, gate, logy  
- [deploy/NGINX_API.md](../deploy/NGINX_API.md) — LAN deploy, `.env`  
- [ITERATION_14.md](../ITERATION_14.md) — Code Policy, path resolution  
- [ITERATION_16.md](../ITERATION_16.md) — Monaco, plný stack  
- [architecture/ARCHITECTURE.md](../architecture/ARCHITECTURE.md) — Developer Mode gate
