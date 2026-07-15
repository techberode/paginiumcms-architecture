# Local development – PaginiumCMS

Run the app as a full stack on your machine (no nginx required).

## 1. Backend (PHP 8.5+)

```bash
cd backend
php -S localhost:8080 -t public
```

Verify:

```bash
curl http://localhost:8080/api/test
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

- Admin: `/login` → `/dashboard`
- Public site: `/` (home, blog, pages from flat-file storage)
- Vite proxies `/api` → `http://localhost:8080`

## 3. Tests

```bash
# Backend (437+ tests)
./vendor/bin/phpunit

# Frontend (60+ tests)
cd frontend && npm test

# Production build check
cd frontend && npm run build
```

## 4. Integration smoke (BE)

`backend/tests/Http/ApplicationFlowTest.php` covers:

- Public endpoints (`/api/test`, navigation, pages, settings)
- Contact form → admin inbox
- Comment submit → admin approve
- Navigation update → public read
- Protected routes return 401 without session

## Troubleshooting

| Symptom | Fix |
|--------|-----|
| API returns HTML in browser | Use Vite dev server (3025), not `file://` or static `dist/` without nginx |
| 401 on admin after login | Check session cookies; same host for FE and API in dev (proxy handles this) |
| Infinite loading in Media/Nav | Fixed: stable `useToast` + no `toast` in `useCallback` deps |

For production deploy with one host, see [NGINX_API.md](./NGINX_API.md).
