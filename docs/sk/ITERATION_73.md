# Iterácia 73 — viacjazyčný content dokument

> **Stav:** ✅ Hotové v `[Unreleased]` (fázy 1–2e) · beta tag pending  
> **Priorita:** 🟡 · vysoký migračný dopad  
> **Vlna:** [Hybrid Engine HE-6](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Závisí od:** [It.68](ITERATION_68.md)

## Cieľ

Podporiť viac jazykových variantov jedného content resource bez vytvárania nesúvisiacich kópií a druhého workflow. It.73 mení **model obsahu**, nie preklady admin UI.

Legacy single-locale dokumenty zostávajú čitateľné. Migrácia musí byť voliteľná, zálohovaná a vratná.

---

## Kanonický model

Návrh JSON dokumentu:

```json
{
  "schemaVersion": 2,
  "slug": "about-us",
  "type": "page",
  "defaultLocale": "sk",
  "localizedContent": {
    "sk": {
      "title": "O nás",
      "body": "…",
      "seo": { "title": "O nás", "description": "…" }
    },
    "en": {
      "title": "About us",
      "body": "…",
      "seo": { "title": "About us", "description": "…" }
    }
  },
  "localeStatus": { "sk": "published", "en": "draft" },
  "revision": 3,
  "updatedAt": "2026-08-02T10:00:00+00:00"
}
```

Presné názvy sa uzamknú v API/schema dokumentácii pred kódom. Jeden resource má jednu identitu, globálne metadata a locale-scoped obsah/status/SEO tam, kde to doména vyžaduje.

### Markdown stratégia

Pre Markdown sa musí zvoliť **jeden kanonický formát na content type**:

- bundle JSON dokument, alebo
- hlavný Markdown + verziovaný `.i18n.json` sidecar s jednoznačnou ownership schémou.

Systém nesmie udržiavať dve autoritatívne telá toho istého locale. Sidecar kontrakt musí presne povedať, ktoré polia patria do Markdown a ktoré do JSON.

---

## Locale resolution

Verejné API používa deterministické poradie:

1. explicitný `?locale=` alebo route locale,
2. podporovaný `Accept-Language`, ak endpoint túto voľbu povoľuje,
3. `defaultLocale` resource/site,
4. prvé dostupné locale na resource, ak `content.localeFallbackEnabled` je `true` (default),
5. inak `404`/jasný no-translation stav.

Odpoveď uvádza výsledný locale a či bol použitý fallback. Cache key a `Vary` z It.69 musia locale zahrnúť.

---

## Backend

| Komponent | Zodpovednosť |
|-----------|--------------|
| `LocalizedContentNormalizer` | legacy → canonical read model bez okamžitej mutácie |
| `LocalizedContentValidator` | schema a publish pravidlá per locale |
| `ContentRepository` | atomický read/write celého resource a revision check |
| `ContentIndexService` | `locales`, publish status a locale facets |
| `LocaleResolver` | jednotná fallback policy pre API a render |
| migration CLI | inventory, dry-run, backup, convert, verify, rollback |
| events | locale-aware content saved/published/translated udalosti |

Jedna `revision` chráni celý dokument v MVP. Ak reálne kolízie prekladateľov ukážu potrebu, per-locale revisions sú samostatné rozhodnutie; nesmú sa pridať bez OCC/API návrhu.

---

## Validácia a publish

- prázdny draft locale je povolený,
- locale označený `published` musí spĺňať povinné polia a SEO policy,
- publish jedného locale nemusí publikovať ostatné,
- slug/identity je globálna; locale-specific slug je mimo MVP alebo vyžaduje samostatný routing kontrakt,
- nepodporovaný locale key → `422`,
- prekladový/AI návrh nesmie sám zmeniť `localeStatus` na `published`.

---

## Frontend

- locale tabs alebo split view s jasným statusom,
- indikácia chýbajúcich a fallback locales,
- save/publish akcia uvádza dotknutý locale,
- konflikt `409` ukáže, že sa zmenil celý resource,
- Monaco JSON zobrazuje canonical objekt a schema errors,
- preview umožní vybrať locale bez predstierania publish stavu.

---

## Migrácia

1. read-only inventár single-locale dokumentov a detekcia existujúcich locale kópií,
2. explicitné mapovanie default locale a konfliktov slugov,
3. backup do `data/migrations/<id>/`,
4. dry-run report bez zápisu,
5. dávková konverzia s journalom,
6. index rebuild a API parity test,
7. potvrdenie pred archive/delete legacy zdrojov,
8. rollback z manifestu.

Automatické zlúčenie dvoch súborov s nejasnou identitou je zakázané. Taký prípad sa označí na manuálne rozhodnutie.

---

## Mimo rozsahu

- automatický strojový preklad (It.76/77),
- AI agent (It.75),
- locale-specific ACL,
- locale-specific slug/routing bez osobitnej špecifikácie,
- simultánne CRDT editovanie,
- odstránenie legacy read compatibility v tej istej iterácii.

---

## Testy

- legacy single-locale read → canonical normalizovaný výstup,
- round-trip SK/EN bez straty globálnych polí,
- draft empty locale povolený, published invalid locale → `422`,
- resolver a fallback matrix,
- cache/index locale separation,
- OCC konflikt pri súbežnom zápise,
- migration dry-run, resume, conflict report a rollback,
- API explicitne uvádza resolved locale/fallback,
- Classic single-locale fixture zostáva funkčná.

---

## Definition of Done

- [x] Kanonický read model a locale fallback uzamknuté pre legacy + schema v2.
- [x] Locale-scoped write path (`locale` payload → schema v2 merge, validator, writer).
- [x] Canonical schema a locale fallback sú uzamknuté v SK/EN API dokumentácii ([CONTENT_API.md](architecture/CONTENT_API.md) §15).
- [x] Legacy dokumenty fungujú bez povinnej migrácie.
- [x] Migration CLI MVP (`content:locale-migrate`: inventory, dry-run, backup, convert, verify, rollback).
- [x] Demo migrácia SK+EN prejde dry-run → convert → verify → rollback (`DemoLocaleMigrationIntegrationTest`).
- [x] Editor spravuje oba locale v jednom resource view.
- [x] Publish a cache sú locale-aware.
- [x] Preklad/AI nemôže automaticky publikovať locale (`proposalSource` guard).
- [x] No-SQL SSOT a Classic compatibility zostávajú zachované.

## Nadväzuje

[It.76 self-hosted translation](ITERATION_76.md) · [It.77 cloud translation](ITERATION_77.md) · [It.75 AI agent](ITERATION_75.md)
