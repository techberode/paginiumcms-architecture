---
title: Admin deep links
description: Stabilný kontrakt admin ciest, query parametrov a navigačných helperov
icon: material/link-variant
---

# 🔗 Admin deep links

> **Cieľ:** zdieľateľné, obnoviteľné a spätne kompatibilné URL v administrácii  
> **Použitie:** sidebar, dashboard, Ctrl+K, notifications, audit odkazy a browser history  
> **Bezpečnostné pravidlo:** deep link je navigácia, nie authorization

Deep link umožní otvoriť konkrétny modul, filter, settings group alebo audit kontext po refreshi a pri zdieľaní URL. Stav, ktorý má prežiť reload a je bezpečný v URL, patrí do path/query; dočasný modal state alebo secret nie.

---

## 1. Kanonické tvary

| Typ | Formát | Príklad |
|-----|--------|---------|
| modul | `/{module}` | `/media`, `/comments` |
| resource zoznam | `/{module}?…` | `/pages?q=foo&page=2&status=draft` |
| resource detail/edit | stabilná route podľa registry | `/pages/o-nas/edit` alebo kanonický projektový ekvivalent |
| settings | `/settings?category={category}&group={group}` | `/settings?category=security&group=accessControl` |
| log filter | `/logs?severity={level}` | `/logs?severity=critical` |
| audit content | `/audit/content/{contentId}` | `/audit/content/page-home` |
| audit user | `/audit/user/{userId}` | `/audit/user/editor-1` |
| locale | query/path podľa finálneho It.73 kontraktu | `/pages/o-nas/edit?locale=en` |

Dokument nefixuje neoverenú detail route len podľa hypotézy. Route registry a frontend test musia potvrdiť kanonický tvar; helper je jediný producer URL.

---

## 2. Query pravidlá

- názvy sú ASCII a stabilné,
- hodnoty sa kódujú cez `URLSearchParams`, nie ručným concat,
- default hodnoty sa môžu z URL vynechať,
- parameter order nemá meniť význam,
- neznámy parameter sa ignoruje alebo odstráni cez `replace`, nie crash,
- invalid enum/bounds sa normalizuje na bezpečný default,
- multi-value filter má jeden zdokumentovaný tvar,
- prázdny search sa z URL odstráni,
- zmena filtra zvyčajne resetne `page=1`,
- UI musí rešpektovať browser back/forward.

Do URL nikdy nepatrí password, CSRF, session ID, API key, reset token, OAuth code po spracovaní, celý content, raw provider prompt alebo interná storage cesta.

---

## 3. Settings deep links

Settings groups zodpovedajú schema keys. Príklady:

| Účel | Canonical link |
|------|----------------|
| role permissions | `/settings?category=security&group=accessControl` |
| branding | `/settings?category=site&group=branding` |
| logging | `/settings?group=logging` |
| firewall | `/settings?group=firewall` |
| scheduler | `/settings?group=scheduler` |
| SMTP | `/settings?group=smtp` |
| connectors | `/settings?group=connectors` |
| code policy | `/settings?group=codePolicy` |

Ak group neexistuje alebo actor nemá permission, UI zobrazí bezpečný fallback/403 a nesmie otvoriť prvú tajnú group náhodou.

`category` je prezentačný hint; `group` je stabilnejší schema key. Backend permission zostáva autorita.

---

## 4. Lists a filtre

Odporúčané parametre:

| Modul | Parametre |
|-------|-----------|
| pages/articles | `q`, `page`, `per_page`, `status`, `sort`, `tag`, `author`, `date_from`, `date_to` |
| media | `q`, `page`, `folder`, `type`, `sort` |
| comments/messages | `q`, `page`, `status`, `sort` |
| logs | `severity`, `channel`, `q`, `from`, `to`, `page` |
| audit | `type`, `severity`, `actor`, `target`, `from`, `to`, `page` |
| jobs | `status`, `type`, `page` |

Parser musí používať rovnaké allow-listy ako API client schema. UI aliasy sa normalizujú na jeden canonical query key.

---

## 5. Audit odkazy

```text
/audit/content/{contentId}
/audit/user/{userId}
```

ID sa path-encode a server request používa canonical ID, nie display title/email. Audit link nesmie obsahovať citlivú hodnotu používateľa v query. Ak target už neexistuje, audit history môže zostať čitateľná podľa retention/permission policy.

---

## 6. Locale-aware links — It.73

Locale parameter musí reprezentovať **editovanú/requested locale**, nie predstierať, že fallback je uložený preklad.

Pri zmene locale:

- dirty editor musí vyžiadať save/discard/cancel,
- pending autosave sa bezpečne dokončí alebo zruší,
- URL sa zmení až po potvrdení nového state,
- unauthorized locale vetva sa neotvorí,
- cache/query key zahŕňa locale.

---

## 7. `returnTo` po login/2FA

Povolený je iba interný path začínajúci jedným `/`, ktorý:

- neobsahuje scheme/host,
- nezačína `//`,
- po decode stále zostáva interný,
- smeruje na známu admin route alebo bezpečný fallback,
- neobsahuje secret parametre.

`returnTo` sa normalizuje central helperom. Nevalidovaný redirect by vytvoril open redirect/phishing vektor.

---

## 8. Central helper API

Typické helpery:

```ts
settingsGroupPath(group, category?)
logsPath({ severity, channel, page })
auditContentPath(contentId)
auditUserPath(userId)
contentListPath(type, filters)
contentEditPath(type, slug, locale?)
safeAdminReturnTo(location)
```

Helper:

- používa route constants/registry,
- percent-encode hodnoty,
- vynechá defaults/undefined,
- deterministicky serializuje query,
- nevkladá secret,
- je pokrytý unit testom.

Ručné stringy roztrúsené v dashboarde, sidebare a palette vedú k driftu.

---

## 9. Producers a consumers

| Zdroj odkazu | Cieľ |
|--------------|------|
| sidebar | hlavné module routes |
| dashboard cards/chips | module + filter |
| LogsManager | settings logging |
| FirewallManager | settings firewall |
| Scheduler | settings scheduler/jobs |
| Notifications | SMTP/connectors |
| Ctrl+K | route/resource `adminPath` |
| audit event | content/user context |
| toast po operácii | detail/resource alebo job status |

Každý producer používa helper. Consumer číta URL pri mount aj pri popstate a synchronizuje UI bez nekonečného replace loopu.

---

## 10. Legacy kompatibilita

Existujúci `location.state.group` môže byť dočasne podporovaný:

```text
query param → canonical source
legacy location.state → jednorazový fallback → replace canonical URL
```

Legacy alias dostane test a deprecation záznam. Nemá sa držať navždy, pretože neprežije refresh ani zdieľanie URL.

Pri zmene route názvu sa použije interný redirect/alias na kanonický path počas migračného obdobia.

---

## 11. Security a privacy

Deep link:

- neobchádza route/backend guard,
- nesmie renderovať raw query ako HTML,
- validuje path IDs a double decode,
- nezapisuje PII/secrets do analytics bez policy,
- neumožní `javascript:`/external scheme v `adminPath`,
- nepoužíva filesystem path ako content ID,
- maskuje resource existence podľa backend 404/403 policy.

Command palette route result sa musí prehnať cez internal-path validator pred navigáciou.

---

## 12. Testovanie

- helper output pre reserved characters a Unicode slug,
- parser invalid enum/negative page/oversized query,
- default omission a deterministic ordering,
- browser refresh/back/forward,
- settings group + category sync,
- legacy `location.state` migration,
- safe `returnTo` a open redirect cases,
- command palette external/malformed `adminPath`,
- dirty editor pri locale/resource switch,
- unauthorized route po deep linku,
- žiadne secret fields v URL snapshots.

---

## 13. Súvisiace dokumenty

- [FRONTEND.md](./FRONTEND.md) — router, state a guards
- [API.md](./API.md) — backend route rodiny
- [CONTENT_API.md](./CONTENT_API.md) — list/detail/filter/locale kontrakt
- [SETTINGS.md](./SETTINGS.md) — schema groups
- [CORE_HARDENING.md](./CORE_HARDENING.md) — redirect, XSS a authorization pravidlá
