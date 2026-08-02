---
title: Správa pluginov
description: Import, aktivácia, diagnostika a bezpečné odstránenie pluginov
icon: material/puzzle
---

# Pluginy — používateľská príručka

> **Cesta v administrácii:** **Rozšírenia** (`/extensions`)  
> **Odporúčaná rola:** privilegovaný administrátor; importovaný PHP kód má vysoké riziko.

Plugin pridáva voliteľnú funkcionalitu bez ručnej úpravy Core. Import však nie je rovnaký ako nahratie obrázka: plugin môže obsahovať PHP routes a hook handlery, ktoré po aktivácii bežia v procese CMS.

---

## 1. Pred importom

- over zdroj a autora balíka,
- prečítaj `README.md` a `plugin.json`,
- skontroluj požadovanú `minCmsVersion`,
- vytvor a over zálohu,
- preferuj staging/dev profil,
- nikdy neinštaluj balík len preto, že má názov podobný známemu pluginu.

Do issue reportu ani screenshotu nevkladaj tokeny, `.env`, privátne cesty alebo celý obsah neznámeho PHP súboru so secretmi.

---

## 2. Očakávaný ZIP balík

```text
plugin-id/
├── plugin.json
├── src/
├── routes.php       # voliteľné
├── frontend/        # voliteľné; môže vyžadovať rebuild
├── assets/
└── README.md
```

ID v manifeste musí zodpovedať priečinku. ZIP nesmie obsahovať absolútne cesty, `../`, vendor tree ani pokus o zápis do Core.

---

## 3. Import

1. Otvor **Rozšírenia**.
2. Zvoľ **Importovať plugin**.
3. Vyber ZIP.
4. Skontroluj report validácie.
5. Potvrď import.

CMS kontroluje manifest, cesty, syntax a zakázané konštrukty. Úspešne importovaný plugin zostáva predvolene **vypnutý**.

### Bežné výsledky

| Výsledok | Čo znamená |
|----------|------------|
| import úspešný | súbory a register boli uložené; plugin ešte nemusí bežať |
| `422` policy violation | syntax, zakázaná funkcia, namespace alebo iné pravidlo neprešlo |
| nekompatibilná verzia | `minCmsVersion` je vyššia alebo manifest nie je podporovaný |
| kolízia ID | plugin s rovnakým ID už existuje; použi podporovaný upgrade flow |
| neplatný ZIP | poškodený archív alebo nebezpečná cesta |

Ručné rozbalenie do `backend/app/Http/Extensions` obchádza audit a import ochrany a nie je odporúčané.

---

## 4. Aktivácia

Po importe:

1. otvor detail pluginu,
2. over názov, ID, verziu a capability,
3. klikni **Povoliť**,
4. vykonaj odporúčaný smoke test,
5. skontroluj audit a aplikačné logy.

Aktivácia registruje hooky a routes deklarované v manifeste. Samotný stav „enabled“ nie je dôkaz, že každá funkcia pluginu funguje; plugin môže závisieť od konfigurácie, build kroku alebo externého providera.

### Frontendové pluginy

Ak plugin obsahuje React/TypeScript zdroj, produkčný Vite bundle ho nemusí vedieť načítať bez kontrolovaného rebuild/redeploy. Admin UI má zobraziť túto požiadavku; neskopíruj vzdialený JS do stránky cez `<script>` ako skratku.

---

## 5. Deaktivácia

Kliknutie **Zakázať**:

- odpojí hooky a extension routes pri podporovanom reload/boote,
- zachová pluginové súbory a dáta,
- umožní diagnostiku bez okamžitého odstránenia.

Deaktivuj plugin ako prvý krok, ak po jeho zapnutí vznikajú HTTP 500, spomalenie, konflikty alebo nečakané side effects.

---

## 6. Odinštalovanie

Odinštalovanie odstráni register a súbory pluginu. Pred potvrdením:

- plugin vypni,
- vytvor zálohu,
- exportuj jeho autoritatívne dáta, ak to podporuje,
- over, či obsah alebo shortcode nezostane závislý od pluginu,
- skontroluj fallback verejnej stránky.

CMS nesmie automaticky zmazať neznáme pluginové dáta bez explicitného cleanup kontraktu. „Uninstall“ preto nemusí znamenať „zmaž všetko, čo plugin kedy vytvoril“.

---

## 7. Upgrade

Public Beta nemusí mať univerzálny bezpečný upgrade pre každý plugin. Neprepisuj existujúci adresár ručne. Bezpečný postup je:

1. prečítať release notes,
2. zálohovať plugin a jeho dáta,
3. plugin vypnúť,
4. použiť podporovaný upgrade/import flow,
5. overiť manifest a migráciu,
6. aktivovať a otestovať,
7. rollbacknúť pri chybe.

Downgrade môže poškodiť novší dátový formát.

---

## 8. Diagnostika

| Symptóm | Kontrola |
|---------|----------|
| plugin sa nedá povoliť | manifest, `minCmsVersion`, zakázané hooky, PHP syntax |
| endpoint vracia 404 | plugin enabled, route deklarácia, bootstrap/reload, správna cesta |
| UI sa nezobrazilo | frontend capability, build/redeploy, browser console, route/menu contract |
| po enable HTTP 500 | plugin vypnúť, PHP log, class/namespace kolízia |
| zmeny sa neprejavili | cache/reload, worker restart, frontend build |
| import Permission denied | ownership a group-write storage adresárov; neopravovať cez `chmod 777` |

Pri podpore uveď CMS verziu, plugin ID/verziu, presnú akciu, čas, request ID a redigovaný relevantný log. Nevkladaj secrets.

---

## 9. Referenčný plugin

Repozitár obsahuje `hello-widget`. Po jeho povolení môže byť dostupný smoke endpoint:

```http
GET /api/extensions/hello-widget/ping
```

Očakávaný výsledok je úspešná odpoveď typu `pong`. Referenčný plugin slúži na overenie runtime kontraktu; nie je dôkazom, že každý externý balík je bezpečný.

---

## 10. Bezpečnostné pravidlá administrátora

- neinštaluj plugin z neovereného odkazu,
- neobchádzaj policy scanner ručným kopírovaním,
- nepovoľ plugin iba kvôli odstráneniu warningu,
- nepoužívaj rovnaký dev token na viacerých strojoch,
- po testovaní zamkni Developer Mode,
- sleduj audit, outbound traffic a nové routes,
- na produkcii drž minimálny počet enabled pluginov.

---

## Súvisiace dokumenty

- [Architektúra pluginov](../architecture/PLUGINS.md)
- [Extension Code Policy](../developer/EXTENSION_CODE_POLICY.md)
- [Code Editor](CODE_EDITOR.md)
- [Developer Mode](DEVELOPER_MODE.md)
- [Zálohy a administrácia](ADMIN_GUIDE.md)
