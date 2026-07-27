# Nginx – API proxy for PaginiumCMS

When the React SPA and PHP backend share one host, **nginx must proxy `/api` to PHP** before the SPA fallback. Otherwise `/api/*` returns `index.html` and the frontend cannot reach the backend.

> **Note:** A legacy prototype was once tested on `paginiumcms.com` (old `/backend/v1/*` scripts + mock SPA). That host is **not** the deployment target for this repo — it was only a visual/functional reference. Use your own domain or LAN IP below.

## Symptom

```bash
curl http://YOUR_HOST/api/test
# Returns HTML (SPA) instead of JSON
```

## Fix (example)

Replace `YOUR_HOST` with your server name or LAN IP (e.g. `192.168.1.50`, `cms.local`).

```nginx
server {
    listen 80;
    server_name YOUR_HOST;

    root /var/www/paginiumcms/frontend/dist;
    index index.html;

    # PHP backend (Docker on port 8080 or php-fpm socket)
    location /api {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Static media from flat-file storage
    location /storage {
        proxy_pass http://127.0.0.1:8080;
    }

    # Public XML feeds (Iteration 22 — must be BEFORE SPA fallback)
    location = /feed.xml {
        proxy_pass http://127.0.0.1:8080/feed.xml;
    }

    location = /sitemap.xml {
        proxy_pass http://127.0.0.1:8080/sitemap.xml;
    }

    location = /robots.txt {
        proxy_pass http://127.0.0.1:8080/robots.txt;
    }

    # SPA fallback (must be AFTER /api and feed routes)
    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

## Frontend build

For production on the **same host** as the API, leave `VITE_API_URL` empty so the client uses `window.location.origin`:

```env
# .env.production
VITE_API_URL=
```

For local dev, Vite proxy handles `/api` → `localhost:8080` (see `frontend/vite.config.ts`).

After deploy, verify:

```bash
curl http://YOUR_HOST/api/test
# {"success":true,"data":{"status":"ok",...}}
```

## Local dev (no nginx)

```bash
# Terminal 1 – backend
cd backend && php -S localhost:8080 -t public   # or Docker on :8080

# Terminal 2 – frontend (proxy /api → :8080)
cd frontend && npm run dev
```

Open `http://localhost:3025` — API calls go through the Vite proxy.

## LAN test layout (split hosts)

| Role | Host | Port |
|------|------|------|
| React SPA (nginx) | `192.168.10.26` | `8081` |
| PHP backend (Docker) | `192.168.10.20` | `8080` |
| Vite dev (local only) | `localhost` | `3025` |

Use the full server block in [`nginx-paginium-test.conf`](./nginx-paginium-test.conf) on **`.26`**.  
For **production demo** host `demo.paginiumcms.com` (Docker upstream = stack `BACKEND_PORT`, typically `8091`), use [`nginx-demo.paginiumcms.com.conf`](./nginx-demo.paginiumcms.com.conf).  
Frontend build must have empty `VITE_API_URL` (same-origin `/api`).

```bash
# On dev machine — upload dist to nginx host
./scripts/deploy-frontend-lan.sh

# Smoke test
curl http://192.168.10.26:8081/api/health
curl -I http://192.168.10.26:8081/feed.xml
```

If backend and nginx share one VM, change upstream to `127.0.0.1:8080` (comment at bottom of the conf file).

### Session cookies on HTTP (LAN)

`backend/bootstrap/session.php` sets `session.cookie_secure` only when the request is HTTPS
(or `X-Forwarded-Proto: https` from nginx). Plain `http://192.168.x.x` LAN tests work without
Secure cookies being dropped by the browser.

After deploy, clear site cookies and hard-refresh before testing login.

### Backend `.env` on LAN (recommended)

```env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://192.168.10.26:8081
DEVELOPER_MODE=true
DEV_UNLOCK_SECRET=change-me-local-dev-secret
# optional explicit list (wildcards below cover LAN when APP_ENV != production)
CORS_ALLOWED_ORIGINS=http://192.168.10.26:8081,http://192.168.10.26:3025
TRUSTED_PROXIES=127.0.0.1,::1,192.168.10.26
```

**Developer Mode / Code Editor:** `DEVELOPER_MODE=true` (or `APP_DEBUG=true`, or `APP_ENV=development`) must be set on the **PHP backend** host (`192.168.10.20:8080`), not only on the nginx SPA host. Without it, `/api/admin/developer/unlock` returns 403 before TOTP is checked. Restart PHP/Docker after editing `.env`.

When `APP_ENV` is not `production`, backend also allows CORS from `http://192.168.*`, `http://localhost:*` (Vite **:3025**), etc.

Restart PHP / Docker after changing `.env` or pulling code.
