# No-SQL mandát — nemenné pravidlo projektu

> **Stav:** kanonické pravidlo  
> **Platnosť:** všetky iterácie, režimy nasadenia a budúce „enterprise-ready“ funkcie  
> **Nadradený princíp:** súbory zostávajú primárnym zdrojom pravdy PaginiumCMS

---

## Pravidlo

PaginiumCMS **NESMIE** používať SQL databázu ani externú dokumentovú databázu, napríklad MySQL, PostgreSQL alebo MongoDB, ako **primárny zdroj pravdy** pre:

- obsah CMS,
- konfiguráciu,
- používateľov a oprávnenia,
- bezpečnostný stav,
- prevádzkové dáta, ktoré patria do súborového modelu projektu.

Primárne dáta musia zostať uložené v prenositeľných súboroch, ktoré možno zálohovať, verzovať, auditovať a obnoviť bez povinnej databázovej služby.

### Povolené formy perzistencie

| Kategória | Formát | Typické umiestnenie |
|-----------|--------|---------------------|
| Obsah | Markdown s YAML front matter, JSON dokumenty | `storage/app/content/pages/`, `blog/`, … |
| Metadáta a nastavenia | JSON | `data/settings.json`, `data/index/*.json` |
| Používatelia a bezpečnostný stav | JSON so šifrovanými citlivými poľami | `data/users/`, auditné úložiská |
| Binárne médiá | Súbory na disku alebo objektové úložisko | `media/`, prípadne S3 cez ovládač |
| Odvodené indexy | JSON vytvorený zo zdrojových súborov | `data/index/content.json` |
| Cache | Súbor, pamäť, APCu alebo Redis | **Iba odvodená vrstva — nikdy SSOT** |

**Redis, APCu a in-memory vrstvy** sú povolené iba ako:

- cache,
- dočasná koordinácia,
- rate limiting,
- voliteľné session úložisko pri horizontálnom škálovaní,
- front úloh,
- zámky alebo krátkodobé prevádzkové počítadlá.

Ich obsah musí byť možné zahodiť a obnoviť bez straty primárnych CMS dát.

---

## Zdroj pravdy verzus odvodené vrstvy

| Vrstva | Môže obsahovať jedinečné primárne dáta? | Musí byť obnoviteľná? |
|--------|------------------------------------------|-----------------------|
| JSON / Markdown / YAML dokumenty | ✅ Áno | Zo zálohy alebo Git histórie |
| Súborové metadáta používateľov a nastavení | ✅ Áno | Zo zálohy |
| Index | ❌ Nie | ✅ Zo zdrojových dokumentov |
| Cache | ❌ Nie | ✅ Zo zdrojových dokumentov alebo API |
| Redis queue | Iba dočasný stav spracovania | ✅ Úlohy musia mať bezpečný retry alebo perzistentný súborový záznam |
| Git remote | Distribučná alebo záložná kópia podľa režimu | Nesmie byť jedinou nezdokumentovanou autoritou |
| Objektové úložisko médií | Môže byť autoritou pre binárne súbory | Metadáta a väzby zostávajú v súborovom modeli CMS |

---

## Čo toto pravidlo nezakazuje

- **Indexové súbory**, napríklad `content.json` alebo agregované používateľské prehľady, ak sa vytvárajú zo zdrojových dokumentov.
- **Git ako distribučný a verzovací mechanizmus**, ak súborový kontrakt zostáva zachovaný.
- **Objektové úložisko S3 alebo kompatibilný backend** pre binárne médiá cez ovládač.
- **Externé API**, napríklad GitHub, webhooky, SMTP, ntfy alebo prekladové služby, ak nepreberú úlohu primárneho CMS úložiska.
- **Redis cache**, rate limiting, front úloh alebo dočasnú koordináciu.
- **Voliteľné integračné moduly**, ktoré exportujú alebo synchronizujú dáta do databázy tretej strany, ak jadro zostáva plne funkčné bez nich.

---

## Zakázané architektonické skratky

Nasledujúce návrhy porušujú mandát:

- uloženie obsahu iba do SQL tabuľky a vytváranie Markdown súborov až pri exporte,
- povinný MongoDB alebo Elasticsearch backend pre čítanie bežného obsahu,
- používateľské účty existujúce iba v Redis alebo externej databáze bez súborového autoritatívneho záznamu,
- cache, z ktorej nemožno bezpečne prejsť späť na súborové dáta,
- funkcia jadra, ktorá prestane pracovať po odpojení povinnej databázovej služby,
- migrácia, ktorá odstráni súborový model bez výslovného rozhodnutia projektu.

---

## Kontrolná brána návrhu

Návrh, ktorý zavádza SQL alebo povinnú externú databázu pre dáta jadra, sa musí **zamietnuť**, pokiaľ nie sú splnené všetky podmienky:

1. ide o jasne označenú **voliteľnú integráciu tretej strany**, nie o povinnú súčasť Core,
2. súborový zdroj pravdy zostáva predvolený, úplný a zdokumentovaný,
3. jadro, administrácia a API zostanú použiteľné bez integrácie,
4. export alebo synchronizácia má definované konflikty, retry a obnovu,
5. návrh prejde architektonickou a bezpečnostnou revíziou,
6. zmena `PHILOSOPHY.md` alebo tohto mandátu nastane iba explicitným projektovým rozhodnutím, nie vedľajším efektom implementácie.

---

## Testovateľné požiadavky

Implementácia rešpektuje No-SQL mandát iba vtedy, keď platí:

- po vymazaní cache možno systém obnoviť zo súborov,
- index možno kompletne prebudovať,
- záloha primárnych súborov obsahuje všetky potrebné CMS dáta,
- lokálny klasický režim nevyžaduje Redis ani databázu,
- nové ovládače nemenia doménový kontrakt dokumentov,
- diagnostika vie rozlíšiť poškodený zdrojový dokument od neaktuálneho indexu alebo cache.

---

## Súvisiace dokumenty

- [HYBRID_ENGINE.md](./HYBRID_ENGINE.md) — vrstvená architektúra nad súborovým SSOT
- [DEPLOYMENT_MODES.md](./DEPLOYMENT_MODES.md) — klasický, hybridný a Git-headless režim
- [STORAGE.md](./STORAGE.md) — fyzická štruktúra úložiska
- [../PHILOSOPHY.md](../PHILOSOPHY.md) — filozofia a rozhodovací rámec projektu
