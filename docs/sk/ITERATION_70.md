# Iterácia 70 — Git publish režimy

> **Stav:** ⏳ plánované  
> **Priorita:** 🟡  
> **Vlna:** [Hybrid Engine HE-3](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Závisí od:** [It.68](ITERATION_68.md)  
> **Rozširuje:** existujúci `GitHubService`; koordinuje [It.48](ITERATION_48.md)

## Cieľ

Pridať Git ako **distribučnú vrstvu** pre headless/Jamstack workflow bez zmeny zdroja pravdy. Editor vždy najprv uloží validovaný dokument na disk. Až následne sa podľa stratégie vytvorí commit a voliteľný push.

Podporované režimy:

- **`immediate`** — úspešný publish vytvorí samostatný commit a voliteľný push,
- **`queued`** — zmeny sa evidujú lokálne a oprávnený používateľ spustí jeden dávkový release commit,
- **`disabled`** — Classic default; Git služby sa pri content write nevolajú.

---

## Hranica medzi uložením a distribúciou

API a UI musia rozlišovať stav:

| Stav | Význam |
|------|--------|
| `stored` | dokument je bezpečne uložený v SSOT |
| `pending_publish` | zmena je v idempotentnej publish queue |
| `committed` | lokálny commit bol vytvorený |
| `pushed` | remote potvrdil push |
| `publish_failed` | SSOT je uložené, distribúcia potrebuje retry |

Git chyba nesmie predstierať stratu uloženého dokumentu. Používateľ dostane pravdivú čiastočnú odpoveď a možnosť retry.

---

## Backend

| Komponent | Zodpovednosť |
|-----------|--------------|
| `Core/Git/Contracts/GitPublisherInterface.php` | `status`, `stage`, `commit`, `push`; typed result |
| `Core/Git/LocalGitPublisher.php` | bezpečný wrapper nad `git` binárkou s allow-list argumentov |
| `Core/Git/GitHubApiPublisher.php` | voliteľný API-only adapter nad existujúcim `GitHubService` |
| `Core/Git/PublishQueueStore.php` | atomický flat-file queue/journal v `data/git/` |
| `Core/Git/PublishPlanner.php` | deduplikácia zmien, diff summary a commit plán |
| job `git.publish` | It.29 scheduler/worker; idempotentný retry |
| Admin API | status, preview, publish, retry; permission `git:publish` |

### Návrh endpointov

```http
GET  /api/admin/git/status
GET  /api/admin/git/publish/preview
POST /api/admin/git/publish
POST /api/admin/git/publish/{jobId}/retry
```

Mutácie vyžadujú session + CSRF alebo explicitne povolený scoped Bearer klient po It.74. Prvá verzia je admin-session only.

---

## Nastavenia

```yaml
engine:
  git:
    enabled: false
    publishStrategy: disabled   # disabled | immediate | queued
    publisher: local           # local | github_api
    repositoryPath: null       # server-side validated path/ref
    remote: origin
    branch: main
    pushEnabled: false
    commitMessageTemplate: "content: publish {count} change(s)"
```

Credentialy sú šifrované a nikdy sa nevracajú vo frontend payload-e. `repositoryPath`, remote a branch podliehajú allow-list/validácii; používateľ nesmie vložiť voľný shell fragment.

---

## Bezpečnosť

- proces používa argument array, nie skladaný shell string,
- binárka, working tree a povolené remote/branch sú explicitne konfigurované,
- `OutboundUrlGuard` a egress policy sa uplatnia na API publisher/webhook,
- commit neobsahuje tajomstvá, interné cache, backupy ani runtime logy,
- queue nepovoľuje path mimo content/export allow-listu,
- audit ukladá actor, stratégiu, commit hash, počet zmien a výsledok; nie credentialy,
- force push, arbitrary tag deletion a custom Git command sú mimo rozsahu.

---

## Queue a idempotencia

Každá položka má stabilné ID, resource identitu, revision/fingerprint a požadovanú akciu. Opakovaný job:

- necommituje ten istý fingerprint dvakrát,
- vie zlúčiť viac zmien jedného dokumentu do najnovšej verzie v queued režime,
- zachová audit históriu zrušenej/staršej queue položky,
- používa lock, aby dva workery nevytvorili paralelný release commit.

Remote push a statický build sú dva kroky. It.48 môže spustiť build až po úspešnom push potvrdení alebo explicitnom lokálnom exporte.

---

## Frontend

- Settings → Engine → Git capability, stratégia a test konfigurácie.
- Content list zobrazuje `pending_publish` bez zmeny stavu samotného content publish.
- **Publish release** modal ukáže súhrn ciest, resource typov a commit message; nie raw tajné diffy.
- Chyba ponúkne retry a odkaz na diagnostiku.
- UI jasne odlíši „uložené v CMS“ od „odoslané do Git“.

---

## Migrácia a rollback

- feature je defaultne `disabled`, takže staré inštalácie nemenia správanie,
- capability probe overí binárku, repo, clean/supported state a write permissions,
- prvý test používa dočasné lokálne repo bez remote,
- vypnutie Git publish nemení obsah ani nevyžaduje konverziu,
- queue sa dá exportovať/opraviť; nesmie sa potichu zahodiť pri rollbacku.

---

## Testy

- temp Git repo: stage/commit a stabilný commit metadata contract,
- 3 queued zmeny → 1 release commit,
- repeated job → žiadny duplicitný commit,
- immediate publish draftu sa riadi explicitným pravidlom,
- command injection payload v branch/remote/path je odmietnutý,
- Classic/disabled → publisher sa vôbec nevolá,
- remote failure → dokument zostane `stored`, queue je retryable,
- paralelné workery → jeden commit,
- secrets a excluded directories sa nikdy nedostanú do stagingu.

---

## Definition of Done

- [ ] Lokálny publisher a queue fungujú bez remote služby.
- [ ] Immediate aj queued stratégia majú end-to-end test.
- [ ] API/UI rozlišuje stored/committed/pushed/failed.
- [ ] Retry je idempotentný a auditovaný.
- [ ] Command/path/remote security testy sú zelené.
- [ ] It.48 používa jeden spoločný publish kontrakt, nie paralelnú pipeline.
- [ ] Classic default nevolá Git.
- [ ] SK/EN deploy a architecture dokumentácia je aktualizovaná.

## Súvisiace

[It.29 job runner](ITERATION_29.md) · [It.48 static render](ITERATION_48.md) · [deployment modes](architecture/DEPLOYMENT_MODES.md)
