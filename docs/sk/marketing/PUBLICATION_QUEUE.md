# Fronta publikácie (SK) — Unreleased → web

Články pripravené na publikovanie po **overení tagu** v [CHANGELOG.md](../../../CHANGELOG.md). Pred uploadom na paginiumcms.com zmeniť `status: draft` → `published` vo frontmatter.

| Priorita | Súbor | Téma | Publikovať keď |
|----------|--------|------|----------------|
| 1 | [ARTICLE_37_neulozeny-obsah-autosave-koncept.md](ARTICLE_37_neulozeny-obsah-autosave-koncept.md) | Autosave nového článku/stránky, banner, stôl | Unreleased editor shipped |
| 2 | [ARTICLE_38_rychlejsi-admin-cache-dashboard.md](ARTICLE_38_rychlejsi-admin-cache-dashboard.md) | P2 cache, warm-admin, gzip, It.100 | Po deployi cache + cron na prod |
| 3 | [ARTICLE_39_stol-csrf-vykon-redakcia.md](ARTICLE_39_stol-csrf-vykon-redakcia.md) | Desk perf, CSRF po login-e | Spolu s 37–38 alebo krátky follow-up |

## Nepublikovať ako „novinka“ (interné / monitor)

- **ISS-197** — npm audit Tailwind chain (monitor only).
- **Load probe** `admin-concurrency-probe.sh` — dokumentácia pre prevádzku, nie blog.
- **404 coming-soon / 401 /api/auth/me** na verejnom webe — očakávané správanie, nie release note.

## Lokálna plná séria

Rozšírené drafty 1–33 + SC: `docs/marketing/` (gitignored). Synchronizovať slugy s tabuľkou v `docs/marketing/ARTICLE_SERIES_SK.md` pri väčšom release.
