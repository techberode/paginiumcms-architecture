---
title: Nginx reverse proxy and static frontend
description: Same-origin API proxy, SPA fallback, storage routes, security headers, trusted proxy, and vhost validation
icon: simple/nginx
---

# Nginx reverse proxy and static frontend

## 1. Reference topology

```text
browser
  ├─ /, /assets/*, /.well-known/security.txt → host nginx → frontend/dist
  ├─ /api/*                                 → host nginx → Docker nginx/PHP API
  ├─ /storage/*                             → PHP-controlled storage route
  └─ /feed.xml, /sitemap.xml, /robots.txt   → public PHP endpoints
```

The key principle is **same-origin**. The public frontend and API use the same scheme, host, and port. This reduces CORS complexity and preserves the session/CSRF model.

## 2. Location ordering

API, storage, and exact public endpoints must appear before the SPA fallback:

```nginx
location /api/ { ... }
location /storage/ { ... }
location = /feed.xml { ... }
location = /sitemap.xml { ... }
location = /robots.txt { ... }
location / { try_files $uri $uri/ /index.html; }
```

If `/api/` falls into `location /`, nginx returns `index.html` with HTTP 200 instead of JSON. The frontend then reports a parsing error while routing is the real problem.

## 3. `proxy_pass` contract

The retained profile uses:

```nginx
location /api/ {
    proxy_pass http://paginium_prod_php;
}
```

Without a trailing URI after the upstream, the original `/api/...` path is preserved. Changing it to `proxy_pass http://upstream/;` can rewrite the prefix. Verify every change against concrete API endpoints.

Recommended proxy headers:

```nginx
proxy_set_header Host $host;
proxy_set_header X-Real-IP $remote_addr;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header X-Forwarded-Host $host;
proxy_set_header X-Forwarded-Port $server_port;
proxy_set_header Connection "";
```

The backend must trust only actual proxy addresses.

## 4. Trusted proxies and client IP

`TRUSTED_PROXIES` is a security boundary. Trusting arbitrary clients allows spoofed `X-Forwarded-For`, distorted audit records, or IP rate-limit bypass.

Inspect the topology from the backend perspective:

```bash
ss -ltnp | grep -E '8089|8091'
docker compose ps
docker compose exec -T php env | grep TRUSTED_PROXIES
```

Do not add whole public or LAN ranges merely because they are “internal.” Add only the hop that actually terminated the proxy.

## 5. CORS and sessions

In a correct same-origin deployment the browser does not need cross-origin CORS for the normal admin flow. `APP_URL` must exactly match the public URL.

Diagnostic login with Origin:

```bash
curl -i -X POST https://demo.paginiumcms.com/api/auth/login \
  -H 'Origin: https://demo.paginiumcms.com' \
  -H 'Content-Type: application/json' \
  -d '{"email":"invalid@example.com","password":"invalid"}'
```

Expect JSON with the correct status, not an empty HTML `401`. Demo specifics are in [DEMO_DEPLOY.md](./DEMO_DEPLOY.md) and [ISS-098](../ISSUES.md#iss-098).

## 6. Static frontend and caching

Hashed Vite assets may use a long immutable cache:

```nginx
location /assets/ {
    try_files $uri =404;
    expires 30d;
    add_header Cache-Control "public, immutable" always;
}
```

`index.html` must not receive the same long immutable cache because it references new hashed files after deployment. Build a new `dist` and swap it as a unit.

## 7. Storage route

`/storage/` remains proxied to the application. Host nginx must not automatically expose the complete `backend/storage`, which includes private data, indexes, logs, backups, and secrets.

```text
public media request
→ PHP route
→ path canonicalization
→ ACL/authorization for the resource
→ allowed file
```

An nginx alias to the entire storage root would violate the Core storage contract.

## 8. Security headers

The artifacts provide separate HTTP/LAN and HTTPS snippets. Important nginx behavior:

> A `location` that sets its own `add_header` may stop inheriting headers from server scope.

Therefore `/assets/`, `security.txt`, and the SPA fallback explicitly include the snippet.

HTTPS baseline:

- HSTS,
- CSP,
- `X-Frame-Options: DENY`,
- `X-Content-Type-Options: nosniff`,
- Referrer Policy,
- Permissions Policy.

The current CSP includes `style-src 'unsafe-inline'` for the current frontend profile. Future tightening requires UI regression testing.

## 9. HSTS and preload

The updated snippet conservatively enables:

```text
Strict-Transport-Security: max-age=31536000
```

Add `includeSubDomains` and `preload` only after confirming that:

- all relevant subdomains support HTTPS,
- no HTTP-only internal or legacy host exists under the domain,
- certificate renewal and recovery are reliable,
- the owner knowingly accepts the long-lived preload commitment.

HSTS is never sent by the HTTP-only LAN configuration.

## 10. `security.txt`

The build copies the file into:

```text
frontend/dist/.well-known/security.txt
```

The exact location must precede the SPA fallback:

```nginx
location = /.well-known/security.txt {
    default_type text/plain;
    try_files $uri =404;
}
```

Smoke:

```bash
curl -fsSI https://paginiumcms.com/.well-known/security.txt
curl -fsS https://paginiumcms.com/.well-known/security.txt
```

Related to [ISS-118](../ISSUES.md#iss-118).

## 11. TLS and ACME

The HTTP vhost retains `/.well-known/acme-challenge/` and redirects other requests to HTTPS. Before activating a certificate verify:

```bash
sudo nginx -t
curl -I http://paginiumcms.com/.well-known/acme-challenge/test
```

Certificate paths in the artifacts are Certbot reference paths. Adapt them for another ACME client without placing API credentials in nginx configuration.

## 12. Production, demo, and LAN

| Artifact | Purpose | Internet-facing |
|---|---|---|
| `nginx-paginiumcms.com.conf` | production HTTPS vhost | yes |
| `nginx-demo.paginiumcms.com.conf` | isolated demo | yes |
| `nginx-paginium-test.conf` | static LAN test | no |
| `nginx-paginium-dev.conf` | Vite HMR LAN proxy | no |
| `nginx-security-headers-https.conf` | HTTPS snippet | yes |
| `nginx-security-headers-http.conf` | HTTP LAN snippet | no |

If IPv6 is disabled on the host, remove or comment the `listen [::]...` directives; do not enable IPv6 merely by copying a template.

## 13. Configuration validation

Before reload:

```bash
sudo nginx -T > /tmp/nginx-effective.conf
sudo nginx -t
sudo systemctl reload nginx
```

After reload:

```bash
curl -fsSI https://paginiumcms.com/
curl -fsS https://paginiumcms.com/api/health
curl -fsSI https://paginiumcms.com/assets/<known-asset>
curl -fsSI https://paginiumcms.com/.well-known/security.txt
```

Verify headers:

```bash
curl -sI https://paginiumcms.com/ | \
  grep -iE 'strict-transport|content-security|x-frame|content-type-options'
```

## 14. Common failures

| Symptom | Cause | Resolution |
|---|---|---|
| API returns HTML | SPA fallback caught `/api` | fix location/order |
| `502` | upstream is down or starting | local curl, `stack.sh ps`, health loop |
| incorrect client IP | proxy headers/trust | audit proxy hops |
| login works without Origin but not in browser | APP_URL/CORS | Origin smoke test |
| static content lacks security headers | nginx `add_header` inheritance | include snippet in location |
| stale frontend after deploy | cache or non-atomic `dist` | asset hashes and atomic swap |
| duplicate upstream | backup file in `sites-enabled` | retain only active symlink |
| Certbot challenge fails | challenge location/root | test HTTP challenge path |

## 15. Related documents

- [DEPLOY.md](./DEPLOY.md)
- [DEV.md](./DEV.md)
- [DEMO_DEPLOY.md](./DEMO_DEPLOY.md)
- [FIREWALL.md](../user/FIREWALL.md)
- [SECURITY.md](../developer/SECURITY.md)
