# PaginiumCMS – Známe incidenty a opravy

> Posledná aktualizácia: 2026-07-19 · verzia **2.0.24+**

Tento súbor eviduje produkčné / integračné problémy zistené pri testovaní, ich príčinu a stav opravy.

> **Audit report:** Súbor `AUDIT_REPORT.md` v koreni repozitára je **gitignored** a slúži len
> ako lokálny prehľad auditov (nie je verejný). Otvorené nálezy a stav opráv sleduj tu a v
> [CHANGELOG.md](../CHANGELOG.md#2024--2026-07-19).

---

## Prehľad

| ID | Symptóm | Závažnosť | Stav |
|----|---------|-----------|------|
| ISS-001 | `POST /api/debug/client-event` → 404 | Nízka (šum v konzole) | ✅ Opravené |
| ISS-002 | `GET /api/pages` → 500 na dashboarde | Vysoká | 🔍 Diagnostika + env |
| ISS-003 | „Noví používatelia“ každú chvíľu | Stredná | ✅ Hardening BE |
| ISS-004 | Hromadenie `navigation.json.backup.*` | Nízka | ✅ Retencia záloh |
| ISS-005 | Vitest worker crash / visnutie | Stredná (CI) | ✅ Opravené |
| ISS-006 | PHPStan 15 chýb | Stredná (CI) | ✅ Opravené |
| ISS-007 | Dashboard nesprávny počet používateľov | Nízka | ✅ Opravené |
| ISS-008 | HTTP heslo polia (login/users) | Info | ⏳ HTTPS v produkcii |
| ISS-009 | `/settings` crash `n.max is not a function` | Vysoká | ✅ Opravené |
| ISS-010 | Vitest stderr: `act(...)` + Router future flags | Nízka (CI šum) | ✅ Opravené (2.0.24) |
| ISS-011 | ESLint 65 warnings (`any`, hook deps) | Nízka (tech. dlh) | ⏳ Baseline 65, postupné čistenie |
| ISS-012 | CSRF middleware nezapojený (audit S3) | Stredná | ⏳ Odložené — SameSite=Lax |
| ISS-013 | ntfy bez auth — privátne topicy zlyhajú | Stredná | ⏳ It.47 |
| ISS-014 | CORS dev wildcardy pri zlej `APP_ENV` (audit S6) | Nízka | ⏳ Overiť deploy |

---

## ISS-001 – Debug client-event 404

**Symptóm:** Konzola plná `XHR POST …/api/debug/client-event [404]`, hoci FE loguje `[PaginiumCMS] event: …`.

**Príčina:** Frontend posielal udalosti pri `VITE_DEBUG=true` alebo v DEV režime, ale backend registroval trasu len keď `APP_DEBUG=true`. Pri prod builde na `:8081` → trasa neexistovala.

**Oprava:**
- `backend/app/Http/Routes/debug.php` – trasa vždy registrovaná.
- `DebugController` – pri vypnutom debug vráti **204** (no-op).
- `frontend/src/utils/debugLog.ts` – POST na backend len pri `VITE_DEBUG=true` (nie automaticky v prod).

**Overenie:** Po redeploy by mali 404 zmiznúť; pri `VITE_DEBUG=false` zostane len `console.debug`.

---

## ISS-002 – GET /api/pages → 500

**Symptóm:** Po prihlásení dashboard volá `/api/pages` a dostane **500 Internal Server Error**. Články (`/api/articles`) môžu prejsť.

**Pravdepodobné príčiny (serverové prostredie):**
1. Poškodený / chýbajúci content index (`data/index/*.json`).
2. Neplatný Markdown / front matter v `content/pages/*.md`.
3. Práva zápisu/čítania na `backend/storage/app/content/`.
4. Poškodený cache po upgrade.

**Diagnostika:**
```bash
# PHP error log (Docker)
docker compose logs php | tail -100

# Priamy test API (s cookies po login)
curl -s -b cookies.txt http://192.168.10.26:8081/api/pages | jq .

# Rebuild index (ak existuje CLI)
php backend/bin/rebuild-content-index.php
```

**Stav:** PHPUnit integračné testy `ContentControllerTest::testListPages*` prechádzajú – chyba je **env/dáta**, nie regresia v kóde. Po zistení konkrétnej výnimky doplniť stack trace do tohto záznamu.

---

## ISS-003 – Phantom / duplicitní používatelia

**Symptóm:** V admin `/users` pribúdajú záznamy (pocit „generovania každú sekundu“).

**Príčiny:**
1. **Backup súbory** v `data/users/` – `FileWriter` vytváral `*.json.backup.Ymd_His`; pri chybnom glob/scandir mohli byť načítané ako používatelia.
2. **Neplatný JSON** bez `id`/`email` – `User` konštruktor generoval nové `uniqid()` pri každom hydrate.
3. **Otvorená registrácia** (`general.allowRegistration`) – bot / opakované POST `/api/auth/register`.

**Oprava (kód):**
- `UserRepository::getAllUserFiles()` – ignoruje `*.backup.*`.
- `UserRepository::findAll()` – vyžaduje `id` + `email`, deduplikácia podľa ID.
- `FileWriter::pruneBackups()` – max. **5** záloh na súbor.

**Manuálne čistenie na serveri:**
```bash
# Náhľad backup súborov medzi používateľmi (NIE samotné účty!)
ls -la backend/storage/app/content/data/users/*.backup.* 2>/dev/null

# Odstránenie starých backupov (po backup celého priečinka!)
find backend/storage/app/content/data/users -name '*.backup.*' -delete
```

> **⚠️ Admin účet sa NEMAŽE.** Príkaz `find … '*.backup.*'` cieli len na súbory typu  
> `user_xxx.json.backup.20260718_120000`. Tvoj prihlasovací súbor `user_xxx.json` (SUPER_ADMIN / ADMIN)  
> zostáva nedotknutý. Pred mazaním vždy over: `ls …/data/users/*.json` — tieto súbory **nechaj**.

**Odporúčanie:** V administrácii vypnúť verejnú registráciu, ak nie je potrebná.

---

## ISS-004 – navigation.json.backup.*

**Symptóm:** V `data/` pribúdajú súbory `navigation.json.backup.20260718_104530`.

**Príčina:** Každý zápis cez `FileWriter::write(..., backup=true)` vytvorí timestamp backup (Navigation, users, content, …).

**Oprava:** `FileWriter::pruneBackups()` – ponechá posledných 5 backupov, staršie zmaže.

**Poznámka:** Backup ≠ nový používateľ. Súbor `navigation.json.backup.*` je normálna rotácia, nie chyba navigácie.

---

## ISS-005 – Vitest worker crash (Node 26)

**Symptóm:** `Error: Worker exited unexpectedly` pri `npm test`.

**Príčina:** Nekonečná slučka v `useBulkSelection` – `useMemo(..., [visibleIds])` pri inline poli v `renderHook`.

**Oprava:** Stabilizácia cez `visibleKey = visibleIds.join('\0')` v `frontend/src/hooks/useBulkSelection.ts`.

**Výsledok:** 28 súborov, 102 testov OK (Node 22 aj 26).

---

## ISS-006 – PHPStan level 8

**Opravené súbory:** `LoginAttemptTracker`, `SeoMetaBuilder`, `MediaFormats`, `MediaRepository`, `BulkBatchResultTest`.

---

## ISS-007 – Dashboard user count = 0

**Príčina:** `DashboardView` čítal `usersRes.data.length`, ale API vracia `{ users: User[] }`.

**Oprava:** `usersRes.data.users.length` v `DashboardView.tsx`.

---

## ISS-008 – Heslo cez HTTP (login, users, **settings**)

**Symptóm:** Prehliadač varuje: „Polia s heslami sú umiestnené na nezabezpečenej stránke (http://).“  
Vidíš to na **`/login`**, **`/users`** aj **`/settings`** – nie je to chyba PaginiumCMS.

**Prečo:** Stránka beží na `http://192.168.10.26:8081` (nginx bez TLS). Prehliadač chráni heslá pred odpočúvaním v sieti (LAN/Wi‑Fi).

**Ktoré polia to spúšťajú v Nastaveniach:**
- SMTP password (`smtp.password`)
- Telegram bot token, webhook secret
- (Rovnako prihlasovacie heslo na `/login`)

**Čo to NIE je:** Chyba validácie, bug vo formulári ani „únik“ hesla z CMS. Ide o **varovanie prehliadača**.

### Riešenie (odporúčané): HTTPS pred nginx

Tvoj deploy: `docs/deploy/nginx-paginium-test.conf` (port **8081**). Pridaj TLS:

```bash
# Self-signed cert pre LAN (192.168.10.26)
sudo mkdir -p /etc/nginx/ssl
sudo openssl req -x509 -nodes -days 825 -newkey rsa:2048 \
  -keyout /etc/nginx/ssl/paginium-test.key \
  -out /etc/nginx/ssl/paginium-test.crt \
  -subj "/CN=192.168.10.26"
```

V nginx server bloku (okrem `listen 8081`):

```nginx
listen 8443 ssl;
listen [::]:8443 ssl;
ssl_certificate     /etc/nginx/ssl/paginium-test.crt;
ssl_certificate_key /etc/nginx/ssl/paginium-test.key;
```

Potom otvor **`https://192.168.10.26:8443/settings`** – varovanie pri heslách zmizne (pri self-signed certe raz potvrdíš výnimku v prehliadači).

**Produkcia s doménou:** Let's Encrypt cez Caddy/nginx (`mail.webland.fun` alebo vlastná doména) – pozri `docs/deploy/NGINX_API.md`.

### Dočasne na izolovanom LAN (bez HTTPS)

- Varovanie môžeš **ignorovať** len ak dôveruješ celej sieti a nikto iný na Wi‑Fi neodpočúva.
- **Neodporúčané** pre verejný internet ani produkciu.

**Backend (budúce vylepšenie):** Session cookie `Secure` + `SameSite=Strict` až pri `X-Forwarded-Proto: https` – samo o sebe HTTP neopraví.

---

## ISS-009 – Settings crash: `n.max is not a function`

**Symptóm:** Po navigácii na `/settings` stránka spadne: `TypeError: n.max is not a function`.

**Príčina:** `zodFromRules.ts` volal `.max()` / `.min()` na `ZodOptional` (po `z.string().optional()`), nie na vnútorný `ZodString`.

**Oprava:** Reťazenie pravidiel na `z.string()` / `z.coerce.number()`, optional wrapper až na konci (`wrapOptional`).

**Overenie:** `npm test -- src/validation/zodFromRules.test.ts` + otvorenie `/settings` v admin.

---

## ISS-010 – Vitest stderr: `act(...)` a Router future flags

**Symptóm:** Pri `npm test` testy prechádzali, ale stderr obsahoval desiatky warningov:
`An update to … was not wrapped in act(...)` a React Router v7 future flag hints.

**Príčina:** `fireEvent` bez `waitFor` v `DeveloperUnlockGate.test.tsx` a `MediaManager.test.tsx`;
`MemoryRouter` bez `future={{ v7_startTransition, v7_relativeSplatPath }}`.

**Oprava (2.0.24, `b9a740f`):**
- `frontend/src/test/renderWithRouter.tsx` — spoločný wrapper s future flags.
- Testy prepísané na `fastUser` (`userEvent`) + `waitFor`.

**Overenie:** `npm test` — 130 testov OK, stderr čistý.

---

## ISS-011 – ESLint 65 warnings (technický dlh)

**Symptóm:** `npm run lint` — 0 errors, **65 warnings** (hlavne `no-explicit-any` v `client.ts` /
`useApi.ts`, `react-hooks/exhaustive-deps`, `react-refresh/only-export-components`).

**Stav:** Baseline zmrazený `--max-warnings 65` v CI — nové warningy spôsobia fail.

**Plán:** Postupné znižovanie od API vrstvy; cieľ ≤50 v ďalšej iterácii.

---

## ISS-012 – CSRF middleware (audit nález S3)

**Symptóm:** `CsrfProtectionManager` existuje, ale mutačné routy nemajú globálny CSRF middleware.

**Mitigácia dnes:** Session cookie `SameSite=Lax`; token endpoint pre SPA.

**Prečo odložené:** Aktuálny token je single-use — globálne vynútenie rozbije SPA a PHPUnit flow.

**Plán:** Koordinovaný rollout (FE token refresh + BE middleware + testy) — samostatná iterácia.

---

## ISS-013 – ntfy bez autentifikácie (privátne topicy)

**Symptóm:** `NtfyAdapter` posiela POST bez `Authorization` — zlyhá na ACL topic / self-hosted ntfy.

**Stav:** Verejný topic na `ntfy.sh` funguje; privátne nie.

**Plán:** [It.47](ITERATION_47.md) — `ntfyAuthMode`, Bearer token, Basic auth, test per konektor.

---

## ISS-014 – CORS dev wildcardy (audit nález S6)

**Symptóm:** Mimo produkcie CORS povoľuje `localhost:*`, `192.168.*`, `10.*`, `172.*` s `credentials: true`.

**Riziko:** Ak server beží s `APP_ENV` ≠ `production`, širšie CORS ostáva aktívne.

**Odporúčanie:** Pri nasadení na `mail.webland.fun` explicitne `APP_ENV=production`; overiť v health/deploy checkliste.

---

## Externé / irelevantné hlášky

| Hláška | Zdroj |
|--------|--------|
| `Failed to get subsystem status` / `content-script.js` | Rozšírenie prehliadača, nie PaginiumCMS |
| `GET /api/auth/me` → 401 pred loginom | Očakávané správanie |

---

## Súvisiace dokumenty

- [CHANGELOG.md](../CHANGELOG.md) — release 2.0.24 (post-audit hardening + QA)
- [TESTING.md](developer/TESTING.md) – ako spúšťať testy a regresiu
- [ROADMAP.md](ROADMAP.md) – plánované iterácie (It.41+, It.47–49)
- [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) – It.29+ detail
- [ITERATION_47.md](ITERATION_47.md) – notification connector auth (ISS-013)
