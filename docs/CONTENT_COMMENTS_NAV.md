# Komentáre, články a navigácia (admin UX)

Dokument popisuje správanie modulov doplnených v rámci iterácií **42+** (admin zoznamy, inbox správy/komentáre) a rozšírení **per-article comments** + **nested menu**.

## Články – stránkovanie

- Zoznam **Články** (`PagesManager` s `type="articles"`) používa server-side pagination (`page`, `per_page`).
- Počet riadkov na stránku: **5 / 10 / 20 / 50** (toolbar) + predvolená hodnota z `settings.ui.adminListPageSize`.

## Komentáre – globálne nastavenia

Skupina **`comments`** v **Nastaveniach → Komentáre**:

| Kľúč | Význam |
|------|--------|
| `enabled` | Globálne zapnúť/vypnúť komentáre na webe |
| `requireApproval` | Globálne vyžadovať schválenie v administrácii |
| `allowGuestComments` | Povoliť komentáre od neprihlásených hostí |
| `maxLength` | Maximálna dĺžka textu komentára |

Verejný výrez: `GET /api/settings/public` → blok `comments` (bez citlivých údajov).

## Komentáre – nastavenia pri článku

Pri editácii článku (`MarkdownEditor` → panel **Komentáre k článku**):

| Front matter / API pole | Typ | Význam |
|-------------------------|-----|--------|
| `commentsEnabled` | `bool` | Komentáre pri tomto článku (default `true`) |
| `commentsRequireApproval` | `bool \| null` | `null` = globálne; `true/false` = prepísanie |
| `commentsAllowGuests` | `bool \| null` | `null` = globálne; `true/false` = prepísanie |

Backend: `CommentPolicyResolver` zlučuje globálne + per-article pravidlá pri `POST /api/comments`.

Frontend: `BlogRenderer` / `ArticleComments` skryjú sekciu alebo formulár podľa efektívnej politiky.

## Navigácia – viacúrovňové menu

- Úložisko: `data/navigation.json` (flat pole s `parentId`).
- Admin: **Menu** (`NavigationManager`) – inline editácia label/cesty, tlačidlo **Submenu**, max. **3 úrovne**.
- Verejný web: `Navbar` zobrazuje strom (dropdown na desktop, odsadenie na mobile).
- API: `GET /api/navigation`, `PUT /api/admin/navigation` – validácia hĺbky na backende.

## Súvisiace súbory

| Oblasť | Súbory |
|--------|--------|
| Article FM | `backend/app/Core/FlatFile/Models/Article.php` |
| Policy | `backend/app/Modules/Comments/Services/CommentPolicyResolver.php` |
| Editor panel | `frontend/src/components/backend/ArticleCommentsPanel.tsx` |
| Menu admin | `frontend/src/components/backend/NavigationManager.tsx` |
| Menu strom | `frontend/src/utils/navigationTree.ts` |
| Verejná navigácia | `frontend/src/components/frontend/Navbar.tsx` |

## Plánované (It.39)

Kontaktný formulár: pevné predmety správ + voliteľná priorita (model `ContactMessage` už pripravený).
