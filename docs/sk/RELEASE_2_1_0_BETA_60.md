# Release `v2.1.0-beta.60` — Admin UX (It.86) + Origin automatizácia

> **Dátum:** 2026-08-30 · **Tag:** `v2.1.0-beta.60`  
> **Kanónický release (EN):** [../en/RELEASE_2_1_0_BETA_60.md](../en/RELEASE_2_1_0_BETA_60.md)  
> **Incidenty:** [ISS-158](../ISSUES.md#iss-158), [ISS-159](../ISSUES.md#iss-159)

## Pre editorov

- **Command palette** — hlavička adminu, **Ctrl+Shift+K**, session auth pre admin search
- **Tlač článku** — nastavenie `content.articlePrintEnabled` (default vypnuté)
- **Bulk „X z Y“** — stránky/články, správy, komentáre

## Opravy

| ID | Problém |
|----|---------|
| ISS-158 | Admin search 401 pri prihlásení |
| ISS-159 | HTTP 500 po oprave ISS-158 (PHP-DI) |

## Origin Panel (maintainer)

Deploy badge, release slices z checklistu, runtime verzia, nové probes It.83/It.86.

## Plánované (len dokumentácia)

[ITERATION_87.md](ITERATION_87.md) — Plánovač projektu stránky + voliteľný allow-list JS v témach.

## Deploy

```bash
DEPLOY_FORCE=1 GIT_REF=v2.1.0-beta.60 ./scripts/deploy-instance-update.sh
```

Po deployi: Ctrl+Shift+K → vyhľadávanie; bulk counter; Origin badge **live** na verzii ≥ beta.60.
