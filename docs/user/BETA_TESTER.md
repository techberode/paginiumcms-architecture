# Beta tester — stručný návod

> Public Beta 1 · tag **`v2.1.0-beta.3`** (odporúčané)  
> Security review: [SECURITY_REVIEW.md](../SECURITY_REVIEW.md)

---

## Pred začiatkom

- PHP **8.5+**, Node **22** (pre frontend dev/build)  
- Odporúčané: Docker Compose alebo `./scripts/first-run.sh`  
- **Nie** pre produkčné e-shopy s vysokou návštevnosťou — beta software  
- Clone + checkout: `git checkout v2.1.0-beta.3`

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

## Security reviewer (≈ 60 min extra)

Ak máš skúsenosti s pentestom / AppSec:

1. Prečítaj [SECURITY_REVIEW.md](../SECURITY_REVIEW.md)  
2. Spusti lokálne `./scripts/iteration-gate.sh`  
3. Prejdi **Suggested test checklist** v SECURITY_REVIEW (storage, CSRF, RBAC, plugin import)  
4. Nálezy hlás podľa [SECURITY.md](../../SECURITY.md) — nie verejný Issue pre neopravené CVE

---

## Čo netestovať ako „chybu“

Pozri [PUBLIC_BETA1.md § Známe limitácie](../PUBLIC_BETA1.md#známe-limitácie-nie-sú-bugy-beta-1).

---

## Hlásenie bugu

1. Skontroluj [ISSUES.md](../ISSUES.md) — možno už známe  
2. Otvor GitHub Issue s kroky reprodukcie  
3. Prilož verziu **`v2.1.0-beta.3`**

Ďakujeme za testovanie.
