# Beta infra checklist (Wave 6)

> **Release:** 2.1.0-beta.2 · **Cieľ:** Public Beta 1 + security review readiness  
> Ops checklist na serveri: lokálny `PRIVATE_OPS_CHECKLIST.md` (gitignored).

---

## Quality gate pred tagom

Spusti **pred každým release tagom**:

```bash
composer gate                    # scripts/iteration-gate.sh
# alebo plná sada:
./scripts/run-all-tests.zsh
```

Minimálny subset (CI mirror):

```bash
composer test && composer stan
cd frontend && npm run type-check && npm run lint && npm run lint:api-barrel && npm test
```

GitHub Actions: `.github/workflows/ci.yml` — backend (PHPUnit, PHPStan, audit) + frontend (tsc, lint, barrel, Vitest, build) + Newman smoke.

---

## Onboarding path (beta tester)

| # | Krok | Dokument |
|---|------|----------|
| 1 | Clone + first-run | [LOCAL_SETUP.md](./LOCAL_SETUP.md) |
| 2 | Inštalácia / hosting | [user/INSTALLATION.md](../user/INSTALLATION.md) |
| 3 | Prvé prihlásenie + 2FA | [user/FIRST_STEPS.md](../user/FIRST_STEPS.md) |
| 4 | Beta smoke checklist | [user/README.md](../user/README.md#beta-test--rýchly-checklist) |
| 5 | Produkcia + cron | [deploy/CRON.md](../deploy/CRON.md) |

**Acceptance:** nový vývojár z clone → `./scripts/first-run.sh` → login admin → `/dashboard` bez 500.

---

## Flat-file diagnostika

Pri prázdnych zoznamoch, 500 na `/api/pages`, alebo po migrácii:

```bash
php backend/bin/console content:diagnose
php backend/bin/console content:diagnose --fix
php backend/bin/console content:diagnose --json
```

Zahrnuté v `scripts/first-run.sh` a v `./scripts/run-all-tests.zsh` (krok 11). Detail: [TESTING.md](./TESTING.md).

---

## Produkcia — cron (blocker pre It.59)

| Server | Stav | Akcia |
|--------|------|--------|
| Backend PHP (.20) | ✅ | — |
| Cron worker (.26) | ⏳ | Nastaviť `scheduler:run` + `worker:process` každú minútu |

Kompletný návod: [deploy/CRON.md](../deploy/CRON.md).

---

## Bezpečnostný baseline pre Beta 1 (C7)

| Oblasť | Stav | Poznámka |
|--------|------|----------|
| RBAC + PermissionMiddleware | ✅ It.20 | USER nemá mutácie |
| Path ACL | ✅ 2.0.52 | Nastavenia → Oprávnenia rolí |
| Encryption at-rest (settings) | ✅ 2.0.48 | `APP_KEY` povinný |
| WAF + structured logs | ✅ 2.0.26 | `/firewall`, `/logs` |
| CSRF synchronizer token | ✅ | Middleware na mutáciách |
| 2FA staff | ✅ | Produkcia vždy vyžaduje |
| Password confirm | ✅ 2.0.56 | Register + admin users |
| Audit CSV sanitization | ✅ 2.1.0-beta.2 | ISS-077 · audit trail export |
| HTTPS | ⏳ ops | ISS-008 — transport na produkcii |
| ESLint tech debt | ⏳ | ISS-011 — pod limitom CI |
| CORS na produkcii | ⏳ ops | ISS-014 — overiť `APP_ENV=production` |

Kritické ISS pre betu: **žiadne otvorené** (063–077 shipped). Zoznam: [ISSUES.md](../ISSUES.md).

**Security review balík:** [SECURITY_REVIEW.md](../SECURITY_REVIEW.md) · [SECURITY.md](../../SECURITY.md) · [developer/SECURITY.md](./SECURITY.md)

---

## Známe limitácie (post-Beta backlog)

Neblokujú Public Beta 1 — dokumentované v [CONTINUATION.md](../CONTINUATION.md):

- It.56–58 — rich navigation, auto tags, layout builder
- It.60–61 — custom editor components, footer newsletter
- It.25 — setup wizard (FIRST_STEPS + first-run stačia)

---

## Po Public Beta 1

**Shipped:** `v2.1.0-beta.2` (odporúčané) · `v2.1.0-beta.1` (Wave 7 docs) — [PUBLIC_BETA1.md](../PUBLIC_BETA1.md) · [BETA_TESTER.md](../user/BETA_TESTER.md) · [SECURITY_REVIEW.md](../SECURITY_REVIEW.md)

**Ops (mimo git):** cron na `.26` — [CRON.md](../deploy/CRON.md) · lokálny `PRIVATE_OPS_CHECKLIST.md`

**Post-beta vývoj:** It.56–61, It.25 — [CONTINUATION.md](../CONTINUATION.md)
