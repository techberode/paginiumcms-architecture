# PaginiumCMS – Flat-File úložisko

> Žiadna SQL/NoSQL databáza. Všetok stav je v súboroch pod koreňom obsahu.

## Koreň úložiska

Základná cesta je nastavená v `backend/bootstrap/app.php`:

```
backend/storage/app/content/
```

Prístup ide vždy cez `FileValidator` (ochrana proti path traversal), `FileReader` a `FileWriter`.

## Rozloženie na disku

```text
backend/storage/app/content/
├── pages/            # *.md stránky (Markdown + YAML front matter)
├── blog/             # *.md články
├── media/            # nahrané assety + registry.json
├── trash/            # kôš (mäkké mazanie)
└── data/
    ├── users/        # user_*.json
    ├── versions/     # verzie obsahu (JSON)
    ├── drafts/       # auto-save koncepty (Iterácia 2)
    │   ├── page/     #   {slug}.json
    │   └── article/  #   {slug}.json
    ├── conflicts.json # log konfliktov obsahu (Iterácia 3)
    ├── settings.json  # odchýlky od predvolených nastavení CMS (Iterácia 4)
    └── locks.json    # register zámkov (Iterácia 1)
```

## Register zámkov – `data/locks.json`

Zavedený v Iterácii 1 (systém zamykania obsahu). Nahrádza „in-memory" stav, ktorý
v bezstavovom PHP (Slim/PHP-FPM) medzi požiadavkami neprežije.

**Formát:** pole objektov zámkov.

```json
[
  {
    "resourceId": "page:o-nas",
    "lockedBy": "user_6630f1a2b3c4d",
    "lockedByName": "Ján Novák",
    "token": "9f2c…(48 hex znakov)",
    "acquiredAt": 1752400000,
    "lastHeartbeat": 1752400120,
    "expiresAt": 1752400420
  }
]
```

**Vlastnosti:**

| Pole | Význam |
|---|---|
| `resourceId` | identifikátor zdroja, napr. `page:{slug}`, `article:{slug}`, `media:{path}` |
| `lockedBy` | ID vlastníka zámku |
| `lockedByName` | zobrazované meno (pre `LockIndicator`) |
| `token` | tajný token vlastníka – nikdy sa neposiela iným klientom |
| `acquiredAt` | čas získania (unix) |
| `lastHeartbeat` | čas posledného heartbeatu (unix) |
| `expiresAt` | `lastHeartbeat + TTL`; po prekročení sa zámok auto-uvoľní |

**Súbežnosť (race conditions):** celý cyklus „načítaj → uprav → zapíš" beží pod
`flock(LOCK_EX)` nad `locks.json` (`LockManager::withLockedRegistry`). Pri každom
prístupe sa najprv odstránia expirované zámky (auto-release, TTL = 300 s).

**Bezpečnosť tokenu:** `token` je súčasťou `toArray()` (uloženie na disk), ale
`jsonSerialize()` (API odpoveď) ho zámerne vynecháva. Klient token dostane iba raz –
pri úspešnom `acquire`.

## Koncepty (auto-save) – `data/drafts/{type}/{slug}.json`

Zavedené v Iterácii 2. Rozpracovaný obsah editora sa každých 60 s ukladá do samostatného
súboru, oddelene od publikovaného obsahu. Umožňuje obnovu po zatvorení karty/páde prehliadača.

**Formát:**

```json
{
  "type": "page",
  "slug": "o-nas",
  "title": "O nás",
  "content": "# Rozpracovaný obsah…",
  "status": "draft",
  "baseRevision": "3f1c…(sha1 publikovaného obsahu)",
  "savedBy": "user_6630f1a2b3c4d",
  "savedAt": 1752400500
}
```

| Pole | Význam |
|---|---|
| `baseRevision` | revízia publikovaného obsahu, z ktorej koncept vychádza (kontext pre konflikt) |
| `savedBy` | ID používateľa, ktorý naposledy uložil koncept |
| `savedAt` | čas posledného auto-save (unix) |

Koncept sa automaticky **zahodí** po úspešnom uložení publikovaného obsahu
(`ContentController` → `DELETE /api/drafts/...`). Slug sa pri zápise sanitizuje
(žiadny path traversal), typ sa normalizuje na `page`/`article`.

## Revízny odtlačok obsahu (optimistické zamykanie)

`ContentRevision` počíta `sha1(content + oddeľovač + kanonický_json(frontMatter))`.
Kanonizácia rekurzívne zoradí kľúče (`ksort`), takže odtlačok je nezávislý od poradia
a časových značiek. Klient dostane `revision` pri `GET` a pošle ho späť ako `baseRevision`
pri `PUT`. Ak sa odtlačok na disku medzičasom zmenil → **HTTP 409 konflikt**.

## Log konfliktov – `data/conflicts.json`

Zavedený v Iterácii 3. Pri každom 409 konflikte sa zaznamená audit stopa pre admin prehľad
(`GET /api/admin/conflicts`). Súbor je ohraničený na posledných 200 záznamov a chránený
`flock(LOCK_EX)` (rovnaký princíp ako `locks.json`).

```json
[
  {
    "resourceId": "page:o-nas",
    "userId": "user_6630f1a2b3c4d",
    "userName": "Ján Novák",
    "baseRevision": "3f1c…",
    "serverRevision": "a7b9…",
    "occurredAt": 1752400600
  }
]
```

## Nastavenia CMS – `data/settings.json` (Iterácia 4)

Flat-file úložisko **odchýlok** od predvolených hodnôt definovaných v `SettingsSchema`.
Ukladajú sa iba zmenené polia – budúce úpravy predvolieb sa prejavia bez migrácie.

**Formát:** objekt skupín → polí.

```json
{
  "general": {
    "siteName": "Moja stránka",
    "language": "en"
  },
  "content": {
    "autoSaveInterval": 120
  }
}
```

| Skupina | Príklady polí |
|---|---|
| `general` | siteName, siteUrl, adminEmail, language, timezone, maintenanceMode |
| `content` | itemsPerPage, defaultStatus, autoSaveInterval, lockTtl |
| `editor` | defaultEditor, spellcheck, tabSize |

**Súbežnosť:** `flock(LOCK_EX)` nad celým cyklom načítaj→uprav→zapíš (`SettingsRepository::withLockedOverrides`).

**API:** `GET/PUT /api/admin/settings/{group}` (ADMIN), `GET /api/settings/public` (AUTH – verejný výrez pre aplikáciu).
