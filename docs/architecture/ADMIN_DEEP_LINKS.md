# Admin deep links (FE routes + query params)

Dokument popisuje zdieľateľné URL v administrácii PaginiumCMS — prepojenie sidebaru, dashboard panelov, modulov a command palette (Ctrl+K).

## Konvencia

| Typ | Formát | Príklad |
|-----|--------|---------|
| Modul | `/cesta` | `/media`, `/comments` |
| Nastavenia — skupina | `/settings?group={key}` | `/settings?group=logging` |
| Logy — filter | `/logs?severity={level}` | `/logs?severity=critical` |
| Audit — obsah | `/audit/content/{contentId}` | `/audit/content/page-home` |
| Audit — používateľ | `/audit/user/{userId}` | `/audit/user/editor-1` |
| Zoznamy (It.44) | query params | `/pages?q=foo&page=2`, `/media?folder=hero&type=image` |

Skupiny nastavení zodpovedajú kľúčom v `SettingsSchema.php` (`general`, `branding`, `accessControl`, `smtp`, `connectors`, `scheduler`, `firewall`, `logging`, `codePolicy`, …).

| Skupina | Deep link |
|---------|-----------|
| Oprávnenia rolí (SUPER_ADMIN) | `/settings?category=security&group=accessControl` |
| Logo a favicon | `/settings?category=site&group=branding` |
| Logy | `/settings?group=logging` |

## Frontend

| Súbor | Úloha |
|-------|-------|
| `frontend/src/utils/adminDeepLinks.ts` | Helpery `settingsGroupPath`, `logsSeverityPath`, `auditContentPath`, `auditUserPath` |
| `frontend/src/components/backend/SettingsView.tsx` | Číta `?group=` (+ legacy `location.state.group`), pri prepnutí tabu syncuje URL |
| `frontend/src/components/Audit/AuditTrail.tsx` | `useParams()` pre `/audit/content/:contentId` a `/audit/user/:userId` |
| `frontend/src/components/backend/LogsManager.tsx` | Sync `?severity=` obojsmerne (dashboard chipy + browser back) |

### Opravené vstupné body

| Zdroj | Cieľ |
|-------|------|
| Dashboard → Logy (severity chip) | `/logs?severity=…` |
| Dashboard → Logy panel | `/logs` |
| LogsManager → Nastavenia logov | `/settings?group=logging` |
| FirewallManager → Nastavenia firewallu | `/settings?group=firewall` |
| SchedulerView → Job scheduler | `/settings?group=scheduler` |
| NotificationsOverview → SMTP & konektory | `/settings?group=smtp` (konektory: `?group=connectors`) |

## Backend

| Súbor | Úloha |
|-------|-------|
| `backend/app/Core/Search/AdminRouteCatalog.php` | Statické trasy pre Ctrl+K (musí sedieť so `AdminSidebar.tsx`) |
| `backend/app/Http/Routes/audittrail.php` | API: `GET /api/admin/audit/content/{contentId}`, `…/user/{userId}` |

Command palette vracia `adminPath` — navigácia môže obsahovať query string (napr. `/settings?group=logging` ak sa v budúcnosti pridá do katalógu).

## Testy

- `frontend/src/utils/adminDeepLinks.test.ts`
- `frontend/src/components/backend/SettingsView.test.tsx`
- `frontend/src/components/Audit/AuditTrail.test.tsx`

Spustenie: `cd frontend && npm test -- adminDeepLinks SettingsView AuditTrail`

## Súvisiace dokumenty

- [SETTINGS.md](./SETTINGS.md) — skupiny nastavení a API
- [FRONTEND.md](./FRONTEND.md) — admin SPA a routovanie
- `docs/CONTENT_COMMENTS_NAV.md` — URL sync zoznamov (It.44)
