---
title: Logs and Audit
description: Operational logs, request correlation, retention, data redaction, and safe diagnostics
icon: material/text-box-search
---

# Logging — administrator guide

> Logs explain system behavior. They are not authoritative content, must not contain secrets, and do not replace domain audit.

## 1. Record types

| Source | Purpose |
|---|---|
| application | errors and meaningful runtime events |
| HTTP/request | method, path, status, duration, and correlation metadata |
| audit | who performed a meaningful change against which target |
| security/event | lockout, WAF, backup, worker, and system events |
| user/activity | user activity only within allowed scope |

Verify concrete directories and source names in the release. Every log directory must be outside direct web access.

## 2. Request log

A safe request record typically contains:

- UTC timestamp,
- method and normalized path,
- status,
- `duration_ms`,
- client IP according to trusted-proxy policy,
- user ID or service principal when authenticated,
- request/correlation ID,
- route name or category.

It must not contain full session cookies, Authorization bearer values, CSRF tokens, passwords, TOTP, API keys, or complete sensitive bodies.

## 3. Severity

| Level | Use |
|---|---|
| `debug` | local diagnostics; limited in production |
| `info` | normal meaningful event |
| `warning` | expected problem, 4xx pattern, slow request |
| `error` | one operation failed |
| `critical` | loss of availability, integrity, or a major capability |

Not every 404 deserves long-term warning retention. A WAF probe may be a security event, while an ordinary missing page should not flood the error log.

## 4. Audit versus request log

Audit should answer:

```text
who → did what → to which target → when → with what result
```

Request logs describe HTTP execution. One administrator action may use multiple requests but produce one domain audit record. Audit export must protect against CSV/formula injection and redact sensitive context.

## 5. Authentication endpoints

Login and password reset are sensitive. Even when request logging is enabled, record only defensive metadata:

- result and reason code,
- anonymized/normalized identifier according to policy,
- IP and user agent within a reasonable scope,
- request ID,
- rate-limit/lockout state.

Never log passwords, reset tokens, TOTP codes, recovery codes, or a complete OAuth callback query.

## 6. Data redaction

A centralized sanitizer should redact names such as:

```text
password, pass, secret, token, api_key, authorization,
cookie, csrf, totp, recovery_code, private_key
```

Name-based redaction is not enough. Log messages must remove CR/LF/ANSI injection and cap unknown input length. Binary uploads and complete content bodies should not be logged.

## 7. Request ID and correlation

Generate or validate a safe request ID for every request. Return it in a response header/envelope, write it to logs, and use it for support. Do not trust an unchecked long client value; normalize its format or generate a new ID.

A background job has its own job ID and may carry a parent request ID. This enables tracing save → event → Git/translation/notification without mixing identities.

## 8. Log administration UI

The UI may filter by severity, source, time, full text, and archived state. Bulk archive/delete must:

- require a privileged role and CSRF,
- show record count,
- confirm irreversible deletion,
- handle partial failure,
- write an audit event without embedding all deleted log content.

“Delete all” is not a normal diagnostic action. Preserve evidence before incident response.

## 9. Retention and rotation

Set retention according to capacity, privacy, and incident needs. Daily JSON files or ring buffers need atomic rotation. Purge must not delete an active file in a way that corrupts the writer.

Separate log backups from authoritative-content backups; you do not need to retain debug logs forever merely because the CMS is backed up.

## 10. Time and timezone

Store UTC and render the user timezone in UI. Every report should include timezone or an ISO timestamp. Unsynchronized time breaks TOTP, correlation, scheduler, and incident timelines; the server must use NTP.

## 11. Slow requests and performance

`slowRequestMs` is a diagnostic threshold, not proof of a security incident. Correlate route, storage I/O, lock wait, outbound provider, and worker load. Do not enable Redis automatically because of one slow request; Performance Guard must pass a capability test.

## 12. External log shipping

Loki/syslog/SIEM export is an environment-specific operations integration. Before sending:

- use TLS or a trusted local transport,
- redact secrets before data leaves the application,
- define retry/backpressure,
- ensure provider failure does not block content save,
- respect retention and personal-data jurisdiction.

## 13. Diagnostic procedure

1. record time, user, action, and release,
2. obtain request ID from UI/network panel,
3. filter request/application logs,
4. correlate audit and security event,
5. inspect worker/provider logs for async actions,
6. redact excerpts before sharing,
7. add a regression test or alert after the fix.

## 14. Common problems

| Symptom | Check |
|---|---|
| IP is always the proxy | `TRUSTED_PROXIES` and forwarded headers |
| request is missing | minimum severity, request logging, auth exclusion, disk permissions |
| log grows too quickly | 404/bot noise, debug, retention, duplicate middleware |
| JSON is corrupt | concurrent write, missing lock/atomic rename |
| UI parse error | invalid line; preserve file and isolate the damaged record |
| secrets appear in logs | incident: restrict access, rotate secret, fix sanitizer, purge according to policy |

## 15. Related documents

- [Firewall](FIREWALL.md)
- [API contract](../architecture/API_CONTRACT.md)
- [Core hardening](../architecture/CORE_HARDENING.md)
- [Administrator guide](ADMIN_GUIDE.md)
