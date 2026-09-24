# Iterácia — Site Design (platený doplnok)

> **Stav:** ⏳ plánované — produktová špecifikácia; **nie** súčasť bezplatného jadra  
> **Priorita:** 🟡 **P1 komerčné** · dopĺňa bezplatné It.58 appearance/layout  
> **Model dodania:** izolovaný **platený doplnok** (ZIP / licencovaný balík), nie merge do Core  
> **Závisí na:** [It.58](../en/ITERATION_58.md) tokeny a layout AST · [It.89](../en/ITERATION_89.md) capability broker · [PLUGINS.md](architecture/PLUGINS.md) · [PHILOSOPHY.md](PHILOSOPHY.md)  
> **Zámerne nie:** Theme Studio HTML témy ([It.88](../en/ITERATION_88.md)), Elementor-like ľubovoľný DOM, paywall nad existujúcim Core appearance

---

## Cieľ

Vizuálny **ladicí nástroj vzhľadu** pre netechnických adminov:

- hotové **farby**, **zaoblenia**, **tiene**, **hustota**, **varianty blokov**;
- **efekty na komponent** len z allow-listu (výhradne CSS);
- **live preview** na reálnych trasách (`/`, `/blog`, ukážková stránka) bez reloadu celej stránky;
- **žiadne spomalenie** CMS, keď je doplnok vypnutý.

Oficiálne open-source **jadro** funguje **rovnako** aj bez nainštalovaného doplnku.

---

## Licencia a súlad s filozofiou (audit)

Technicko-produktový audit voči `LICENSE` (MIT) a [PHILOSOPHY.md](PHILOSOPHY.md). Nie je to právne poradenstvo; komerčné podmienky doplnku patria do **vlastnej licencie doplnku**.

| Kontrola | Výsledok | Poznámka |
|----------|----------|----------|
| Licencia jadra (MIT) | ✅ Zlučiteľné | MIT povoľuje proprietárne samostatné dielo, ktoré **používa** CMS cez verejné API/hooky. Doplnok nemusí byť MIT, ak sa distribuuje oddelene. |
| „Oficiálny projekt zostáva bezplatný“ | ✅ Zlučiteľné | Site Design **nie je** paywall v OSS Core repozitári. V jadre ostávajú It.58 **farebné schémy**, **layout šablóny**, **outline/shortcodes**, **nastavenia blogu**, **branding**. |
| „Žiadna platená Pro edícia jadra“ | ✅ Zlučiteľné | Monetizácia = **voliteľný extension balík**, nie fork „PaginiumCMS Pro“. |
| „Tenké jadro a moduly“ | ✅ V súlade | Logika tunera v `Http/Extensions/site-design/` (pracovný názov), nie v `Core/`. |
| No-SQL / súbory ako SSOT | ✅ V súlade | Nastavenia doplnku v **namespaced** flat-file kľúčoch, nie nová SQL vrstva. |
| Security by design | ✅ Povinné | AuthN + `PermissionMiddleware`, CSRF, žiadne voľné CSS/HTML — len **enum preset-y**. |
| API First | ✅ Povinné | Každá akcia má REST kontrakt. |

**Konflikt (musí sa vyhnúť):**

- ❌ Presun existujúcich **Nastavenia → Appearance** alebo **blog list** za paywall.
- ❌ Patch Core súborov v komerčnom ZIP.
- ❌ Vypnutie verejného webu pri zlyhaní licencie (fail-open na Core tokeny; efekty doplnku jednoducho chýbajú).

**Záver:** **Platený izolovaný doplnok Site Design** je so filozofiou projektu v súlade, ak základ jadra zostane free a integrácia ide cez extension architektúru.

---

## Hranica produktu: free Core vs platený doplnok

| Schopnosť | Free Core (ostáva) | Site Design (platené) |
|-----------|-------------------|------------------------|
| 5 farebných schém | ✅ | Voliteľné **premium balíky presetov** |
| Light / dark / system | ✅ | Jemné posuvníky mapované na tokeny |
| Layout šablóny a builder | ✅ | **Design hub** UI nad rovnakým AST |
| Blog sidebar / stĺpce | ✅ | **Vizuálne karty** + preview `/blog` |
| Outline / shortcode bloky | ✅ | Panel **variant bloku** (allow-list `pg-*`) |
| Theme balíky / Theme Studio | ✅ pre maintainerov | **Mimo rozsahu** Site Design |
| Live preview pri nastaveniach | čiastočné | **Iframe** na reálnych trasách |
| Hover / motion na blokoch | základné utility | **Katalóg efektov** (reduced-motion) |

---

## Izolačná architektúra

```text
backend/app/Http/Extensions/site-design/     # proprietárny balík (mimo OSS Core stromu)
├── plugin.json
├── src/ …                                   # API /api/admin/site-design/*
├── routes.php
└── assets/

frontend/src/extensions/site-design/         # admin SPA chunk pri deployi
├── SiteDesignHub.tsx
└── registerAdminRoute.ts

data/plugins.json                            # enabled + licencia (operátor)
```

**Integrácia s jadrom:**

1. **Čítať** appearance/layout/content cez existujúce služby — žiadny druhý SSOT.
2. **Vypustiť** token overrides cez **nový allow-list hook** v jadre (**SD-0**, free): napr. `public.head.assets`.
3. **Admin trasa** cez extension loader (`/admin/site-design`).
4. **Vypnuté** = žiadny hook, žiadne extra CSS/JS na webe, žiadny admin chunk.

Zmeny jadra SD-0 = **minimálne extension pointy**, nie UI doplnku v Core.

---

## Kontrakt dizajnu (fail-closed)

- Presety = **enumy**, nie voľné CSS.
- Efekty = **iba CSS**, rešpekt `prefers-reduced-motion`.
- Varianty blokov = existujúce **shortcode / outline** typy (jeden AST It.58).
- Preview = **debounce** 150–300 ms.
- Uloženie = jeden zápis + invalidácia cache tagov.
- Žiadny Sandpack/Monaco v hub-e (to je [It.95](../en/ITERATION_95.md)).

---

## Live preview a výkon

- Verejný web v1: **žiadny JS doplnku**, len CSS premenné / `data-sd-*`.
- Admin: lazy-load chunku Site Design.
- API: debounce preview, save explicitne.
- Vypnutý doplnok: **O(0)** overhead.

---

## Implementačné slice-y

| ID | Vlastník | Práca |
|----|----------|-------|
| **SD-0** | **Core (free)** | Hook pre voliteľný stylesheet / `:root` tokeny |
| **SD-1** | Doplnok | Manifest, settings repo, licencia (admin), enable/disable |
| **SD-2** | Doplnok | Globálny tuner (radius, card style, accent) |
| **SD-3** | Doplnok | Blog región (preferuje existujúce `content.*` kľúče) |
| **SD-4** | Doplnok | Preview iframe + draft API |
| **SD-5** | Doplnok | Varianty blokov v page editore |
| **SD-6** | Doplnok | Premium preset packy (JSON import) |
| **SD-7** | Ops | Distribúcia, licenčný kľúč, samostatný repo |

Poradie: **SD-0 → SD-1 → SD-2 → SD-4 → SD-3 → SD-5 → SD-6**.

---

## Mimo rozsahu

- Import HTML tém / BloggyPress ZIP ako Site Design.
- Duplicitný layout AST alebo náhrada It.58 canvasu.
- React marketplace na verejnom webe.
- npm na produkčnom PHP hoste.
- Zamknutie adminu pri expirácii licencie.

---

## Súvisiace dokumenty

- [PHILOSOPHY.md](PHILOSOPHY.md)  
- [architecture/PLUGINS.md](architecture/PLUGINS.md)  
- [ITERATION_58.md](../en/ITERATION_58.md)  
- [architecture/THEMES.md](architecture/THEMES.md)  
- [ITERATION_89.md](../en/ITERATION_89.md)  

Anglická verzia špecifikácie: [../en/ITERATION_SITE_DESIGN.md](../en/ITERATION_SITE_DESIGN.md).
