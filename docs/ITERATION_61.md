# Iteration 61 – Newsletter vo footeri (verejný odber)

**Status:** ⏳ Planned  
**Priorita:** 🟡 Stredná  
**Nadväzuje na:** [It.52 Kontakt / dashboard](ITERATION_52.md) ✅ · maintenance newsletter (2.0.51)

## Cieľ

V **pätičke verejného webu** rýchle prihlásenie na **odber newslettera** (email). Funkciu možno **zapnúť / vypnúť** v administrácii (bez deploye).

## Rozsah

| Oblasť | Popis |
|--------|--------|
| **Footer FE** | Kompaktný formulár (email + tlačidlo); SK/EN i18n; GDPR súhlas checkbox (voliteľné pole v settings) |
| **API** | `POST /api/newsletter/subscribe` (verejné, rate limit, CSRF exempt alebo token) |
| **Úložisko** | Flat-file `data/newsletter/subscribers.json` alebo rozšírenie existujúceho maintenance newsletter store |
| **Admin** | Nastavenia → Stránka / Kontakt: `newsletterFooterEnabled`, texty, double opt-in (voliteľné v2) |
| **Admin prehľad** | Export / zoznam odberateľov (read-only, SUPER_ADMIN alebo ADMIN) |

## Odlíšenie od maintenance newsletter (2.0.51)

| | Maintenance (`POST /api/maintenance/newsletter`) | Footer newsletter (It.61) |
|--|--|--|
| Kontext | Coming Soon / Údržba stránky | Bežný footer na celom webe |
| Zapnutie | Režim údržby | Samostatný prepínač v nastaveniach |
| Účel | Lead počas výpadku | Pravidelný marketing / novinky |

## Technicky

- Reuse `NotificationService` pre potvrdenie (voliteľné).
- Spam ochrana: `RateLimitMiddleware` + honeypot field.
- Unsubscribe link (v2) — mimo MVP ak treba rýchle MVP.

## Acceptance criteria

- [ ] Footer formulár skrytý keď `newsletterFooterEnabled=false`
- [ ] Úspešné prihlásenie → 201 + uloženie emailu (deduplikácia)
- [ ] Admin toggle bez reštartu
- [ ] PHPUnit: API + settings
- [ ] Vitest: FooterNewsletter komponent

## Súvisiace

- [ITERATION_52.md](ITERATION_52.md) — kontakt a company settings
- [user/ADMIN_GUIDE.md](user/ADMIN_GUIDE.md)
- [architecture/API.md](architecture/API.md)
