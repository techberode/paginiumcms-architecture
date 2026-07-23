# Prvé kroky po inštalácii

> Predpoklad: CMS beží podľa [INSTALLATION.md](INSTALLATION.md) a API odpovedá na `/api/health`.

---

## 1. Otvorenie administrácie

| Prostredie | URL adminu |
|------------|------------|
| Vite dev (`npm run dev`) | http://localhost:3025/login |
| Produkcia (nginx + dist) | https://vaša-domena.sk/login |
| Docker + profil dev | http://localhost:3025/login |

Verejný web (návštevníci): rovnaký host, cesta `/` (domov), `/blog`, `/about`, …

---

## 2. Prvé prihlásenie

1. Otvor **`/login`**
2. Zadaj email a heslo admina z `first-run` (predvolene `admin@localhost` / `Admin123!ChangeMe`)
3. Ak systém vyžaduje **2FA** (produkcia alebo staff účet):
   - pri **prvom** prihlásení ťa presmeruje na **Účet → Bezpečnosť** (`/account/security`)
   - naskenuj QR kód v autentifikátore (Google Authenticator, Aegis, …)
   - pri ďalších prihláseniach zadáš 6-miestny TOTP kód

**Vývoj:** v `.env` môžeš dočasne nastaviť `TWO_FACTOR_REQUIRED=false` a `APP_ENV=development` — na beta/produkcii to **nepoužívaj**.

---

## 3. Dashboard — čo uvidíš

Po prihlásení landuješ na **`/dashboard`** (Prehľad):

- počty stránok, článkov, médií, používateľov
- posledná aktivita (audit)
- rýchle odkazy do modulov
- pre adminov: stav disku (flat-file úložisko)

V bočnom paneli (sidebar) sú sekcie:

| Sekcia | Moduly |
|--------|--------|
| — | Prehľad, Analytika (admin) |
| **Pracovný priestor** | Stránky, Články, Médiá, Navigácia |
| **Schránka** | Komentáre, Správy (admin) |
| **Platforma** | Nastavenia, Preklady, Používatelia, Notifikácie, Plánovač, Bezpečnosť účtu |
| **Vývoj** | Code Editor, Blueprinty, Doplnky, Demo (SUPER_ADMIN) |
| **Bezpečnosť** | Firewall, Logy, Audit, Bezpečnostný audit |
| **Prevádzka** | Zálohy, Kôš, GitHub sync |

Badge pri položkách = počet záznamov (dá sa vypnúť v nastaveniach).

---

## 4. Odporúčaný postup prvého dňa

### Krok A — Nastavenia webu

**Admin → Nastavenia** (`/settings`):

| Skupina | Čo nastaviť |
|---------|-------------|
| **Všeobecné** | Názov stránky, jazyk adminu (SK/EN), registrácia |
| **Logo a favicon** | Logo + ikona prehliadača → [BRANDING.md](BRANDING.md) |
| **Prihlásenie** | Nadpis, pozadie pri login/registrácii |
| **SEO** | Indexovanie, predvolené meta |

**SUPER_ADMIN:** **Nastavenia → Bezpečnosť → Oprávnenia rolí** — RBAC a Path ACL → [ACCESS_CONTROL.md](ACCESS_CONTROL.md).

### Krok B — Prvá stránka

1. **Stránky** → **Nová stránka** (`/pages/new`)
2. Vyplň **názov**, **slug** (URL), obsah (Markdown alebo WYSIWYG)
3. Stav: **Draft** (náhľad) alebo **Published** (verejne)
4. **Uložiť**

Detail editora: [CONTENT_EDITOR.md](CONTENT_EDITOR.md).

### Krok C — Prvý článok

1. **Články** → **Nový článok** (`/articles/new`)
2. Rovnako ako stránka + **tagy**, **excerpt**, **OG obrázok**
3. Publikuj → skontroluj **`/blog`** na verejnom webe

### Krok D — Médiá

1. **Médiá** (`/media`) → nahraj obrázok (drag & drop alebo výber súboru)
2. Doplň **alt text** (SEO) — dôležité pre prístupnosť
3. Obrázok použiješ v editore cez **Vybrať z médií**

### Krok E — Navigácia

**Navigácia** (`/navigation`): zoradenie položiek menu verejného webu (Domov, Blog, O nás, …).

---

## 5. Koncepty, ktoré musíš poznať

### Flat-file = súbory na disku

Obsah nie je v databáze. Stránky sú `.md` súbory, nastavenia `settings.json`, používatelia `data/users/*.json`. Zálohuj priečinok `backend/storage/app/content/`.

### Draft vs Published

| Stav | Verejný web | Admin |
|------|-------------|-------|
| **draft** | Skrytý (okrem náhľadu pre staff) | Viditeľný |
| **published** | Viditeľný | Viditeľný |
| **archived** | Skrytý | Viditeľný v zozname |

### Zámky pri editácii

Keď otvoríš stránku/článok, systém **zamkne** záznam na tvoj účet (heartbeat). Iný editor uvidí upozornenie. Pri odchode sa zámok uvoľní (TTL cca 5 min).

### Konflikt pri simultánnom ukladaní

Ak niekto medzitým uložil inú verziu, uvidíš **Conflict resolver** — zlúčenie alebo manuálny výber verzie.

---

## 6. Ďalší obsah príručky

Kompletný popis všetkých modulov: **[ADMIN_GUIDE.md](ADMIN_GUIDE.md)**.

Rýchle odkazy:

- Editor obsahu → [CONTENT_EDITOR.md](CONTENT_EDITOR.md)
- Používatelia a roly → [ADMIN_GUIDE.md § Používatelia](ADMIN_GUIDE.md#používatelia)
- Zálohy → [ADMIN_GUIDE.md § Zálohy](ADMIN_GUIDE.md#zálohy-a-kôš)
- Firewall → [FIREWALL.md](FIREWALL.md)
