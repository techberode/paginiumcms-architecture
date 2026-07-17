# Local development – PaginiumCMS

Run the app as a full stack on your machine (no nginx required).

## 1. Backend (PHP 8.5+)

```bash
cd backend/public
php -S localhost:8080
```

Verify:

```bash
curl http://localhost:8080/api/test
curl http://localhost:8080/api/health
```

## 2. Frontend (Vite + proxy)

In a second terminal:

```bash
cd frontend
cp .env.example .env   # optional; leave VITE_API_URL empty
npm install
npm run dev
```

Open **http://localhost:3025**

- Admin: `/login` → `/dashboard` (requires EDITOR+ role via `AdminRoleGuard`)
- Public site: `/` (home, blog, pages from flat-file storage)
- Preview draft: `/preview/{slug}` (auth + staff role)
- Developer logs: `/developer/logs` (dev mode unlocked)

**Vite proxies:**

| Path | Target |
|------|--------|
| `/api` | `http://localhost:8080` |
| `/storage` | `http://localhost:8080` (media files, It. 20) |

## 3. Tests

```bash
# Backend (488 tests, PHPStan L8)
./vendor/bin/phpunit
./vendor/bin/phpstan analyse backend --level=8

# Frontend
cd frontend && npm test

# Production build check
cd frontend && npm run build
```

## 4. CLI commands

```bash
php backend/bin/console audit:run
php backend/bin/console backup:run-schedule   # cron: checks schedule.json
```

## 5. Integration smoke (BE)

`backend/tests/Http/ApplicationFlowTest.php` covers:

- Public endpoints (`/api/test`, navigation, pages, settings)
- Contact form → admin inbox
- Comment submit → admin approve
- Navigation update → public read
- Protected routes return 401 without session

`backend/tests/Http/Controllers/CoreHardeningTest.php` (It. 20):

- RBAC 403 for USER on content create
- Maintenance mode 503 + health exempt
- Registration toggle
- `/storage` serving + path traversal block

## Troubleshooting

| Symptom | Fix |
|--------|-----|
| API returns HTML in browser | Use Vite dev server (3025), not `file://` or static `dist/` without nginx |
| 401 on admin after login | Check session cookies; proxy must forward credentials |
| Media images 404 | Ensure backend serves `/storage/...` or nginx alias; Vite proxy includes `/storage` |
| 503 on public API | Check `general.maintenanceMode` in settings |
| USER cannot access admin | Expected — only EDITOR/ADMIN/SUPER_ADMIN (`AdminRoleGuard`) |

For production deploy with one host, see [NGINX_API.md](./NGINX_API.md).

## LAN test server (192.168.10.x)

Ready-made config for the split setup (SPA on `.26:8081`, PHP on `.20:8080`):

- **File:** [`nginx-paginium-test.conf`](./nginx-paginium-test.conf)
- **Deploy script:** [`../../scripts/deploy-frontend-lan.sh`](../../scripts/deploy-frontend-lan.sh)

Quick install on the nginx host:

```bash
sudo cp docs/deploy/nginx-paginium-test.conf /etc/nginx/sites-available/paginium-test
sudo ln -sf /etc/nginx/sites-available/paginium-test /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
curl -s http://192.168.10.26:8081/api/health
```
