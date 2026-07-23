# Oprávnenia a Path ACL

> **Kde v admin paneli:** Nastavenia → kategória **Bezpečnosť** → **Oprávnenia rolí** (`/settings?category=security&group=accessControl`)  
> **Kto môže meniť:** iba **SUPER_ADMIN** (skupina sa ostatným rolám v UI ani API nezobrazí)

PaginiumCMS používa **dve vrstvy** autorizácie:

| Vrstva | Čo rieši | Kde sa nastavuje |
|--------|----------|------------------|
| **RBAC — oprávnenia rolí** | Globálne akcie (`content:edit`, `media:upload`, …) | Nastavenia → Oprávnenia rolí |
| **Path ACL** | Obmedzenie podľa **cesty** k flat-file obsahu | Rovnaká skupina — sekcia Path ACL |

**SUPER_ADMIN** má vždy plný prístup (Path ACL aj RBAC obíde).

---

## 1. Oprávnenia rolí (RBAC)

### Konfigurovateľné roly

| Rola | Nastaviteľné v UI | Poznámka |
|------|-------------------|----------|
| **ADMIN** | ✅ | Checkboxy oprávnení |
| **EDITOR** | ✅ | Checkboxy oprávnení |
| **USER** | ✅ | Checkboxy oprávnení |
| **SUPER_ADMIN** | ❌ | Vždy `*` — plný prístup |

### Dostupné oprávnenia

| Oprávnenie | Význam |
|------------|--------|
| `user:manage` | Správa používateľov |
| `content:manage` | Všetky akcie nad obsahom (pokrýva `content:*`) |
| `content:create` | Vytvorenie stránky/článku |
| `content:edit` | Úprava, drafty, zámky |
| `content:delete` | Mazanie obsahu |
| `content:view` | Zobrazenie (USER) |
| `media:manage` | Všetky akcie nad médiami |
| `media:upload` | Upload do knižnice |
| `media:delete` | Mazanie médií |
| `settings:manage` | Admin nastavenia |
| `logs:view` | Prehliadanie logov |
| `profile:edit` | Úprava vlastného profilu |

Mapovanie sa ukladá do `data/settings.json` (skupina `accessControl`: `permissionsAdmin`, `permissionsEditor`, `permissionsUser` ako čiarkou oddelený zoznam).

Backend: `PermissionCatalog.php` + `AuthorizationManager::reloadFromSettings()` po uložení.

### Predvolené hodnoty (ak nič neuložíš)

| Rola | Oprávnenia |
|------|------------|
| ADMIN | `user:manage`, `content:manage`, `media:manage`, `settings:manage`, `logs:view` |
| EDITOR | `content:create`, `content:edit`, `content:delete`, `media:upload`, `media:delete` |
| USER | `content:view`, `profile:edit` |

---

## 2. Path ACL (cesty obsahu)

Path ACL je **opt-in**: kým nezaškrtneš **Povoliť path ACL**, pravidlá sa nevyhodnocujú (platí len RBAC).

### Kde Path ACL platí

| Oblasť | Chránené | Typická normalizovaná cesta |
|--------|----------|----------------------------|
| Stránky | ✅ | `content/pages/{slug}` |
| Články (blog) | ✅ | `content/blog/{slug}` |
| Drafty | ✅ | rovnaká cesta ako stránka/článok |
| Médiá | ✅ | `content/media/…` (priečinok alebo súbor) |
| Nastavenia, používatelia, logy, code editor, navigácia | ❌ | Path ACL sa nevolá |

Enforcement: `ContentPathAclGuard` v `ContentController`, `DraftController`, `MediaController`.

### Syntax cesty v pravidle

Engine normalizuje cestu:

1. `\` → `/`, oreže `/`
2. Odstráni príponu (`.md`, `.json`, `.png`, …)
3. Ak nezačína `content/`, doplní prefix `content/`

**Príklady:**

| Zadáš | Po normalizácii |
|-------|-----------------|
| `pages/finance/budget.md` | `content/pages/finance/budget` |
| `content/pages/finance/*` | `content/pages/finance/*` |
| `media/private/logo.png` | `content/media/private/logo` |
| `media/team/*` | `content/media/team/*` |

### Podporované matchovanie

| Typ | Príklad | Popis |
|-----|---------|--------|
| **Presná cesta** | `content/pages/about` | Len táto položka |
| **Prefix + `*`** | `content/pages/finance/*` | Všetko pod prefixom |

**Nepodporované:** `**`, regex, `?`, hviezdička uprostred (`content/**/secret`), pravidlá mimo stromu `content/pages|blog|media`.

### Logika pravidla

1. Path ACL vypnuté → **povolené**
2. **SUPER_ADMIN** → **povolené**
3. Na cestu **nesedí žiadne** pravidlo → **povolené** (default allow)
4. Sedí pravidlo (prvé v zozname vyhrá):
   - Ak má **roly** → používateľ musí mať aspoň jednu
   - Inak ak má **permissions** → aspoň jedno oprávnenie (alebo oprávnenie akcie z API)
   - Ak roly aj permissions prázdne → **povolené**

### Správanie pri zamietnutí

| Operácia | HTTP | Poznámka |
|----------|------|----------|
| Čítanie (GET) | **404** | Skryje existenciu položky |
| Zápis (POST/PUT/DELETE) | **403** | `ACL denied for path: …` |
| Zoznamy (verejný web) | filter | Položky mimo ACL sa neukážu |

### Príklady pravidiel

**Len admin na stránky s prefixom `internal-`:**

```
Cesta:  content/pages/internal-*
Roly:   ADMIN
```

**Finančná sekcia pre editorov:**

```
Cesta:  content/pages/finance/*
Roly:   EDITOR, ADMIN
```

**Súkromný priečinok médií:**

```
Cesta:  content/media/internal/*
Roly:   ADMIN
```

**Skrytá publikovaná stránka pred verejnosťou** (anonymous GET → 404):

```
Cesta:  content/pages/acl-hidden-*
Roly:   EDITOR
```

(Verejný návštevník nemá rolu → zamietnuté.)

---

## 3. Úložisko a API

| Dáta | Súbor / endpoint |
|------|------------------|
| Oprávnenia rolí + Path ACL prepínač/JSON | `data/settings.json` → skupina `accessControl` |
| Path ACL runtime (sync) | `data/security/acl.json` (zapisuje `AccessControlSyncService` pri uložení nastavení) |
| Admin UI | `SettingsView` + `AccessControlSettingsPanel.tsx` |
| Legacy API (stále funguje) | `GET/PUT /api/admin/security/acl` — **len SUPER_ADMIN** |
| Security audit (nezávislé od ACL UI) | `GET /api/admin/security/audit`, `/audit/export` — **ADMIN** + **SUPER_ADMIN** |
| Legacy URL | `/security/acl` → presmeruje na `/settings?category=security&group=accessControl` |

**GET `/api/admin/settings`** vracia pre super admina aj `meta.permissions` a `meta.configurableRoles`.

---

## 4. Odporúčaný postup

1. Najprv nastav **globálne oprávnenia rolí** (EDITOR nemá `settings:manage`, …).
2. Path ACL zapni až keď potrebuješ **izolovať konkrétne cesty** (interné stránky, private media).
3. Pravidlá píš od **špecifickejších** k všeobecnejším — vyhráva **prvé** sediace pravidlo.
4. Po zmene otestuj ako EDITOR aj ako anonymný návštevník (404 vs 403).

---

## 5. Testy a kód

```bash
./vendor/bin/phpunit backend/tests/Modules/Security/Services/PathAclServiceTest.php \
  backend/tests/Modules/Security/Services/ContentPathAclGuardTest.php \
  backend/tests/Http/Controllers/Security/PathAclIntegrationTest.php \
  backend/tests/Modules/Security/PermissionCatalogTest.php \
  backend/tests/Modules/Security/Services/AuthorizationManagerSettingsReloadTest.php
```

| Súbor | Úloha |
|-------|--------|
| `PermissionCatalog.php` | Kanonický zoznam oprávnení + predvolené mapy |
| `AuthorizationManager.php` | RBAC + `reloadFromSettings()` |
| `PathAclService.php` | Normalizácia ciest + glob match |
| `ContentPathAclGuard.php` | Enforcement na content/media/drafts |
| `AccessControlSyncService.php` | Sync settings ↔ `acl.json` |

História: Iterácia 11 (ACL UI), ISS-055 (enforcement), post-2.0.51 (presun do Nastavení, nastaviteľné RBAC), ISS-072 (audit rout oddelené od ACL).

---

## Súvisiace dokumenty

- [ADMIN_GUIDE.md](ADMIN_GUIDE.md) — roly a admin moduly
- [architecture/SETTINGS.md](../architecture/SETTINGS.md) — engine nastavení
- [architecture/API.md](../architecture/API.md) — security endpointy
- [ISSUES.md](../ISSUES.md) — ISS-055
