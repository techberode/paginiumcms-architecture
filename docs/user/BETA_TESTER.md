# Beta tester — stručný návod

> Public Beta 1 · tag **`v2.1.0-beta.1`**

---

## Pred začiatkom

- PHP **8.5+**, Node **22** (pre frontend dev/build)  
- Odporúčané: Docker Compose alebo `./scripts/first-run.sh`  
- **Nie** pre produkčné e-shopy s vysokou návštevnosťou — beta software  

---

## Checklist (≈ 30 min)

| # | Úloha | OK? |
|---|--------|-----|
| 1 | Inštalácia — [INSTALLATION.md](INSTALLATION.md) | [ ] |
| 2 | `./scripts/first-run.sh` + login `admin@localhost` | [ ] |
| 3 | `/api/health` → 200 | [ ] |
| 4 | Dashboard načíta bez 500 | [ ] |
| 5 | Nová stránka + článok (draft → publish) | [ ] |
| 6 | Upload obrázka v Médiá + zobrazenie na webe | [ ] |
| 7 | Prepínač jazyka adminu SK ↔ EN | [ ] |
| 8 | 2FA zapnutie v Účet → Bezpečnosť | [ ] |
| 9 | (Produkcia) Cron podľa [CRON.md](../deploy/CRON.md) | [ ] |

---

## Čo netestovať ako „chybu“

Pozri [PUBLIC_BETA1.md § Známe limitácie](../PUBLIC_BETA1.md#známe-limitácie-nie-sú-bugy-beta-1).

---

## Hlásenie bugu

1. Skontroluj [ISSUES.md](../ISSUES.md) — možno už známe  
2. Otvor GitHub Issue s kroky reprodukcie  
3. Prilož verziu `v2.1.0-beta.1`  

Ďakujeme za testovanie.
