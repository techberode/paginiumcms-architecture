---
title: Extension Code Policy
description: Záväzná fail-closed politika pre pluginy, budúce témy a nedôveryhodný authoring
icon: material/shield-code-outline
---

# PaginiumCMS Extension Code Policy

> **Status:** záväzný architektonický a bezpečnostný kontrakt pre kód mimo Core.  
> **Platí pre:** plugin ZIP import, extension source v Code Editore, referenčné pluginy, budúce theme packages, shortcode definície a ďalšie nedôveryhodné authoring cesty.

Táto politika dopĺňa coding standards, architektúru pluginov, Core hardening a API kontrakt. Automatické kontroly znižujú riziko, ale PHP extension stále nie je sandbox. Balík schválený scannerom nesie iba význam „nebol nájdený známy zakázaný pattern a spĺňa deklarovaný kontrakt“, nie „je dokázateľne bezpečný“.

---

## 1. Základné princípy

1. **Core je uzavretý pre externý zápis.**
2. **Nedôveryhodný kód sa validuje fail-closed pred uložením aj aktiváciou.**
3. **Import a enable sú oddelené udalosti.**
4. **Každá capability je explicitná v manifeste a allow-liste.**
5. **Serverová autorizácia a policy sa nespoliehajú na frontend.**
6. **Žiadny slabší alternatívny zápis cez Monaco, ZIP, scaffold alebo API.**
7. **Plugin nemá implicitnú dôveru iba preto, že ho nahral SUPER_ADMIN.**
8. **Kód, dáta, konfigurácia, secrets a odvodené artefakty majú odlišné pravidlá.**
9. **Bezpečné odmietnutie je lepšie než čiastočná inštalácia.**
10. **Dokumentovaná capability je verejný kontrakt; interná trieda nie je extension API.**

---

## 2. Trust realms

| Realm | Príklad | Dôvera | Policy |
|-------|---------|--------|--------|
| platform Core | `backend/app/Core/` | najvyššia, shipped/reviewed | project CI; externý zápis zakázaný |
| interný modul | `backend/app/Modules/` | shipped/reviewed | plný CI + architektonické pravidlá |
| externý plugin | `backend/app/Http/Extensions/{id}/` | nedôveryhodný | forced untrusted policy |
| budúca téma | `backend/resources/views/themes/{id}/` | prezentačná, stále nedôveryhodná | theme profile + asset/template policy |
| shortcode/layout definícia | `data/shortcodes`, `data/layout` alebo virtuálny buffer | nedôveryhodné dáta | schema + safe expansion policy |
| generated cache/build | cache/index/compiled HTML | odvodené | nie zdroj pravdy; rebuildable |

Názov adresára nie je jediný trust signál. Importovaný súbor sa validuje ako untrusted ešte v temp adresári alebo pod virtuálnou cestou `untrusted://…`.

---

## 3. Umiestnenie

| Typ | Backend | Routes | Frontend | Register |
|-----|---------|--------|----------|----------|
| Plugin | `backend/app/Http/Extensions/{id}/` | `backend/app/Http/Routes/extensions/{id}.php` | `frontend/src/extensions/{id}/` | `data/plugins.json` |
| Theme package | `backend/resources/views/themes/{id}/` | iba ak explicitne podporované | `frontend/src/themes/{id}/` | plánované `data/themes.json` |
| Internal module | `backend/app/Modules/{Name}/` | centrálna/registrovaná HTTP vrstva | feature-owned FE | shipped config/catalog |

Zakázané externému balíku:

```text
backend/app/Core/
backend/bootstrap/
backend/vendor/
.env
server/system paths
arbitrary data/ writes
public web root outside approved assets
```

Canonical path sa overuje po normalizácii a po resolve symlinkov. Kontrola prefixu na surovom stringu nestačí.

---

## 4. Povolené súbory a limity

Policy profil definuje allow-list prípon a maximálnu veľkosť. Typický plugin môže obsahovať:

- `.php`, `.json`, `.md`, `.txt`,
- podporované `.ts`, `.tsx`, `.css` iba pre build-managed frontend source,
- schválené obrázky/font-reference assety podľa media policy,
- test fixtures bez secretov.

Zakázané alebo osobitne reviewované:

- executable binary, shared object, PHAR,
- nested archive,
- symlink/hardlink,
- `.env`, private key, certificate private material,
- vendor tree bez schváleného dependency modelu,
- obfuskovaný/base64-packed executable payload,
- súbor prekračujúci limity,
- MIME/extension mismatch.

Fonty sa nevkladajú do dokumentačného balíka ani nezdieľajú ako „súčasť témy“ bez jasnej licencie a deployment policy.

---

## 5. Manifest policy

Minimálne polia pluginu:

| Pole | Pravidlo |
|------|----------|
| `id` | kebab-case `[a-z0-9]+(-[a-z0-9]+)*`; zhodné s adresárom |
| `name` | čitateľný názov s limitom dĺžky |
| `version` | SemVer |
| `minCmsVersion` | podporovaná SemVer constraint/hodnota |
| `hooks` | mapa iba z `HookCatalog` |
| `routes` | boolean/capability deklarácia |
| `frontend` | boolean alebo budúci verziovaný FE descriptor |

Manifest:

- používa UTF-8 JSON bez duplicate keys,
- neobsahuje secrets alebo host paths,
- neumožňuje arbitrary PHP classname mimo namespace pluginu,
- neumožňuje svojvoľný URL script/import,
- musí deklarovať `manifestVersion`, keď sa zavedie viac schém,
- pri neznámej povinnej capability sa odmietne,
- pri budúcom optional poli zachová backward-compatible ignore pravidlo iba ak je bezpečné.

ID je stabilná identita. Display name sa môže meniť; ID/path/namespace sa pri upgrade nemení bez explicitnej migrácie.

---

## 6. PHP pravidlá

Každý PHP súbor extension:

```php
<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\ExamplePlugin;
```

Povinné:

- `strict_types=1`,
- namespace zodpovedá extension ID a povolenému prefixu,
- PSR-compatible autoload/class naming podľa podporovaného loadera,
- typed parametre a návratové hodnoty vo verejnom extension API,
- žiadne side effects pri samotnom include okrem deklarovanej registrácie,
- žiadne globálne function/class názvy kolidujúce s iným pluginom,
- žiadny vlastný skrytý DI/service locator, ktorý obchádza capability contract.

### Zakázané konštrukty

Minimálny forced untrusted zoznam zahŕňa:

```text
eval
exec
shell_exec
system
passthru
proc_open
popen
pcntl_*
assert(string)
create_function
include/require s dynamickou alebo neallow-listovanou cestou
unserialize nedôveryhodných dát
call_user_func* nad nekontrolovaným vstupom
FFI
```

Scanner má používať token/AST prístup, nie iba regex. Alias, namespace funkcia, concatenation alebo escape nesmie jednoduchú kontrolu obísť. Zoznam sa môže rozširovať; policy je deny-first, nie sľub, že všetko neuvedené je automaticky povolené.

---

## 7. Filesystem a dáta

Plugin nemá generic filesystem capability. Pracuje cez namespaced storage/repository contract:

```text
extensions/{id}/settings
extensions/{id}/content/{documentId}
extensions/{id}/state/{key}
```

Zakázané:

- čítať `.env`, session files, backups iného modulu alebo server secrets,
- zapisovať do Core, bootstrap, vendor alebo iného pluginu,
- používať užívateľský path bez canonical resolvera,
- nasledovať symlink mimo povoleného rootu,
- ukladať plaintext credentials,
- zamieňať cache za autoritatívny SSOT.

Pluginové autoritatívne dáta sa zahrnú do backup/exportu alebo manifest explicitne deklaruje, že stav je odvodený a rebuildovateľný.

---

## 8. Hook policy

Plugin registruje hooky iba cez manifest. Povolený názov a payload určuje `HookCatalog`.

Handler:

```php
public static function onContentAfterSave(array $context): void
```

Pravidlá:

- neznámy hook → import/enable failure,
- classname musí patriť plugin namespace,
- payload sa považuje za read-only,
- handler nesmie spoliehať na nedokumentované keys,
- citlivé fields sa do public hook payloadu neposielajú,
- exception behavior je definovaný per hook,
- nákladný side effect sa presúva do queue/job s explicitnou identity a retry policy,
- handler nesmie autonómne publishovať obsah bez permission/policy flow.

`before_*` hook nesmie obísť server validation. `after_*` hook nemá rollbackovať už commitnutý SSOT zápis náhodnou exception bez transakčného kontraktu.

---

## 9. Route policy

Plugin route musí:

- byť deklarovaná capability,
- používať podporovaný registrar signature,
- mať explicitný public/admin status,
- mať route-level permission/scopes,
- použiť CSRF pri cookie-auth mutácii,
- validovať body/query/path,
- použiť rate limit a size limit podľa threat modelu,
- používať central response/error contract,
- auditovať citlivé mutácie,
- nepoužiť wildcard CORS alebo open redirect,
- neposielať filesystem path/stack trace v produkcii.

Public route sa neoznačí ako public iba vynechaním middleware. Public classification má byť vedomá a testovaná.

Endpoint má zodpovedajúci typovaný frontend klient alebo je explicitne označený ako headless. Raw `fetch` roztrúsený v komponentoch je porušenie API↔FE zákona.

---

## 10. Outbound sieť a provideri

Priamy `curl`/socket k ľubovoľnej URL nie je default capability. Outbound ide cez platformový client/provider contract s:

- `https` allow-listom,
- DNS/IP kontrolou proti localhost, RFC1918, link-local a metadata endpointom,
- redirect revalidáciou,
- timeoutom, response size limitom a content-type kontrolou,
- redakciou authorization headers v logoch,
- per-plugin/provider rate limitom,
- secretom uloženým v encrypted settings,
- explicitnou user/admin konfiguráciou.

Plugin nesmie používať backend ako SSRF proxy alebo tajne odosielať obsah/analytics tretej strane.

---

## 11. Frontend policy

Frontend extension source:

- používa TypeScript strict,
- exportuje cez dokumentovaný entry contract,
- používa central API client,
- rešpektuje route/slot/menu registráciu,
- používa semantic theme tokens,
- namespaceuje CSS,
- sanitizuje HTML a URL,
- neukladá bearer/admin secrets do `localStorage`,
- nepoužíva `eval`, `new Function`, inline script injection ani unverified remote bundle,
- nepatchuje Core source alebo globálne prototypes.

### Build-time hranica

`import.meta.glob` zaraďuje súbory pri builde. Import ZIP-u s `frontend/` preto musí:

- buď označiť plugin ako backend-only do ďalšieho buildu,
- alebo spustiť podporovaný, izolovaný a auditovaný build/redeploy pipeline,
- alebo v budúcnosti použiť signed prebuilt bundle s integrity/CSP/ABI kontraktom.

Nie je dovolené vyriešiť hranicu cez dynamické `eval` alebo raw remote `<script>`.

---

## 12. Theme policy

Budúca téma má užší capability profil než plugin:

- deklaratívne templates/slots/tokens/assets,
- žiadna business logika alebo priamy repository write,
- žiadny arbitrary PHP v untrusted profile,
- žiadne script URL mimo schváleného bundle modelu,
- asset MIME/size/licence kontrola,
- preview bez mutácie SSOT,
- fallback pri chýbajúcom slote/assete,
- accessibility a contrast testy.

Ak theme package obsahuje executable PHP, klasifikuje sa bezpečnostne ako plugin alebo trusted shipped theme, nie ako pasívny dizajn.

---

## 13. Shortcode, layout a AI-generated artefakty

Shortcode/layout definícia je **dáta**, nie PHP:

- schema JSON,
- allow-listované atribúty a `pg-*` triedy,
- zákaz script/iframe/event handlers,
- expand template sa sanitizuje,
- preview používa rovnaký validator ako save,
- neznámy shortcode failne kontrolovane,
- artifact sa neaktivuje pri `422`.

AI návrh, preklad alebo scaffold nemá výnimku z policy. AI môže vytvoriť proposal/diff, ale Apply vykoná autorizovaný používateľ cez rovnakú validation pipeline. AI nikdy nedostáva generic shell alebo extension superuser capability.

---

## 14. Import, aktivácia a rollback

```text
ZIP/Monaco/scaffold input
→ temp/virtual untrusted buffer
→ path + type + size
→ syntax
→ security scan
→ manifest/artifact schema
→ compatibility
→ stage files
→ registry enabled=false
→ explicit enable
→ smoke test
```

Failure pravidlá:

- pri validácii sa nič neaktivuje,
- čiastočné súbory sa odstránia alebo ostanú v izolovanom quarantine temp priestore,
- registry sa neprepíše nekonzistentným stavom,
- enable failure rollbackne runtime registráciu,
- pôvodný plugin pri zlyhanom upgrade zostane obnoviteľný,
- error report je machine-readable a redigovaný,
- každá etapa má audit event.

---

## 15. Kompatibilita a verzie

Plugin deklaruje minimálnu CMS verziu a vlastnú SemVer. Odporúčaný budúci kontrakt doplní:

- `manifestVersion`,
- podporované hook/API ABI verzie,
- required capabilities,
- optional capabilities,
- checksum/signature metadata,
- migration version.

`minCmsVersion` samotná negarantuje kompatibilitu s každou budúcou major verziou. Breaking zmena extension API vyžaduje deprecation, migration guide a contract test fixtures.

---

## 16. Secrets a konfigurácia

Plugin môže deklarovať settings schema, nie vlastný plaintext secret store.

- secret field je write-only/redacted,
- ukladá sa cez platform EncryptionService,
- public settings API ho nikdy nevystaví,
- export/backup rešpektuje encryption policy,
- logs a exception context secret rediguje,
- UI nesmie znovu zobraziť uloženú hodnotu,
- uninstall jasne deklaruje, čo sa so secretom stane.

Manifest ani committed config neobsahuje reálne credentials.

---

## 17. Testovanie

Povinné minimum podľa capability:

### Všetky extension balíky

- valid/invalid manifest fixtures,
- path traversal/Zip-Slip/symlink tests,
- forbidden function/token variants,
- oversized a MIME mismatch fixtures,
- import rollback,
- compatibility failure.

### PHP plugin

- unique namespace a autoload test,
- hook registration/payload/error policy,
- route permission/CSRF/rate-limit matrix,
- boot/enable/disable/uninstall,
- no secret leakage.

### Frontend

- entry/loader contract,
- typed API calls,
- XSS/URL sanitization,
- theme token compatibility,
- accessible keyboard behavior,
- build failure reporting.

### Theme/shortcode

- template/schema validation,
- output sanitization,
- missing slot/fallback,
- contrast/axe smoke,
- cache invalidation.

Test extension v dočasnom adresári musí používať unikátne ID a namespace. Duplicitná trieda referenčného `hello-widget` spôsobila incident [ISS-075](../ISSUES.md#iss-075); testovací fixture preto používa napríklad `ping-demo`.

---

## 18. Review checklist autora

- [ ] ID, adresár a namespace sú zhodné.
- [ ] Manifest má validnú verziu a realistickú kompatibilitu.
- [ ] Každý PHP súbor má `strict_types=1`.
- [ ] Nie sú použité zakázané funkcie, dynamický include ani obfuskácia.
- [ ] Hooky sú iba z katalógu.
- [ ] Routes majú explicitnú auth/permission/CSRF policy.
- [ ] Dáta používajú namespaced storage a backup kontrakt.
- [ ] Secrety sú v encrypted settings, nie v kóde/manifeste.
- [ ] Outbound používa SSRF-safe provider/client.
- [ ] Frontend rešpektuje build model, central API a semantic tokens.
- [ ] Testy používajú unikátny namespace.
- [ ] README popisuje konfiguráciu, riziká, upgrade a uninstall.
- [ ] Import/enable/rollback bol overený na čistej inštancii.

---

## 19. Prevádzkový checklist správcu

- [ ] Zdroj balíka je overený.
- [ ] Záloha je vytvorená a overená.
- [ ] Import prebehol bez ručného obídenia policy.
- [ ] Plugin zostal po importe disabled.
- [ ] Manifest a capabilities boli skontrolované.
- [ ] Enable prebehol najprv v staging profile.
- [ ] Health/routes/content smoke test je zelený.
- [ ] Logy, audit a outbound traffic sú bez anomálií.
- [ ] Frontend build/redeploy bol vykonaný, ak je potrebný.
- [ ] Existuje rollback postup.

---

## 20. Súvisiace dokumenty

- [Architektúra pluginov](../architecture/PLUGINS.md)
- [Architektúra tém](../architecture/THEMES.md)
- [Udalosti a hooky](../architecture/EVENTS.md)
- [Core hardening](../architecture/CORE_HARDENING.md)
- [Code Editor](../user/CODE_EDITOR.md)
- [Developer Mode](../user/DEVELOPER_MODE.md)
- [Pluginy — používateľská príručka](../user/PLUGINS.md)
