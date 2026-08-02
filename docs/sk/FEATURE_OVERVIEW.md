# PaginiumCMS — prehľad funkcií

> **Účel:** jeden živý inventár toho, čo je dodané, čo je čiastočné a čo je plánované  
> **Snapshot:** `v2.1.0-beta.23` · 2. august 2026  
> **Architektúra:** React/Vite SPA ↔ Slim REST API ↔ PHP Core ↔ No-SQL súborový SSOT

**Súvisiace:** [ROADMAP.md](ROADMAP.md) · [ITERATION_BACKLOG.md](ITERATION_BACKLOG.md) · [PUBLIC_BETA1.md](PUBLIC_BETA1.md) · [architecture/HYBRID_ENGINE.md](architecture/HYBRID_ENGINE.md)

| Symbol | Význam |
|--------|--------|
| ✅ | funkcia je dodaná a reálne zapojená |
| 🟡 | existuje použiteľný základ, ale zostáva konkrétny rozsah |
| ⏳ | naplánované |
| 🔒 | typicky ADMIN/SUPER_ADMIN, podľa ACL a 2FA politiky |
| 🛠 | vyžaduje Developer Mode / unlock |

---

## 1. Release snapshot

| Míľnik | Verzie | Výsledok |
|--------|--------|----------|
| Stabilizácia jadra | `2.0.x` až `2.0.58` | obsah, auth, médiá, bezpečnosť, pluginy, i18n, beta infra |
| Public Beta 1 | `v2.1.0-beta.1` | prvý verejný beta gate a tester path |
| Beta patch séria | `beta.2` až `beta.23` | hardening, UX, demo, newsletter, update, galéria, layout |
| Najnovšie vydanie v snapshot-e | **`v2.1.0-beta.23`** | It.58c Layout Switch |
| Hybrid Engine | Fáza 0 docs | cieľová architektúra; kód It.68+ ešte nezačal |
| Final 1.0 | bez tagu | scope musí potvrdiť samostatný release gate |

---

## 2. Autentifikácia, používatelia a prístup ✅

| Funkcia | Backend | UI | Poznámka |
|---------|---------|----|----------|
| Session login/logout | ✅ | ✅ | session regeneration, bezpečné cookies podľa deployu |
| Registrácia | ✅ | ✅ | zapínateľná cez settings |
| Password reset/change | ✅ | ✅ | generické odpovede proti account enumeration |
| TOTP 2FA | ✅ | ✅ | setup, QR, login krok |
| Password confirmation | ✅ | ✅ | registrácia a admin user flow |
| Role a permissions | ✅ | ✅ | RBAC + `PermissionMiddleware` |
| Path/resource ACL | ✅ | ✅ | obsahové a médiové rozsahy |
| Email OTP workflow | ✅ | ✅ | voliteľné schválenia podľa nastavení |
| SSO OAuth základ | ✅ | 🟡 | závisí od provider konfigurácie a finálneho UX |
| API keys/JWT | ⏳ | ⏳ | It.74; aditívne, admin session zostáva |

---

## 3. Obsah a spolupráca ✅

| Funkcia | Stav | Poznámka |
|---------|------|----------|
| Stránky a články CRUD | ✅ | súborové repository + index |
| Markdown editor | ✅ | typovaný admin flow |
| WYSIWYG/Tiptap profily | ✅ | modulárny toolbar, JSON storage |
| Draft auto-save | ✅ | interval a dirty-state workflow |
| Editovacie zámky | ✅ | heartbeat + TTL |
| Optimistická súbežnosť | ✅ | revision + HTTP 409 |
| 3-way merge | ✅ | automatické a manuálne riešenie konfliktu |
| História verzií a diff | ✅ | editor integration |
| Plánovaná publikácia | ✅ | vyžaduje funkčný scheduler/cron |
| OTP publish approval | ✅ | voliteľné podľa workflow settings |
| Bulk actions | ✅ | content/trash/comments/users/backups podľa modulu |
| Filtre, sort, pagination | ✅ | admin a verejný blog |
| SEO panel a suggest-meta | ✅ | title, description, tags, social meta |
| Live preview | 🟡 | modal/preview route existuje; plný in-editor režim môže pokračovať v It.58d |
| Multi-locale jeden dokument | ⏳ | It.73 |

---

## 4. Médiá a DAM ✅

| Funkcia | Stav | Poznámka |
|---------|------|----------|
| Upload a priečinky | ✅ | bezpečné typy a cesty |
| Metadata a alt text | ✅ | editácia v admin UI |
| Bulk operácie | ✅ | spoločný selection pattern |
| Stock image import | ✅ | konfigurovateľný zdroj |
| Lightbox preview | ✅ | strict binary handling |
| Editor image upload | ✅ | Tiptap integration |
| Verejné `/storage/` | ✅ | allow-list a bezpečné content headers |
| Flysystem/S3/CDN drivers | ⏳ | It.72; lokálny driver zostane default |
| Scoped Section FileManager | ⏳ kandidát | zostávajúci backlog bez prideleného nového čísla |

---

## 5. Verejný web ✅

- React verejné routy pre home, stránky, blog a detail článku.
- SK/EN používateľské rozhranie.
- RSS, sitemap, robots a SEO metadata.
- Bohatá viacúrovňová navigácia s popisom a ikonou.
- Farebné schémy a light/dark/system režim.
- Maintenance/coming-soon režim.
- Cookie consent nastavenia.
- Kontaktný formulár s konfigurovateľnými predmetmi.
- Newsletter subscribe/confirm/preferences/unsubscribe a admin odberatelia.
- Feature gallery s layoutmi, efektmi, deep linkmi a export/import metadát.
- Layout template selection dodaný v It.58c.

**Zostáva:** ďalší layout/editor polish It.58d a voliteľný static/Jamstack render It.48/70.

---

## 6. Administrácia a prevádzka ✅

| Oblasť | Stav | Hlavné prvky |
|--------|------|--------------|
| Dashboard a analytics | ✅ | KPI, activity, referrer/device/geo enrichment, SPA beacon |
| Search a navigácia adminu | ✅ | `Ctrl+K`, deep links, sidebar counts |
| Settings engine | ✅ | schema-driven forms, encrypted secrets |
| Admin i18n | ✅ | SK/EN + editor prekladov |
| Scheduler a queue | ✅ | registry, CLI, UI, outcome history |
| Zálohy a koš | ✅ | create/restore/import/hash/verify, soft delete |
| Audit a logy | ✅ | sanitizácia, CSV, app/HTTP/security pohľady |
| Notifikácie | ✅ | SMTP, ntfy, Discord, Telegram, webhook |
| Monitoring reporty | ✅ | scheduled HTML reports; cron required |
| WAF | ✅ | detekcia, jail/ban, admin UI |
| GitHub content sync | ✅ čiastočne k cieľu | obsahová integrácia; nie plný It.70 Git publish |
| System update | ✅ | version check, tag deploy, voliteľný webhook |
| Demo sandbox | ✅ | izolovaný demo režim a reset |
| Setup wizard | ⏳ | It.25 pre-Final; `first-run.sh` je súčasný onboarding |
| Performance Guard | ⏳ | It.71 |

---

## 7. Rozšírenia a Developer Mode

| Funkcia | Stav |
|---------|------|
| External plugin registry/runtime | ✅ |
| ZIP import a Zip-Slip ochrana | ✅ |
| Hook emitters | ✅ |
| Code Policy / security scanner | ✅ |
| Code Editor create/delete/restore | ✅ 🛠 |
| Custom editor components | ✅ |
| Blueprint manager | ✅ |
| Full theme import/runtime | 🟡 |
| Untrusted surfaces hardening | ⏳ It.67 |
| JSON Schema registry pre všetky admin zápisy | ⏳ It.68 |

---

## 8. Bezpečnostný baseline ✅ priebežne

- CSRF na mutujúcich session endpointoch.
- RBAC/ACL na chránených operáciách.
- TOTP a voliteľné workflow OTP.
- Šifrovanie citlivých settings/user polí cez aplikačný kľúč.
- SSRF ochrana outbound URL.
- Path traversal, Zip-Slip a media allow-list.
- WAF, login/OTP/newsletter rate limits.
- Sanitizácia logov a CSV proti injection.
- Demo režim fail-closed mimo určenej inštancie.
- Security audit a incident log.

Hybrid Engine nesmie znížiť tento baseline. Nové ovládače používajú existujúce doménové brány.

---

## 9. Hybrid Engine — plánované schopnosti

| Schopnosť | Dnes | Cieľ |
|-----------|------|------|
| Súborový SSOT | ✅ | zachovať bez výnimky |
| Obsahový index | ✅ | formalizovať za abstrakciou It.68 |
| File/memory cache | ✅ | zjednotiť v It.69 |
| Redis | ❌ | voliteľný driver It.69 |
| HTTP validators | ❌ | `ETag`/`Last-Modified` It.69 |
| Git publish | 🟡 iba content sync | immediate/queued It.70 |
| APM | ❌ | Performance Guard It.71 |
| S3 media | ❌ | It.72 |
| Multi-locale document | ❌ | It.73 |
| API keys/JWT | ❌ | It.74 |
| AI agent | ❌ | It.75 |
| Self-hosted translation | ❌ | It.76 |
| Cloud translation | ❌ | It.77 |

---

## 10. Quality a testovanie

Povinný gate zahŕňa:

- PHP syntax a coding standard,
- PHPStan level 8,
- PHPUnit,
- TypeScript strict check,
- ESLint vrátane API barrel kontroly,
- Vitest,
- podľa zmeny API smoke, deploy smoke a bezpečnostný pack.

Počty testov sa menia každým vydaním; kanonický stav je výstup aktuálneho gate, nie pevné číslo v dokumente.

```bash
composer gate
cd frontend && npm run type-check && npm run lint && npm test
```

---

## 11. Známe hranice snapshot-u

- Host cron musí byť na každej inštancii reálne nakonfigurovaný; samotná existencia jobov nestačí.
- SSO, webhook deploy a externé konektory vyžadujú správnu konfiguráciu secrets a outbound policy.
- Classic režim je aktuálny bezpečný základ; Hybrid/Git-headless capability je ešte plán.
- Setup wizard, finálny theme model a celé It.68–77 nie sú súčasťou `beta.23`.
- Stav konkrétneho incidentu sa overuje v `ISSUES.md`; tento prehľad nie je incident tracker.

---

## 12. Údržba

Tento dokument sa aktualizuje pri každom release, ktorý:

- dodá alebo odstráni používateľskú schopnosť,
- zmení stav `⏳/🟡/✅`,
- pridá route modul alebo admin/public route,
- zmení bezpečnostný baseline,
- mení rozsah Final 1.0.
