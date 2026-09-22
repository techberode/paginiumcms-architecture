# Iterácia 95 — Component playground a vlastná knižnica komponentov

> **Stav:** ✅ v strome (`v2.1.0-beta.90`); nasadenie na serveri = checklist v [EN](../en/ITERATION_95.md#production-rollout-checklist-it95-on-server).
> **EN špecifikácia:** [../en/ITERATION_95.md](../en/ITERATION_95.md)

## Zhrnutie

**CodeSandbox-obdoba** v admin-e: buď **vedľa Monaca** (Theme Studio / Code Editor), alebo samostatná playground stránka — **Sandpack** v prehliadači, nie `npm install` na PHP serveri.

V **nastaveniach**:

- zapnúť/vypnúť **balíky komponentov** (manifest, nie voľné hľadanie na npm),
- voliteľne **import knižnice z vlastného Git repozitára** (token, scan, Zip-Slip — rovnaká disciplína ako extensions).

Vlastný repozitár = buď **import ZIP/tarball** cez admin (95d), alebo **organizačné balíky** linkované pri **`npm ci` / deploy** (dokumentácia v DEPLOY), nie tiché sťahovanie za behu.

## Slicey

| ID | Obsah |
|----|--------|
| **95a** | Sandpack playground + live preview |
| **95b** | ✅ Prepojenie s Monacom (otvoriť / exportovať s validáciou) |
| **95c** | Registr komponentov + prepínače v Settings |
| **95d** | ✅ Import z privátneho Git (pin ref, OutboundUrlGuard) |

## Väzby

It.88 Theme Studio · It.89 capabilities · It.90 Editor Tool SDK · It.94 toasty · bezpečnostný baseline v EN dokumente.
