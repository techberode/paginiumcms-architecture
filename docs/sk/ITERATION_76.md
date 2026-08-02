# Iterácia 76 — asistovaný preklad cez self-hosted provider

> **Stav:** ⏳ plánované  
> **Priorita:** 🔵  
> **Vlna:** [Hybrid Engine HE-6](ITERATION_WAVE_HYBRID_ENGINE.md)  
> **Závisí od:** [It.73](ITERATION_73.md)  
> **Provider smer:** LibreTranslate-compatible / self-hosted / OSS

## Cieľ

Pridať **asistovaný preklad** chýbajúcich locale variantov cez self-hosted HTTP provider. Výsledok je návrh na kontrolu; systém ho nikdy automaticky nepublikuje.

It.76 vytvára spoločný `TranslationProviderInterface`, službu, kvóty a editorový workflow, ktoré It.77 rozšíri cloud drivermi bez druhého UI.

---

## Workflow

```text
editor vyberie source locale + target locales + fields
→ server načíta autorizovaný resource
→ job rozdelí/chráni štruktúru
→ provider vráti preklad
→ server validuje návrh
→ editor porovná diff
→ Apply uloží draft po revision kontrole
→ Publish je samostatná ľudská akcia
```

Prekladová požiadavka nesmie obísť lock/OCC It.73. Ak sa zdroj počas jobu zmení, Apply vráti konflikt namiesto prepísania novšej verzie.

---

## Backend

| Komponent | Zodpovednosť |
|-----------|--------------|
| `TranslationProviderInterface` | typed request/result, provider capability a health |
| `LibreTranslateDriver` | kompatibilný REST klient s timeoutom a retry policy |
| `TranslationService` | field selection, chunking, placeholder protection, result validation |
| `TranslationProposalStore` | krátko žijúci flat-file návrh viazaný na actor/resource/revision |
| `TranslationQuotaStore` | bounded daily character/use counters; odvodený prevádzkový stav |
| `TranslationProviderRegistry` | allow-list providerov; `none | libretranslate` |
| job `content.translate` | It.29 worker pre dlhšie požiadavky |
| Admin API | create job, status, result, apply/discard |

### API koncept

```http
POST /api/admin/content/{type}/{slug}/translations
GET  /api/admin/translations/{jobId}
POST /api/admin/translations/{jobId}/apply
DELETE /api/admin/translations/{jobId}
```

Create body obsahuje `sourceLocale`, `targetLocales`, `fields`, `sourceRevision`. Apply vyžaduje tú istú alebo explicitne vyriešenú revision.

---

## Ochrana štruktúry

TranslationService musí rozlišovať plain text, Markdown, HTML a Tiptap JSON:

- kódové bloky, URL, media IDs, shortcode tokeny a placeholders sa chránia,
- HTML sa neodosiela/neskladá ako neoverený raw string bez parsera,
- Tiptap JSON sa prekladá iba v allow-listovaných text nodes,
- návrat providera prejde schema a content sanitizer validáciou,
- neplatná štruktúra sa odmietne, nie „opraví“ riskantným regexom.

---

## Nastavenia

```yaml
translation:
  enabled: false
  provider: none              # none | libretranslate
  baseUrl: null
  apiKey: null
  dailyCharLimit: 0           # 0 = admin-defined unlimited policy
  overwriteExisting: false
  timeoutSeconds: 15
```

- API key je šifrovaný a write-only vo frontend odpovedi.
- `baseUrl` prechádza `OutboundUrlGuard`.
- Keď je provider v LAN/private rozsahu, admin ho musí explicitne povoliť v outbound allow-liste; všeobecné povolenie private IP je zakázané.
- Deploy dokumentácia používa pripnutú verziu/digest image, nie nekontrolovaný `latest`.

---

## Frontend

V multi-locale editore:

- **Preložiť chýbajúce** alebo výber konkrétnych polí/locales,
- jasná identifikácia source locale,
- job progress a provider offline stav,
- side-by-side diff per locale,
- Apply selected, Discard a manuálna úprava,
- existujúci locale sa defaultne neprepisuje,
- publish zostáva samostatné tlačidlo.

Settings → Translation obsahuje URL, credential write-only field, test connection, quota a privacy upozornenie.

---

## Bezpečnosť a súkromie

- provider dostane iba vybrané polia, nie celý admin resource alebo secrets,
- content text sa neloguje; log obsahuje provider, char count, locale, job ID a výsledok,
- request je autorizovaný ako invoking user a vyžaduje `content:read` + `content:write`,
- endpoint má rate limit a quota,
- návrh má TTL a ACL; iný používateľ ho nečíta bez oprávnenia,
- provider response je nedôveryhodný vstup,
- disable znamená nulový outbound traffic.

---

## Chybové scenáre

- provider offline/timeout → retryable job bez zmeny contentu,
- quota exceeded → `429`/jasný stav, návrh sa neaplikuje,
- partial target failure → výsledok po locale s možnosťou retry iba zlyhaných,
- source revision changed → Apply `409`,
- invalid provider response → rejected proposal + sanitized incident,
- disabled provider → `503` alebo capability error bez outbound requestu.

---

## Mimo rozsahu

- cloud provider drivers (It.77),
- autonómny publish,
- preklad tajomstiev, logov alebo systémových konfigurácií,
- tréning modelu na customer content,
- neobmedzený outbound endpoint bez allow-listu,
- všeobecný AI agent (It.75).

---

## Testy

- mocked provider vracia SK→EN proposal,
- Markdown/HTML/Tiptap placeholders sa zachovajú,
- SSRF private target bez allow-listu je blokovaný,
- explicitne povolený LAN provider funguje,
- quota/rate limit a concurrent counter writes,
- source revision conflict pri Apply,
- invalid schema response je odmietnutá,
- no content text/API key v logoch,
- disabled/offline → nulová mutácia a jasný stav,
- CI nepoužíva live network.

---

## Definition of Done

- [ ] Self-hosted provider vytvorí SK→EN draft proposal v editore.
- [ ] Apply zapisuje cez It.73 schema/OCC a audit `content.translated`.
- [ ] Publish nie je súčasť Apply.
- [ ] Provider URL, tajomstvá, kvóty a logovanie majú bezpečnostné testy.
- [ ] Shared provider interface je pripravený pre It.77.
- [ ] Classic/disabled nemá outbound traffic ani povinnú službu.
- [ ] SK/EN user, security a deploy dokumentácia je aktualizovaná.

## Nadväzuje

[It.77 cloud providers](ITERATION_77.md) · [It.75 AI agent](ITERATION_75.md)
