# Iterácia 71 — Performance Guard (aplikačné APM)

> **Stav:** ⏳ plánované  
> **Priorita:** 🟡  
> **Vlna:** [Hybrid Engine HE-4](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Závisí od:** [It.69](ITERATION_69.md)  
> **Dopĺňa:** It.7 reporting a zostávajúci host-metrics rozsah It.46

## Cieľ

Zaviesť ľahké **in-request APM** pre Slim: latenciu, memory delta, počty storage/cache operácií a chybové budgety. Performance Guard upozorňuje na regresie a môže vykonať iba konzervatívne, vopred schválené opatrenia nad odvodenými vrstvami.

Nie je to náhrada Promethea, host agenta ani profileru. Je to aplikačný guardrail dostupný aj na malom self-hosted nasadení.

---

## Merací model

| Metrika | Zdroj | Poznámka |
|---------|-------|----------|
| request duration | monotonic timer | route template, nie raw URL so slugom/tokenom |
| peak/memory delta | PHP runtime | trend, nie presná host RAM |
| storage reads/writes | instrumentované rozhranie It.68 | bez názvov citlivých súborov |
| cache hit/miss/fallback | It.69 | agregované podľa resource typu |
| response status/error class | middleware | bez stack trace v metrike |
| queue latency | job metadata | voliteľné pre publish/translation/agent |

Samples sa ukladajú do ohraničeného ring bufferu alebo exportujú cez existujúci metrics kontrakt. Content payloady, query tokeny a tajomstvá sa nemerajú.

---

## Backend

| Komponent | Zodpovednosť |
|-----------|--------------|
| `PerformanceGuardMiddleware` | timing, route label, status a odovzdanie sample |
| `PerformanceContext` | request-local counters pre storage/cache operácie |
| `PerformanceSampleStore` | atomický bounded ring buffer v `data/metrics/` |
| `PerformanceGuardPolicy` | warning/critical budgety per route group |
| `PerformanceAggregator` | p50/p95/p99, error rate a breach windows |
| `PerformanceIncidentService` | deduplikácia incidentov a notifier integrácia |
| `SafeRemediationService` | iba allow-listované opatrenia nad odvodenými vrstvami |
| Admin API | súhrny a posledné breach udalosti; permission `metrics:read` |

---

## Nastavenia

```yaml
engine:
  performanceGuard:
    enabled: false
    sampleRate: 1.0
    latencyMsWarning: 200
    latencyMsCritical: 500
    breachCount: 3
    windowMinutes: 10
    remediationMode: suggest   # off | suggest | automatic
```

Default je vypnutý. Produkčný admin nastavuje budgety podľa vlastného hardvéru a workloadu; dokumentácia ich neprezentuje ako univerzálne SLA.

---

## Bezpečný remediation model

### `suggest` — odporúčaný default

Guard vytvorí incident a odporučí napríklad:

- rebuild indexu,
- purge konkrétneho cache tagu,
- zapnutie už nakonfigurovaného cache drivera,
- kontrolu scheduler/worker fronty.

### `automatic` — explicitný opt-in

Automatická akcia je povolená iba keď:

1. capability bola vopred úspešne otestovaná,
2. zmena je idempotentná a vratná,
3. týka sa iba indexu/cache/worker režimu, nie primárneho obsahu,
4. existuje cooldown a maximálny počet pokusov,
5. každá akcia má audit a výsledok,
6. zlyhanie vedie k `suggest`, nie k reťazeniu ďalších náhodných zmien.

Guard **nesmie automaticky zapnúť Redis**, ak neexistuje platná a overená Redis konfigurácia. Nikdy nevypína auth, CSRF, WAF, audit alebo validáciu „kvôli výkonu“.

---

## Frontend

- dashboard widget: p95, error rate, cache fallback a breach trend,
- Settings → Engine → Performance Guard s jasným overhead/retention popisom,
- incident detail s route group, časovým oknom a odporúčaním,
- samostatný odkaz na host metriky It.46, aby sa nemiešala PHP a OS vrstva.

---

## Retencia a ochrana dát

- ring buffer má pevný limit a atomickú rotáciu,
- route sa ukladá ako template (`/api/articles/{slug}`), nie raw path,
- IP, user-agent a používateľské ID sa neukladajú, ak nie sú potrebné pre samostatný bezpečnostný incident,
- export metrics neobsahuje title, body, prompt ani preklad,
- admin môže vyčistiť samples bez straty obsahu.

---

## Mimo rozsahu

- host CPU/RAM/disk agent,
- full distributed tracing,
- automatické škálovanie infraštruktúry,
- zmeny primárneho obsahu,
- automatické vypínanie bezpečnostných middleware,
- sľub konkrétneho výkonu na neznámom hardvéri.

---

## Testy

- middleware sample na health/content route,
- route template neobsahuje raw slug/token,
- counters z It.68/69 sa agregujú korektne,
- breach window deduplikuje notifikácie,
- disabled fast path má merateľne minimálny overhead,
- `suggest` nemení settings,
- automatic bez capability → žiadna zmena,
- automatic s overenou vratnou akciou → jeden auditovaný zásah za cooldown,
- ring buffer limit a concurrent writes,
- Classic bez guardu funguje identicky.

---

## Definition of Done

- [ ] Staging zobrazuje p95 a breach trend bez content payloadov.
- [ ] Umelo spomalená test route vytvorí deduplikovaný incident.
- [ ] Default je `enabled=false`, `remediationMode=suggest`.
- [ ] Automatic režim má capability gate, cooldown, rollback a audit.
- [ ] Redis sa nikdy nezapne bez overenej konfigurácie.
- [ ] It.46 a It.71 majú jasne oddelenú zodpovednosť.
- [ ] Retencia, privacy a runbook sú v SK/EN dokumentácii.

## Súvisiace

[It.69 cache](ITERATION_69.md) · [It.46 host metrics](ITERATION_46.md) · [It.7 reports](ITERATION_7.md)
