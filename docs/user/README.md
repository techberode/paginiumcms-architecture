# Príručka používateľa PaginiumCMS

> Kompletný návod pre beta testerov, editorov a administrátorov — od inštalácie po každodennú prácu s CMS.

---

## Ako čítať túto príručku

| Krok | Dokument | Pre koho |
|------|----------|----------|
| 1 | **[INSTALLATION.md](INSTALLATION.md)** | Kto CMS prvýkrát nasadí (Docker, hosting, lokálne) |
| 2 | **[FIRST_STEPS.md](FIRST_STEPS.md)** | Prvé prihlásenie, 2FA, dashboard, prvý obsah |
| 3 | **[ADMIN_GUIDE.md](ADMIN_GUIDE.md)** | Celá administrácia — moduly, roly, bežné úlohy |
| 4 | Špecializované kapitoly | Podľa potreby (editor, firewall, logy…) |

**Metafora projektu:** React admin = **hosť v reštaurácii**, API = **čašník**, PHP backend = **kuchár**. Všetko, čo uvidíš v admin paneli, musí prejsť cez API — backend je jediný zdroj pravdy.

---

## Špecializované kapitoly

| Téma | Súbor |
|------|--------|
| Editor stránok a článkov (SEO, médiá, WYSIWYG) | [CONTENT_EDITOR.md](CONTENT_EDITOR.md) |
| Logo a favicon | [BRANDING.md](BRANDING.md) |
| Oprávnenia rolí a Path ACL | [ACCESS_CONTROL.md](ACCESS_CONTROL.md) |
| Code Editor (úprava PHP/TS z adminu) | [CODE_EDITOR.md](CODE_EDITOR.md) |
| Developer Mode (TOTP unlock) | [DEVELOPER_MODE.md](DEVELOPER_MODE.md) |
| Firewall / WAF | [FIREWALL.md](FIREWALL.md) |
| Logy a HTTP access | [LOGGING.md](LOGGING.md) |
| Doplnky (plugins) | [PLUGINS.md](PLUGINS.md) |
| Témy (budúcnosť) | [THEMES.md](THEMES.md) |

---

## Technická dokumentácia (vývojári)

| Téma | Súbor |
|------|--------|
| Lokálne prostredie + Docker | [developer/LOCAL_SETUP.md](../developer/LOCAL_SETUP.md) |
| API | [architecture/API.md](../architecture/API.md) |
| Nasadenie nginx | [deploy/NGINX_API.md](../deploy/NGINX_API.md) |

---

## Roly v systéme

| Rola | Prístup do adminu | Typické úlohy |
|------|-------------------|---------------|
| **USER** | ❌ Nie | Len verejný web (ak je registrovaný) |
| **EDITOR** | ✅ Áno | Stránky, články, médiá, navigácia |
| **ADMIN** | ✅ Rozšírený | + používatelia, nastavenia, firewall, zálohy |
| **SUPER_ADMIN** | ✅ Plný | + demo reset, oprávnenia rolí (Path ACL), extensions, blueprints |

Detail rolí a oprávnení: [ADMIN_GUIDE.md § Roly](ADMIN_GUIDE.md#roly-a-oprávnenia).

---

## Beta test — rýchly checklist

1. [ ] Inštalácia podľa [INSTALLATION.md](INSTALLATION.md)
2. [ ] `./scripts/first-run.sh` + prihlásenie adminom
3. [ ] Zapnutie 2FA v **Účet → Bezpečnosť**
4. [ ] Vytvorenie testovacej stránky a článku
5. [ ] Nahratie obrázka v **Médiá** a kontrola na verejnom webe
6. [ ] Prehľad **Nastavenia → Stránka → Logo a favicon** ([BRANDING.md](BRANDING.md))
7. [ ] Pri probléme: **Logy**, **Audit**, prípadne [ISSUES.md](../ISSUES.md) pre známe incidenty
