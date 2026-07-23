# Public Beta 1 — PaginiumCMS

> **Release:** `v2.1.0-beta.2` (Beta 1 Testing) · **Tag:** `v2.1.0-beta.2`  
> **Dátum:** 2026-07-23 · **Stav:** verejná beta pre testerov a early adopters

> Použi **`v2.1.0-beta.2`** — obsahuje pre-push security gate (audit CSV sanitization, ISS-077).  
> `v2.1.0-beta.1` je len dokumentačný Wave 7 tag bez tohto patchu.

---

## Čo je v Beta 1

Flat-file CMS s admin SPA (React) a PHP 8.5 API — **bez SQL databázy**.

| Oblasť | Stav v Beta 1 |
|--------|----------------|
| Auth (session, 2FA, password confirm) | ✅ |
| Content (pages, articles, MD/WYSIWYG, SEO) | ✅ |
| Media DAM, navigácia, komentáre | ✅ |
| Admin i18n SK/EN + verejný web i18n | ✅ |
| Scheduled publish (It.59) | ✅ · vyžaduje cron |
| Zálohy, koš, WAF, logy, audit | ✅ |
| External plugins + hook emitters | ✅ |
| Path ACL, branding | ✅ |
| Pre-beta security gate (beta.2) | ✅ audit CSV + ISS-077 |

---

## Rýchly štart pre testera

1. [INSTALLATION.md](user/INSTALLATION.md) — Docker alebo hosting  
2. `./scripts/first-run.sh` — admin + storage + diagnose  
3. [FIRST_STEPS.md](user/FIRST_STEPS.md) — login, 2FA, prvý obsah  
4. [user/README.md § Beta checklist](user/README.md#beta-test--rýchky-checklist) — smoke test  

Maintainer: [developer/BETA_INFRA.md](developer/BETA_INFRA.md) · Cron: [deploy/CRON.md](deploy/CRON.md)

---

## Security review (pre pokročilých / audítorov)

Ak reviewuješ bezpečnosť (napr. ako beta tester s cybersec skúsenosťami):

| Dokument | Účel |
|----------|------|
| **[SECURITY_REVIEW.md](SECURITY_REVIEW.md)** | Attack surface, endpointy, test checklist |
| [developer/SECURITY.md](developer/SECURITY.md) | Architektúra — CSRF, WAF, encryption, pluginy |
| [SECURITY.md](../SECURITY.md) (root) | Reporting policy, supported versions |
| [ISSUES.md](ISSUES.md) | Verejný incident log (ISS-001–077) |

**Odporúčaný tag:** `git checkout v2.1.0-beta.2`

---

## Známe limitácie (nie sú bugy Beta 1)

Tieto funkcie **nie sú** v scope Public Beta 1 — plánované post-beta:

| It. | Funkcia |
|-----|---------|
| 56 | Rich navigation (ikony, mega menu) |
| 57 | Auto tags & meta description |
| 58 | Page layout builder |
| 60 | Vlastné MD/WYSIWYG komponenty |
| 61 | Footer newsletter |
| 25 | Setup wizard (first-run + FIRST_STEPS stačia) |
| 16 | Plné CMS témy + Code Editor file tree |

Ops mimo kódu (dokumentované, nie blocker pre lokálny test):

| Téma | Poznámka |
|------|----------|
| HTTPS | ISS-008 — na produkcii povinné |
| Cron na serveri | Scheduled publish / backup bez `scheduler:run` nebežia |
| Setup wizard | Manuálny first-run namiesto `/setup` |
| CSP `style-src` | `unsafe-inline` kvôli React inline štýlom (S5) |

---

## Feedback

Pri hlásení problému uveď:

- Verzia (**`v2.1.0-beta.2`**) a commit/tag  
- Kroky reprodukcie  
- Očakávané vs. skutočné správanie  
- Výstup z `php backend/bin/console content:diagnose` (ak ide o obsah/API)  

**Kanál:**

- **Bežné bugy / UX:** GitHub Issues (label `beta-feedback` ak dostupné)
- **Bezpečnostné nálezy:** pozri [SECURITY.md](../SECURITY.md) — **nepublikuj** neopravené CVE ako verejný Issue

Známe opravené incidenty: [ISSUES.md](ISSUES.md).

---

## GitHub Releases

| Tag | Popis |
|-----|--------|
| `v2.1.0-beta.1` | Public Beta 1 — Wave 7 (docs, scope) |
| **`v2.1.0-beta.2`** | **Beta 1 Testing** — odporúčané pre testerov a security review |

---

## Po Beta 1

Plánované vlny: It.56–61, setup wizard (It.25), Redis cache (It.49), server metrics (It.46).

Roadmap: [ROADMAP.md](ROADMAP.md) · Pokračovanie vývoja: [CONTINUATION.md](CONTINUATION.md)
