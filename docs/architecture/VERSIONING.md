# PaginiumCMS – Verzovanie, koncepty a riešenie konfliktov

> Iterácia 2. Flat-File, žiadna databáza. Doplnok k `STORAGE.md` a `API.md`.

Tento dokument popisuje, ako CMS chráni obsah pred stratou a súbežným prepísaním
pomocou troch spolupracujúcich mechanizmov:

1. **Optimistické zamykanie** (revízny odtlačok) – detekcia konfliktu pri uložení.
2. **Auto-Save koncepty** – priebežné ukladanie rozpracovaného obsahu.
3. **Verzovanie + Diff** – história zmien a porovnanie verzií.

Dopĺňa pesimistické zamykanie z Iterácie 1 (`LockIndicator`, `data/locks.json`).

---

## 1. Optimistické zamykanie (ContentRevision)

**Princíp:** každý obsah má „revízny odtlačok" = `sha1(content + kanonický_json(frontMatter))`.
Kanonizácia rekurzívne zoradí kľúče, takže odtlačok je stabilný a nezávislý od poradia.

**Tok (metafora reštaurácie):**
- Hosť (React) si vyžiada jedálny lístok → dostane obsah **aj `revision`** (GET).
- Pri objednávke zmeny pošle späť `baseRevision` (PUT).
- Kuchár (PHP) porovná `baseRevision` s aktuálnym stavom na tanieri:
  - zhoda → uloží,
  - nezhoda → **409 konflikt** so serverovou verziou (medzičasom niekto zmenil recept).

```
GET  /api/pages/o-nas      → { ..., "revision": "3f1c…" }
PUT  /api/pages/o-nas      { ..., "baseRevision": "3f1c…" }
                            → 200 OK                          (ak sa nič nezmenilo)
                            → 409 + conflict.serverContent    (ak sa zmenilo)
```

**Kód:**
- `Core/FlatFile/Services/ContentRevision.php` – výpočet a porovnanie (`matches`).
- `Core/FlatFile/Exception/ContentConflictException.php` – nesie serverovú verziu, HTTP 409.
- `Http/Controllers/Content/ContentController.php` – `assertNoConflict()` pred zápisom.

Chýbajúci `baseRevision` = kontrola sa preskočí (spätná kompatibilita so starými klientmi).

---

## 2. Auto-Save koncepty (Drafts)

Rozpracovaný obsah sa každých **60 s** ukladá do `data/drafts/{type}/{slug}.json`,
oddelene od publikovaného obsahu. Po zatvorení karty/páde prehliadača ponúkne editor obnovu.

**Kód:**
- `Core/Drafts/Models/Draft.php`, `Contracts/DraftManagerInterface.php`, `Services/DraftManager.php`.
- `Http/Controllers/Content/DraftController.php` + `Http/Routes/drafts.php`.
- Frontend: `hooks/useAutoSave.ts`, `api/drafts.ts`.

**Životný cyklus:**
1. Editor sa otvorí → `GET /api/drafts/...` → ak existuje koncept, ponúkne „Obnoviť".
2. Počas písania → každých 60 s (pri zmene) `PUT /api/drafts/...`.
3. Po úspešnom publikovaní obsahu → `DELETE /api/drafts/...` (koncept už netreba).

Koncept nesie `baseRevision` – vieme tak neskôr posúdiť, či medzičasom nevznikol konflikt.

---

## 3. Verzovanie a Diff

História zmien sa ukladá do `data/versions/` (JSON). Pri každom uložení obsahu vzniká
nová verzia s voliteľnou **commit správou** (`message`) a diffom voči predchádzajúcej verzii.

**Kód:**
- `Core/Versioning/Services/EnhancedVersionManager.php` (produkčný, v DI).
- `Core/Versioning/Services/VersionManager.php` (základný; v tejto iterácii opravený `hydrate()`).
- Frontend: `api/versions.ts`, `components/versioning/DiffViewer.tsx`.

**DiffViewer** zobrazí dve verzie vedľa seba (side-by-side) s farebným zvýraznením
pridaných/odobraných/zmenených riadkov. Dáta získava z `GET /api/admin/versions/compare`.

---

## 4. Trojcestné zlúčenie (3-way merge) – Iterácia 3

Keď optimistická revízia zachytí konflikt (409), namiesto tvrdého „prepíš/zahoď" sa spustí
**3-way merge** priamo na klientovi. Potrebné tri verzie sú dostupné bez extra stavu na backende:

- **base** – obsah, ktorý používateľ pôvodne načítal (drží ho editor v `baseContent`),
- **mine** – aktuálne úpravy používateľa,
- **theirs** – serverová verzia z 409 (`conflict.serverContent`).

**Algoritmus** (`utils/merge3.ts`): riadkový diff3. Base slúži ako kotva; riadky nezmenené
v `mine` aj `theirs` sú stabilné. Medzi kotvami sa rozhodne:

| Zmenil mine | Zmenil theirs | Výsledok |
|---|---|---|
| nie | nie | pôvodné |
| áno | nie | mine |
| nie | áno | theirs |
| áno (rovnako) | áno (rovnako) | mine (== theirs) |
| áno (inak) | áno (inak) | **konflikt** → `ConflictResolver` |

- **Auto-merge:** ak nie sú konfliktné bloky, zlúči sa automaticky a rovno douloží (proti `serverRevision`).
- **Manuálne riešenie:** `ConflictResolver` pre každý konflikt ponúkne Moja / Serverová / Obe / Ručne + náhľad.

Každý zachytený konflikt sa zapíše do `data/conflicts.json` (admin: `GET /api/admin/conflicts`).

---

## 5. Ako to spolupracuje

| Vrstva | Chráni pred | Mechanizmus |
|---|---|---|
| Zámok (Iterácia 1) | súbežnou úpravou dvoch ľudí | `LockIndicator` + `data/locks.json` |
| Optimistická revízia | „tichým" prepísaním novšej verzie | `baseRevision` → 409 |
| 3-way merge (Iterácia 3) | stratou zmien pri konflikte | `merge3` + `ConflictResolver` |
| Auto-Save | stratou rozpracovaného textu | `data/drafts/` každých 60 s |
| Verzovanie | nemožnosťou vrátiť sa späť | `data/versions/` + DiffViewer |

---

## 6. Testy

| Test | Pokrýva |
|---|---|
| `tests/Core/FlatFile/Services/ContentRevisionTest.php` | determinizmus revízie, nezávislosť od poradia, detekcia zmeny |
| `tests/Core/Drafts/DraftManagerTest.php` | save/get/exists/discard, sanitizácia slugu, normalizácia typu |
| `tests/Core/Locking/LockManagerTest.php` | celý životný cyklus zámku, auto-release, bezpečnosť tokenu |
| `tests/Core/Versioning/VersionManagerTest.php` | vytvorenie/čítanie verzií (po oprave `hydrate()`) |
| `tests/Core/Conflict/ConflictLoggerTest.php` | log konfliktov: poradie, ohraničenie, clear, perzistencia |
| `src/utils/merge3.test.ts` (Vitest) | auto-merge, detekcia konfliktu, voľby mine/theirs/both, ručné riešenie |
| `src/components/versioning/ConflictResolver.test.tsx` (Vitest) | render konfliktu, voľby, onResolve/onCancel |

### Spúšťanie testov

```bash
# Backend (PHP) – z koreňa projektu
vendor/bin/phpunit
vendor/bin/phpstan analyse --level=8

# Frontend (TypeScript) – z priečinka frontend/
npm test              # vitest run (jednorazovo)
npm run test:watch    # vitest (watch režim)
npm run type-check    # tsc --noEmit
```

---

## 7. Prepojenie VersionHistory ↔ DiffViewer

`frontend/src/components/CodeEditor/VersionHistory.tsx` je prepojený na nový
`components/versioning/DiffViewer.tsx`. Opravené boli aj tri predexistujúce chyby, ktoré
komponent robili nefunkčným:

```diff
- import { useApi } from '../hooks/useApi';
- import { DiffViewer } from './DiffViewer';
+ import { useApi } from '../../hooks/useApi';
+ import { DiffViewer } from '../versioning/DiffViewer';
...
- const { get, post, del } = useApi();
+ const { get, post, delete: del } = useApi();
```

`VersionHistory` odošle `GET /api/admin/versions/compare` a výsledný `diff` odovzdá
priamo `DiffViewer` cez prop `diff`, ktorý ho vykreslí side-by-side.
