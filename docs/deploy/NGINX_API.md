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

    # SPA fallback (must be AFTER /api)
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
