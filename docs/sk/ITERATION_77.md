# Iterácia 77 — asistovaný preklad cez cloud providerov

> **Stav:** ⏳ plánované  
> **Priorita:** 🔵  
> **Vlna:** [Hybrid Engine HE-6](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Závisí od:** [It.76](ITERATION_76.md)

## Cieľ

Rozšíriť spoločný `TranslationService` o cloud provider drivers bez vytvorenia druhej služby alebo druhého editorového workflow. Admin vyberá provider v nastaveniach; content model, návrhy, diff, Apply, audit a publish pravidlá zostávajú rovnaké ako v It.76.

Dokumentácia nesľubuje konkrétny bezplatný limit. Cenník, dostupnosť regiónov a podmienky providerov sú externé a môžu sa meniť; admin ich overuje pri konfigurácii.

---

## Provider kontrakt

MVP registry:

| Hodnota | Driver | Poznámka |
|---------|--------|----------|
| `none` | žiadny outbound | manuálny workflow |
| `libretranslate` | It.76 | self-hosted / compatible |
| `deepl` | `DeepLTranslationDriver` | pevne definované vendor endpointy |
| `google` | `GoogleTranslationDriver` | minimálny podporovaný auth variant |

Ďalší provider je nový driver implementujúci rovnaký interface a contract tests. Provider-specific výnimky sa mapujú na stabilné doménové kódy, napríklad `AUTH_FAILED`, `QUOTA_EXCEEDED`, `RATE_LIMITED`, `PROVIDER_UNAVAILABLE`, `INVALID_RESPONSE`.

---

## Backend

| Komponent | Zodpovednosť |
|-----------|--------------|
| `DeepLTranslationDriver` | vendor REST adapter, pevný host allow-list, timeout/retry |
| `GoogleTranslationDriver` | podporovaný REST adapter s minimálnym credential scope |
| `TranslationProviderRegistry` | resolver podľa `translation.provider`; žiadna dynamická class name |
| `TranslationCredentialResolver` | decrypt credential iba tesne pred requestom |
| `TranslationUsageMeter` | normalizovaný char/request meter nad It.76 quota store |
| `TranslationErrorMapper` | generické UI chyby bez úniku vendor payloadu/secretu |
| failover policy | voliteľná, explicitná a auditovaná; nikdy tichá zmena významu |

Provider request používa rovnaké štruktúrne ochrany, proposal store, OCC a Apply flow ako It.76.

---

## Nastavenia

```yaml
translation:
  enabled: false
  provider: none                 # none | libretranslate | deepl | google
  fallbackProvider: none
  deepl:
    apiKey: null
  google:
    apiKey: null
    projectId: null
  dailyCharLimit: 0
```

- Credential fields sú šifrované a write-only.
- UI pri zmene providera nevymazáva credentialy bez výslovného potvrdenia, ale nikdy ich nevracia plaintext.
- Vendor endpoint je v driveri allow-listovaný; admin nevkladá arbitrary URL pre cloud driver.
- Service-account JSON, ak bude podporované, musí mať samostatnú schému, veľkostný limit, minimálne scopes a bezpečný parser. MVP môže začať API key variantom.
- `fallbackProvider` je defaultne `none`.

---

## Failover pravidlá

Failover je bezpečný iba keď:

1. admin ho explicitne zapol,
2. sekundárny provider prešiel capability testom,
3. request neobsahuje content zakázaný pre sekundárny provider privacy policy,
4. quota/billing dopad je zobrazený,
5. proposal uvádza, ktorý provider preložil ktorý locale,
6. audit zaznamená fallback dôvod.

Auth failure sa defaultne neprelieva na ďalšieho providera; najprv sa považuje za konfiguračný incident. Tichý failover nesmie obísť organizáciou zvolenú data residency/privacy voľbu.

---

## Frontend

Settings → Translation:

- provider dropdown,
- dynamické credential polia podľa drivera,
- Test connection / test fixed phrase bez použitia reálneho contentu,
- usage meter podľa interného quota store a dostupných vendor metadata,
- jasné upozornenie na externé spracovanie obsahu,
- failover konfigurácia s potvrdením privacy/cost dopadu.

Editor používa presne rovnaké Translate → Diff → Apply rozhranie ako It.76. Provider je detail prevádzky, nie druhý content workflow.

---

## Bezpečnosť a privacy

- secrets cez `EncryptionService`; nikdy v logu, exception message ani frontend hydrate,
- pevné HTTPS endpointy a TLS verification,
- request/response body sa neloguje,
- sanitizované vendor request IDs sa môžu uložiť na troubleshooting,
- provider output je nedôveryhodný a prejde rovnakou schema/content validáciou,
- admin docs vysvetlia, že vybrané texty odchádzajú tretej strane,
- credential rotation nevyžaduje content migráciu,
- test connection používa fixnú ne-citlivú frázu.

---

## Chybové scenáre

- neplatný credential → generická auth chyba; žiadny secret/vendor raw response,
- `429` → quota/rate-limit stav a voliteľný explicitný failover,
- provider partial result → proposal per locale/field s rejected časťami,
- provider switch počas jobu → job používa immutable provider snapshot bez credential dumpu,
- billing/account issue → configuration incident, nie content loss,
- disabled translation → nulový outbound traffic.

---

## Mimo rozsahu

- garantovanie free tieru alebo ceny,
- automatická voľba najlacnejšieho providera,
- odosielanie celého repository/batch bez limitov,
- cloud LLM agent (It.75),
- provider-specific druhý editor,
- live vendor API v CI.

---

## Testy

- contract tests pre DeepL/Google mock adaptery,
- provider switch použije nový driver pri ďalšom jobe,
- credential sa nevracia a je redigovaný v logoch,
- fixed-host policy odmietne arbitrary endpoint,
- `429` a auth error sa mapujú na stabilný kód,
- failover iba pri explicitnej policy a s auditom,
- privacy-disabled provider nedostane request,
- shared It.76 proposal/OCC/Apply tests pre každý driver,
- CI bez live network.

---

## Definition of Done

- [ ] Aspoň dva cloud drivers prejdú spoločným mocked contract suite.
- [ ] Provider sa mení nastavením bez deployu a bez druhého UI.
- [ ] Credentials sú šifrované, write-only a rotovateľné.
- [ ] Error/failover/privacy policy je explicitná a auditovaná.
- [ ] Editor proposal workflow je identický s It.76.
- [ ] Classic/disabled nevykonáva outbound requesty.
- [ ] SK/EN admin a security dokumentácia neobsahuje zastaraný prísľub cien/free tierov.

## Nadväzuje

[It.75 CMS-aware AI agent](ITERATION_75.md)
