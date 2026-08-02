---
title: Editor obsahu
description: Stránky, články, drafty, médiá, SEO, konflikty a bezpečné publikovanie
icon: material/file-edit
---

# Editor obsahu — stránky a články

> Editor je klient nad API. Autoritatívny zápis vykonáva backend do flat-file SSOT; browser state alebo lokálny preview nie je uložený obsah.

## 1. Typy obsahu

| Typ | Typické polia |
|---|---|
| Stránka | title, slug, body, template/layout, status, SEO |
| Článok | stránky + excerpt, tagy, featured image, dátum a komentáre |

Blueprint alebo extension môže pridať polia. Neznáme polia musí migrácia a API spracovať deterministicky; editor ich nesmie potichu zahodiť.

## 2. Režimy editora

- **Markdown**: priama editácia textového zdroja a preview.
- **WYSIWYG/TipTap**: vizuálna editácia nad podporovanou schémou.
- **SEO/meta panel**: title, description, canonical, OG image, robots policy.

Prepínanie medzi Markdown a WYSIWYG môže byť stratové pri nepodporovanom HTML alebo extension node. Pred prepnutím zložitého dokumentu vytvor verziu alebo zálohu a skontroluj diff.

## 3. Slug a identita

Slug má byť stabilný, URL-safe a unikátny v danom type/locale. Zmena slugu môže:

- zmeniť fyzickú/logickú cestu dokumentu,
- rozbiť interné odkazy,
- vyžadovať redirect,
- zmeniť Path ACL match,
- ovplyvniť Git históriu ako rename.

Editor nemá automaticky prepisovať externé odkazy bez jasného reportu.

## 4. Draft, published a archived

| Stav | Verejný web | Admin |
|---|---|---|
| `draft` | nie, okrem autorizovaného preview | áno |
| `published` | áno podľa publish času/policy | áno |
| `archived` | nie v bežnom verejnom výstupe | áno |

Scheduled publication je doménový stav plus worker. Uložený budúci dátum bez bežiaceho schedulera nemusí obsah publikovať.

## 5. Uloženie a revision kontrola

Bezpečný save flow:

```text
auth → permission → validation → lock/revision check
→ atomický SSOT write → version/audit
→ index/cache/event → voliteľný následný job
```

Klient posiela revision/ETag podľa kontraktu. Pri stale verzii backend vráti konflikt; nesmie urobiť silent last-write-wins, ak endpoint deklaruje OCC.

## 6. Zámky a heartbeat

Otvorený dokument môže získať dočasný lock s TTL a heartbeat. Lock:

- pomáha koordinovať editorov,
- nie je náhradou revision kontroly,
- môže expirovať pri zavretom notebooku alebo strate siete,
- má byť uvoľnený pri odchode, ale backend musí zvládnuť aj TTL recovery.

Force unlock patrí privilegovanej roli a musí byť auditovaný.

## 7. Konflikt a merge

Pri konflikte porovnaj:

- base revision,
- tvoju lokálnu verziu,
- najnovšiu serverovú verziu.

Preferovaný je 3-way merge alebo explicitný výber. Pred potvrdením skontroluj front matter, media odkazy, custom nodes a status. Nikdy nekopíruj iba vizuálne telo, ak tým zahodíš metadata.

## 8. Autosave

Autosave má ukladať koncept alebo recovery snapshot, nie automaticky meniť publikovanú verziu. UI musí ukázať aspoň stavy `saving`, `saved`, `offline/error` a konflikt.

Pred zatvorením tabu pri chybe exportuj text alebo skopíruj lokálnu zmenu. Browser local storage nie je serverová záloha.

## 9. SEO a Open Graph

| Pole | Pravidlo |
|---|---|
| SEO title | stručný a relevantný; fallback môže byť content title |
| Meta description | zmysluplný súhrn bez keyword spamu |
| Canonical URL | iba dôveryhodná absolútna URL alebo prázdny auto režim |
| OG/featured image | vyber z Media Managera; over verejnú dostupnosť |
| `noindex` | používaj vedome; nie je access control |

`noindex` nezabraňuje prístupu k URL. Súkromný obsah chráň autorizáciou/ACL a nepublikuj ho.

## 10. Médiá v obsahu

Media picker má vracať kanonický identifikátor alebo podporovanú URL. Pred zmazaním média skontroluj referencie.

Do tela nevkladaj neoverený `<script>`, inline event handler alebo `javascript:` URL. HTML/Markdown renderer musí sanitizovať výstup podľa policy.

## 11. Featured a OG obrázok

Článok môže mapovať jednu media cestu na `featuredImage`, `ogImage` alebo front matter `seoImage` podľa prechodného API kontraktu. Dokumentáciu konkrétneho buildu považuj za rozhodujúcu; editor nemá vytvárať tri navzájom odlišné hodnoty bez jasného dôvodu.

Pri 404 skontroluj uloženie po výbere, media route/proxy, public path a cache.

## 12. Preview

Preview môže byť:

- staff-authenticated route,
- podpísaný krátkodobý link,
- lokálny renderer v admin aplikácii.

Preview link nesmie byť trvalý verejný bypass draft policy. Nezdieľaj session URL alebo token v issue/screenshot.

## 13. Bulk operácie

Pred bulk publish/archive/delete:

1. over filter a total count,
2. skontroluj rolu a Path ACL každej položky,
3. rozhodni, či je operácia atomická alebo či vracia per-item výsledok,
4. pri partial failure neopakuj úspešné položky naslepo,
5. over audit.

## 14. Lokalizácia

Dnešný admin jazyk a budúci viacjazyčný content model sú odlišné. Cieľová It.73 používa locale-aware identity a väzby variantov. Preklad z It.76/77 má vytvoriť návrh a diff; **Apply** a **Publish** zostávajú samostatné autorizované kroky.

## 15. Git publish a headless výstup

Save do SSOT je lokálny úspech. Git publish podľa It.70 má vlastný stav a môže zlyhať po úspešnom uložení. Editor musí ukázať rozdiel medzi `stored`, `pending_publish`, `pushed` a `publish_failed` bez straty lokálneho obsahu.

## 16. Diagnostika

| Symptóm | Kontrola |
|---|---|
| Save 409 | revision, lock, otvorená druhá session |
| Save 422 | field validation, slug, blueprint, HTML policy |
| Save 403 | permission alebo Path ACL |
| Preview je iný než public | draft session, cache, build/theme rozdiel |
| Obrázok je v admin preview, nie na webe | public media route/proxy a uložená cesta |
| Zmeny zmizli po prepnutí režimu | nepodporovaná konverzia Markdown ↔ WYSIWYG |
| Publish čaká | worker/scheduler alebo Git job stav |

## 17. Súvisiace dokumenty

- [Content API](../architecture/CONTENT_API.md)
- [API kontrakt](../architecture/API_CONTRACT.md)
- [Verzovanie](../architecture/VERSIONING.md)
- [Médiá a storage](../architecture/STORAGE.md)
- [Oprávnenia](ACCESS_CONTROL.md)
