# Iterácia 80 — SEO redirecty, integrácie a operátorský toolkit

> **Stav:** ⏳ plánované · checklist aktívny  
> **Priorita:** 🟡 (najprv vysoký dopad / primeraná náročnosť)  
> **Vlna:** Produkt & ops (po HE-5) · nezávislé od Hybrid Engine jadra  
> **Závisí od:** stabilný Classic/headless model ([It.73](ITERATION_73.md), [It.74](ITERATION_74.md) odporúčané)  
> **Snapshot:** 2026-08-09 · `v2.1.0-beta.30`

## Cieľ

Dodať **prioritizovaný balík** operátorských funkcií: SEO, spam ochrana, integrácie, GDPR baseline a bulk operácie — bez druhého content modelu a bez SQL.

Iterácia je **checklist-driven**: každý riadok má status, pripomienky a acceptance kritériá. Sub-fázy `80a`–`80g` môžu ísť v samostatných beta release.

---

## Poradie (dopad / náročnosť)

| Por. | Sub-fáza | Prečo |
|------|----------|-------|
| 1 | **80a** Redirect manager | Najväčší SEO win za najmenšiu prácu |
| 2 | **80b** 404 tracking | Prirodzené nadväzujúce na redirect middleware |
| 3 | **80c** Spam heuristika komentárov | Lacné pred väčším trafficom |
| 4 | **80d** Outbound webhooks | Scheduler/Jobs + headless smer |
| 5 | **80e** GDPR export/anonymizácia | Ak máš EU používateľov v newsletter/komentároch |
| 6 | **80f** CLI toolkit | CI/bulk bez HTTP session |
| 7 | **80g** Import z iných CMS | Onboarding — väčší rozsah |

---

## Master checklist

| ID | Funkcia | Priorita | Stav | Dopad / náročnosť | Popis | Pripomienky / návrhy | Závisí od |
|----|---------|----------|------|-------------------|-------|----------------------|-----------|
| **80a** | Redirect manager (301/302) | 🟡 P1 | ✅ hotové (`beta.32`) | **Vysoký / Nízky** | `data/redirects.json`; middleware; admin UI. | nginx hook voliteľný pre slug redirecty na produkcii. | — |
| **80b** | 404 tracking report | 🟡 P2 | ✅ hotové (`beta.35`) | **Stredný / Nízky** | Log 404 hitov; dashboard + CSV. | Vzor AccessLog / PerformanceSample; sanitizácia. | odporúčané **80a** |
| **80c** | Spam heuristika komentárov | 🟡 P3 | ✅ hotové (`beta.35`) | **Stredný / Nízky** | Honeypot + skóre v `CommentPolicyResolver`. | Bez CAPTCHA lock-in; karanténa v admin inboxe. | comment policy |
| **80d** | Outbound webhooks | 🟡 P4 | ✅ hotové (`beta.36`) | **Stredný / Stredný** | `content.published`, `content.updated` → POST (Slack/Zapier). | `OutboundUrlGuard`, HMAC, retry queue. | Jobs · [It.74](ITERATION_74.md) |
| **80e** | GDPR export / anonymizácia | 🔵 P5 | ✅ hotové (`beta.37`) | **Stredný / Stredný** | Export JSON/ZIP používateľa; anonymizácia PII. | Nie full DPA produkt; audit exportov. | user/comment/newsletter |
| **80f** | CLI nástroje | 🔵 P6 | ⏳ plánované | **Stredný / Stredný** | `content:import/export`, `user:create`, `redirect:validate`. | Rovnaké validátory ako HTTP. | console |
| **80g** | Import z CMS | 🔵 P7 | ⏳ plánované | **Vysoký / Vysoký** | WordPress XML, Jekyll, Ghost → flat-file. | Fáza 1: články/stránky; dry-run. | **80f** · [It.73](ITERATION_73.md) |

### Legenda stavu

| Symbol | Význam |
|--------|--------|
| ⏳ plánované | Špecifikácia OK; nezačaté |
| 🚧 v riešení | Aktívna implementácia |
| ✅ hotové | V tagovanom release |
| ⏸️ odložené | Vedome posunuté |
| 💡 návrh | Potrebuje schválenie rozsahu |

---

## Súvisiace fixy (beta.31 — mimo It.80)

| Položka | Stav | Release |
|---------|------|---------|
| API keys v menu pre ADMIN | ✅ hotové | `v2.1.0-beta.31` |
| Banner chýbajúci PEPPER + chybové hlášky | ✅ hotové | `v2.1.0-beta.31` |
| AUTO `api-keys:manage` pre ADMIN v ACL | ✅ hotové | `v2.1.0-beta.31` |

---

## Odporúčané slice release

```text
beta.31  — ✅ API keys UX fix pack (shipped)
beta.32  — ✅ 80a redirect manager (shipped)
beta.33  — ✅ deploy pipeline fix (shipped)
beta.35  — ✅ 80b + 80c spolu (404 + spam; plánovaná beta.34 preskočená)
beta.36  — ✅ 80d outbound webhooks (shipped)
beta.38+ — 80f / 80g / …
```

Detail EN: [ITERATION_80.md](../en/ITERATION_80.md)
