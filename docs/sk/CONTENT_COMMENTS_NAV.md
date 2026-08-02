# Obsah, komentáre, kontakt a navigácia — funkčný kontrakt

> **Stav:** živá referenčná dokumentácia pre admin a verejný web  
> **Storage:** No-SQL súbory; nastavenia a obsah sa zapisujú cez backend služby

Tento dokument spája správanie, ktoré bolo v staršom backlogu nesprávne prezentované ako celé plánované iterácie. Základ článkov, komentárov, kontaktu a vnoreného menu je už dodaný; zostávajú iba konkrétne rozšírenia.

---

## 1. Články — zoznam, filtre a stránkovanie ✅

Admin zoznam článkov používa server-side kontrakt:

- `page`,
- `per_page`,
- textový filter,
- status/tag/date/author filtre podľa podporovaného endpointu,
- sort,
- synchronizáciu podporovaných filtrov do URL.

Podporované veľkosti stránky: **5 / 10 / 20 / 50**. Predvolená hodnota pochádza z `settings.ui.adminListPageSize`.

Verejný blog používa vlastné publikované filtre a nesmie v anonymnom scope vrátiť draft alebo private obsah.

---

## 2. Globálne nastavenia komentárov ✅

Skupina `comments` v administrácii:

| Kľúč | Typ | Význam |
|------|-----|--------|
| `enabled` | bool | globálne zapnúť komentáre |
| `requireApproval` | bool | nové komentáre čakajú na moderáciu |
| `allowGuestComments` | bool | povoliť anonymný/guest formulár |
| `maxLength` | int | maximálna dĺžka komentára |

Verejný výrez cez `GET /api/settings/public` smie obsahovať iba bezpečné hodnoty potrebné na render formulára.

---

## 3. Politika komentárov pri článku ✅

Article front matter / API:

| Pole | Typ | Význam |
|------|-----|--------|
| `commentsEnabled` | `bool` | zapnúť komentáre pre konkrétny článok |
| `commentsRequireApproval` | `bool \| null` | `null` = globálne pravidlo; bool = override |
| `commentsAllowGuests` | `bool \| null` | `null` = globálne pravidlo; bool = override |

`CommentPolicyResolver` vytvorí efektívnu politiku:

```text
effective.enabled = global.enabled AND article.commentsEnabled
effective.requireApproval = article override ?? global.requireApproval
effective.allowGuests = article override ?? global.allowGuestComments
```

Backend vynúti politiku pri `POST /api/comments`. Frontend je iba UX vrstva a nesmie byť jedinou ochranou.

---

## 4. Moderácia komentárov ✅ základ / 🟡 rozšírenia

Dodaný základ:

- admin zoznam a počty,
- approve/reject/delete podľa permissions,
- bulk operácie,
- voliteľný email OTP approval workflow,
- globálne a per-article pravidlá.

Možný zostávajúci backlog:

- jemnejšie oddelenie moderátorskej role,
- CAPTCHA/provider adapter pre guest komentáre,
- anti-spam scoring a quarantine,
- per-article notification subscriptions.

Tieto položky nesmú dostať znovu číslo It.39 bez overenia histórie; majú byť nové, jednoznačne pomenované backlog candidates.

---

## 5. Kontaktný formulár ✅

Dodaný rozsah zahŕňa:

- konfigurovateľné predvolené predmety `contact.subjects`,
- voľbu vlastného predmetu cez `contact.allowCustomSubject`,
- inbox/admin správu kontaktov,
- bezpečné verejné nastavenia,
- firemné údaje a voliteľný map embed podľa allow-list pravidiel.

Ak dátový model obsahuje prioritu, UI a backend musia používať rovnaký enum a bezpečný default. Priorita nesmie meniť autorizáciu ani obchádzať spam/rate-limit pravidlá.

---

## 6. Navigácia — strom a vnorené menu ✅

Primárne úložisko: `data/navigation.json` alebo kompatibilný storage driver po It.68.

Model používa plochý register s `parentId`; utility vytvoria strom pre admin a verejný render.

| Pravidlo | Hodnota |
|----------|---------|
| Maximálna hĺbka | 3 úrovne, pokiaľ settings/schema neurčí prísnejšie pravidlo |
| Validácia | backend pri každom uložení |
| Cykly | zakázané |
| Neexistujúci parent | zápis sa odmietne alebo bezpečne opraví podľa explicitnej migračnej politiky |
| Duplicitné ID | zakázané |

Dodaný rich navigation model môže obsahovať:

- `label`, `path`, `parentId`,
- `description`,
- `iconType` / `iconValue`,
- thumbnail/preview nastavenia,
- `publicRoute` pre špecializované moduly.

---

## 7. Admin UX navigácie ✅

- vytvorenie, editácia a odstránenie položky,
- vytvorenie submenu,
- reorder v rámci povolenej hĺbky,
- inline polia pre label/path/popis,
- media picker alebo ikona podľa typu,
- validácia pred save a čitateľná serverová chyba,
- live preview tam, kde je dostupný.

Frontend nesmie uložiť cyklickú alebo príliš hlbokú štruktúru ani vtedy, keď dôjde k manuálnej úprave payloadu; rozhodujúca je backend validácia.

---

## 8. Verejný render navigácie ✅

- desktop dropdown pre vnorené položky,
- mobilné odsadenie/accordion správanie,
- popis ako sekundárny text,
- voliteľná ikona/thumbnail,
- hover preview iba na zariadeniach, kde dáva zmysel,
- rešpektovanie `prefers-reduced-motion`,
- bezpečné interné/externé linky a nastavenie targetu.

---

## 9. API a bezpečnostné hranice

| Operácia | Požiadavka |
|----------|------------|
| `GET /api/navigation` | iba bezpečný verejný model |
| `PUT /api/admin/navigation` | session + CSRF + permission + schema validation |
| `POST /api/comments` | rate limit + content validation + efektívna politika |
| Admin moderácia | permission + CSRF + audit |
| Kontakt | rate limit, sanitizácia, size limits, generická odpoveď |
| Verejné settings | bez secrets, tokenov a interných ciest |

---

## 10. Súvisiace komponenty

| Oblasť | Typické súbory/služby |
|--------|------------------------|
| Article model | `Core/FlatFile/Models/Article.php` |
| Comment policy | `Modules/Comments/Services/CommentPolicyResolver.php` |
| Article comments panel | `frontend/src/components/backend/ArticleCommentsPanel.tsx` |
| Navigation admin | `frontend/src/components/backend/NavigationManager.tsx` |
| Tree utility | `frontend/src/utils/navigationTree.ts` |
| Public navbar | `frontend/src/components/frontend/Navbar.tsx` |
| Contact settings | settings schema + contact controller/service |

Cesty sú orientačné pre snapshot; pri refaktore sa aktualizujú v oboch jazykových vydaniach.

---

## 11. Acceptance checklist

- [ ] globálne vypnuté komentáre nemožno obísť priamym API callom,
- [ ] per-article override sa zhoduje v API aj UI,
- [ ] draft/private článok neunikne cez komentárový alebo navigačný endpoint,
- [ ] menu cyklus a 4. úroveň sú odmietnuté,
- [ ] guest formulár rešpektuje rate limit a max length,
- [ ] kontakt nevracia interné chyby ani citlivé údaje,
- [ ] SK/EN texty používajú rovnaké kľúče a rovnaký funkčný stav.
