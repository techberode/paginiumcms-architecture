---
title: Code Editor
description: Bezpečná práca s allow-listovanými zdrojovými súbormi cez Monaco
icon: material/code-braces
---

# Code Editor — používateľská príručka

> **Cesta:** `/code-editor`  
> **Riziko:** vysoké — upravuje sa zdrojový alebo konfiguračný súbor, nie bežný obsah.

Code Editor je kontrolovaný administračný nástroj pre skúseného správcu/vývojára. Chybný PHP alebo config súbor môže spôsobiť HTTP 500, nedostupný admin, bezpečnostný problém alebo zlyhaný boot.

---

## 1. Kedy ho nepoužiť

| Potrebuješ zmeniť | Použi |
|-------------------|-------|
| text stránky | Podstránky / Content Editor |
| článok | Články |
| menu | Navigácia |
| logo/farby | Nastavenia → Branding/Vzhľad |
| obrázky | Médiá |
| plugin enable/disable/import | Rozšírenia |
| `.env` produkcie | bezpečný serverový deployment workflow, nie browser editor |

Code Editor nie je náhrada Git-u, IDE, code review ani CI.

---

## 2. Ochranné vrstvy

```text
privilegovaná autentifikovaná session
  → 2FA/security policy
  → Developer Mode unlock
  → allow-list path resolver
  → syntax + Code Policy
  → backup
  → potvrdený zápis
  → audit/test/redeploy
```

Frontendový button nie je autorita. Backend opakuje gate, permission, canonical path a policy pri každej request operácii.

---

## 3. Predpoklady

- používateľ má potrebnú admin permission,
- 2FA je aktívna, ak sa unlock používa cez TOTP,
- Developer Mode je povolený serverom,
- session je odomknutá,
- storage pre backupy je zapisovateľný,
- existuje aktuálna overená záloha alebo Git commit.

Produkcia má mať predvolene:

```env
APP_ENV=production
APP_DEBUG=false
DEVELOPER_MODE=false
```

---

## 4. Povolené korene

Aktuálne dokumentovaný whitelist:

| Koreň | Účel |
|-------|------|
| `backend/app/Modules` | dôveryhodné interné moduly |
| `backend/app/Http/Extensions` | externé extension zdroje |
| `backend/resources/views/themes` | theme/template zdroje |
| `backend/config` | konfigurácia aplikácie |

Zakázané minimálne:

```text
backend/app/Core
backend/bootstrap
backend/vendor
storage secrets/log exports mimo podporovaného API
.env
cesta mimo project root
```

Whitelist nie je tvrdenie, že každý súbor v povolenom koreni je bezpečné meniť. `backend/config` môže ovplyvniť boot rovnako vážne ako Core.

Canonical path kontrola musí blokovať `../`, dvojité encodingy, symlink escape, null byte a case-trick na case-insensitive filesysteme.

---

## 5. Odomknutie

1. Otvor Code Editor.
2. Zadaj aktuálny TOTP kód alebo registrovaný dev token.
3. Potvrď **Odomknúť Developer Mode**.
4. Over, že UI ukazuje expirácie a metódu bez zobrazenia secretu.

Detail gate a tokenov: [DEVELOPER_MODE.md](DEVELOPER_MODE.md).

---

## 6. Strom a otvorenie súboru

Editor načíta iba existujúce súbory v povolených koreňoch. Pri otvorení:

- cesta sa znovu overí serverom,
- obsah sa načíta ako text s limitom veľkosti,
- binárne a nepodporované typy sa odmietnu,
- UI si eviduje dirty stav,
- zmena na disku mimo editora môže vytvoriť konflikt.

Cieľový kontrakt má používať revision/fingerprint a pri save odmietnuť prepis cudzej novšej verzie. Ak aktuálny endpoint OCC ešte negarantuje, pred uložením obnov súbor alebo skontroluj Git diff.

---

## 7. Uloženie

Pred zápisom:

1. skontroluj diff,
2. použi formátovanie/lint, ak je dostupné,
3. klikni **Uložiť**,
4. prečítaj confirmation,
5. backend vytvorí pre-save backup,
6. syntax a policy musia prejsť,
7. súbor sa zapíše podporovaným bezpečným postupom,
8. výsledok sa auditne zaznamená.

Policy failure typicky vráti `422` s kategóriami chýb. Neodstraňuj bezpečnostné kontroly iba preto, aby save „prešiel“.

### Nedôveryhodný extension/theme kód

Pre untrusted paths musí platiť vynútená `validateUntrusted` politika aj vtedy, keď je všeobecný Code Editor policy switch pre interný vývoj voľnejší. Import, Monaco a budúci scaffold nesmú mať paralelnú slabšiu write cestu.

---

## 8. Vytvorenie súboru

Panel **Nový súbor**:

1. vyber povolený koreň,
2. zadaj relatívnu cestu,
3. použi bezpečnú šablónu,
4. over namespace a `strict_types`,
5. ulož cez rovnakú policy pipeline.

Nevytváraj nový plugin iba jedným `Hooks.php` bez manifestu, testu a lifecycle. Code Editor wizard/scaffold nie je v dokumentácii označený ako kompletne implementovaný univerzálny authoring flow.

---

## 9. Zmazanie a obnova

### Zmazanie

- vyžaduje explicitné potvrdenie,
- pred zmazaním sa vytvorí backup,
- aktívny plugin/theme/config sa má najprv deaktivovať alebo prepnúť,
- zmazanie súboru nemusí aktualizovať plugin registry, routes alebo frontend build.

### Obnova

```http
POST /api/admin/code-editor/restore
```

Vyber backup pre presný súbor. Pred restore sa má zálohovať aktuálny stav. Po obnove spusti syntax/test/health check; staršia verzia nemusí byť kompatibilná s novším manifestom alebo config schema.

---

## 10. API rodina

| Metóda | Route | Úloha |
|--------|-------|-------|
| `GET` | `/api/admin/developer/status` | gate status |
| `POST` | `/api/admin/developer/unlock` | TOTP alebo dev token unlock |
| `POST` | `/api/admin/developer/lock` | lock session |
| `GET` | `/api/admin/code-editor/directories` | povolené korene |
| `GET` | `/api/admin/code-editor/files?directory=all` | strom/list súborov |
| `GET` | `/api/admin/code-editor/file?path=…` | načítanie obsahu |
| `POST` | `/api/admin/code-editor/save` | uloženie |
| `POST` | `/api/admin/code-editor/file` | vytvorenie |
| `DELETE` | `/api/admin/code-editor/file?path=…` | zmazanie |
| `POST` | `/api/admin/code-editor/restore` | obnova backupu |

Presný response envelope sa riadi [API_CONTRACT.md](../architecture/API_CONTRACT.md). Každá route opakuje auth, CSRF pri cookie mutácii, permission, Developer Mode a path policy.

---

## 11. Čo save neurobí automaticky

Uloženie zdrojového súboru automaticky negarantuje:

- reload PHP worker/opcache,
- rebuild Vite frontend bundle,
- registráciu plugin manifestu,
- enable pluginu,
- refresh route cache,
- Git commit/push,
- úspešný test alebo deploy.

Po zmene sa riaď deployment profilom. Frontend extension source pridaný do `frontend/src/extensions` potrebuje build/redeploy; nevstúpi magicky do existujúceho bundle.

---

## 12. Odporúčaný pracovný postup

```text
backup/Git clean
→ unlock
→ otvoriť a skontrolovať revision
→ malá zmena
→ save + policy
→ syntax/unit test
→ health/smoke test
→ build/reload podľa potreby
→ Git diff/commit
→ lock Developer Mode
```

Rob jednu malú zmenu naraz. Browser editor nie je ideálne miesto na refaktor 40 súborov — na to je lokálne IDE, vetva, testy a pull request. Staré remeslo stále platí: najprv záloha, potom odvaha. 🙂

---

## 13. Recovery pri nedostupnom CMS

Ak po save admin alebo API padne:

1. nepokračuj refreshovaním a ďalšími editmi,
2. prihlás sa na server cez bezpečný admin kanál,
3. skontroluj PHP syntax a log,
4. obnov posledný Code Editor backup alebo Git revert,
5. reštartuj worker/opcache podľa nasadenia,
6. spusti health endpoint a test gate,
7. Developer Mode nechaj vypnutý, kým sa príčina neuzavrie.

Backupy sa typicky ukladajú pod `storage/backups/code/`; presnú runtime cestu a retention over v implementácii/deployment dokumentácii.

---

## 14. Zamknutie po práci

Klikni **Zamknúť editor**. Pred lockom UI upozorní na neuložené zmeny. Lock nie je logout, ale zruší prístup ku gated operáciám pre session.

Na produkcii po dokončení znovu nastav bezpečný env profil a reštartuj backend.

---

## Súvisiace dokumenty

- [Developer Mode](DEVELOPER_MODE.md)
- [Pluginy](PLUGINS.md)
- [Extension Code Policy](../developer/EXTENSION_CODE_POLICY.md)
- [Architektúra pluginov](../architecture/PLUGINS.md)
- [Nasadenie a reverse proxy](../deploy/NGINX_API.md)
