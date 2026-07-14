I'll create a comprehensive Markdown file for you. However, I don't have a tool to write directly to a file system. I'll provide the complete Markdown content here that you can copy and save as `API.md`.

```markdown
# PaginiumCMS - API & Frontend Documentation

> **Version:** 2.0.0  
> Complete reference for backend API endpoints and frontend architecture

---

## 📋 Table of Contents

1. [API Endpoints (Backend)](#1-api-endpoints-backend)
   - [1.1 Public Endpoints](#11-public-endpoints-no-authentication)
   - [1.2 Protected Endpoints](#12-protected-endpoints-require-login)
2. [Frontend Structure](#2-frontend-structure)
   - [2.1 Directory Structure](#21-directory-structure)
   - [2.2 Main Screens (Routes)](#22-main-screens-routes)
   - [2.3 Key Frontend Features](#23-key-frontend-features)
   - [2.4 Environment Variables](#24-environment-variables)
3. [Endpoint ↔ Frontend Component Mapping](#3-endpoint--frontend-component-mapping)

---

## 1. API Endpoints (Backend)

### 1.1 Public Endpoints (No Authentication)

| Method | Endpoint | Description | Example Response |
|:------|:---------|:------------|:-----------------|
| `GET` | `/` | API information | `{"name": "PaginiumCMS API", "version": "2.0.0", "status": "running"}` |
| `GET` | `/api/health` | Health check | `{"status": "healthy", "version": "2.0.0"}` |
| `POST` | `/api/auth/login` | User login | `{"success": true, "user": {...}, "requires_two_factor": false}` |
| `POST` | `/api/auth/register` | New user registration | `{"success": true, "message": "Registration successful"}` |
| `POST` | `/api/auth/reset-password` | Password reset (token sending) | `{"success": true, "token": "reset-token-..."}` |
| `POST` | `/api/auth/verify-reset-token` | Verify reset token & set new password | `{"success": true, "message": "Password was successfully changed"}` |
| `GET` | `/api/auth/csrf-token` | Get CSRF token | `{"token": "...", "key": "default"}` |

### 1.2 Protected Endpoints (Require Login)

#### 🔐 Authentication & User

| Method | Endpoint | Description | Notes |
|:------|:---------|:------------|:------|
| `POST` | `/api/auth/logout` | User logout | - |
| `POST` | `/api/auth/change-password` | Change password | Requires `old_password` and `new_password` |
| `GET` | `/api/auth/me` | Get current user | - |

#### 🔒 Two-Factor Authentication (2FA)

| Method | Endpoint | Description |
|:------|:---------|:------------|
| `POST` | `/api/auth/2fa/enable` | Enable 2FA |
| `POST` | `/api/auth/2fa/disable` | Disable 2FA |
| `POST` | `/api/auth/2fa/verify` | Verify TOTP code |
| `GET` | `/api/auth/2fa/qr-code` | Get QR code for 2FA setup |
| `GET` | `/api/auth/2fa/status` | 2FA status (enabled/disabled) |
| `POST` | `/api/auth/2fa/verify-login` | Verify TOTP code during login |

#### 📄 Content (Pages & Articles)

**Pages:**

| Method | Endpoint | Description |
|:------|:---------|:------------|
| `GET` | `/api/pages` | Get all pages |
| `GET` | `/api/pages/{slug}` | Get specific page by slug |
| `POST` | `/api/pages` | Create new page |
| `PUT` | `/api/pages/{slug}` | Update page |
| `DELETE` | `/api/pages/{slug}` | Delete page |

**Articles:**

| Method | Endpoint | Description |
|:------|:---------|:------------|
| `GET` | `/api/articles` | Get all articles |
| `GET` | `/api/articles/{slug}` | Get specific article by slug |
| `POST` | `/api/articles` | Create new article |
| `PUT` | `/api/articles/{slug}` | Update article |
| `DELETE` | `/api/articles/{slug}` | Delete article |

#### 👑 Admin Endpoints (Require ADMIN Role)

**User Management** (vyžaduje ADMIN + TOTP ak je 2FA zapnutá):

| Method | Endpoint | Description |
|:------|:---------|:------------|
| `GET` | `/api/admin/users` | List users |
| `GET` | `/api/admin/users/{id}` | User detail |
| `POST` | `/api/admin/users` | Create user (password required) |
| `PUT` | `/api/admin/users/{id}` | Update user (name, email, role, optional password) |
| `DELETE` | `/api/admin/users/{id}` | Delete user |

**Auth / 2FA** (session cookie):

| Method | Endpoint | Description |
|:------|:---------|:------------|
| `POST` | `/api/auth/login` | Login (returns `requires_two_factor` if 2FA enabled) |
| `POST` | `/api/auth/2fa/verify-login` | Complete login with TOTP code |
| `GET` | `/api/auth/me` | Current user (`{ success, user }`) |
| `POST` | `/api/auth/2fa/enable` | Enable 2FA (returns secret) |
| `POST` | `/api/auth/2fa/disable` | Disable 2FA |
| `POST` | `/api/auth/2fa/verify` | Verify TOTP during setup |
| `GET` | `/api/auth/2fa/qr-code` | QR code for authenticator app |
| `GET` | `/api/auth/2fa/status` | 2FA status for current user |

**Backups:**

| Method | Endpoint | Description |
|:------|:---------|:------------|
| `GET` | `/api/admin/backups` | List backups |
| `POST` | `/api/admin/backups` | Create new backup |
| `GET` | `/api/admin/backups/{id}/download` | Download backup |
| `POST` | `/api/admin/backups/{id}/restore` | Restore backup |
| `DELETE` | `/api/admin/backups/{id}` | Delete backup |

**Audit Trail:**

| Method | Endpoint | Description |
|:------|:---------|:------------|
| `GET` | `/api/admin/audit/content/{contentId}` | Audit trail for specific content |
| `GET` | `/api/admin/audit/user/{userId}` | Audit trail for user |
| `GET` | `/api/admin/audit/stats` | Audit statistics |
| `GET` | `/api/admin/audit/export` | Export audit to CSV |
| `POST` | `/api/admin/audit/log` | Manual event logging |

**Code Editor:**

| Method | Endpoint | Description |
|:------|:---------|:------------|
| `GET` | `/api/admin/code-editor/files` | List files |
| `GET` | `/api/admin/code-editor/file` | File content |
| `POST` | `/api/admin/code-editor/save` | Save file |
| `GET` | `/api/admin/code-editor/backups` | File backups |

**Versioning:**

| Method | Endpoint | Description |
|:------|:---------|:------------|
| `GET` | `/api/admin/versions/{contentId}` | Version history |
| `GET` | `/api/admin/versions/{contentId}/{version}` | Specific version |
| `POST` | `/api/admin/versions/restore` | Restore version |
| `GET` | `/api/admin/versions/compare` | Compare two versions |
| `GET` | `/api/admin/versions/stats` | Versioning statistics |
| `DELETE` | `/api/admin/versions/{contentId}` | Clean up old versions |

---

## 2. Frontend Structure

### 2.1 Directory Structure

```text
frontend/
├── src/
│   ├── api/                      # API communication
│   │   ├── client.ts             # Axios configuration
│   │   ├── auth.ts               # Authentication API
│   │   ├── content.ts            # Pages & Articles API
│   │   ├── codeEditor.ts         # Code Editor API
│   │   ├── backup.ts             # Backup API
│   │   ├── audit.ts              # Audit API
│   │   ├── health.ts             # Health API
│   │   ├── version.ts            # Versioning API
│   │   ├── user.ts               # User API
│   │   └── types.ts              # TypeScript types
│   │
│   ├── components/               # React components
│   │   ├── frontend/             # Public components
│   │   │   ├── LoginModal.tsx    # Login modal
│   │   │   ├── Navbar.tsx        # Navigation bar
│   │   │   └── CMSBar.tsx        # CMS bar
│   │   │
│   │   ├── backend/              # Admin components
│   │   │   ├── DashboardView.tsx # Main dashboard
│   │   │   ├── PagesManager.tsx  # Page management
│   │   │   ├── BackupManager.tsx # Backup management
│   │   │   ├── MarkdownEditor.tsx# Markdown editor
│   │   │   └── WysiwygEditor.tsx # WYSIWYG editor
│   │   │
│   │   ├── CodeEditor/           # Code Editor
│   │   │   ├── CodeEditor.tsx         # Main editor
│   │   │   ├── EditorToolbar.tsx      # Toolbar
│   │   │   ├── FileTree.tsx           # File tree
│   │   │   ├── VersionHistory.tsx     # Version history
│   │   │   └── DiffViewer.tsx         # Diff viewer
│   │   │
│   │   ├── Audit/                # Audit Trail
│   │   │   └── AuditTrail.tsx    # Audit display
│   │   │
│   │   └── layout/               # Layout components
│   │       └── ResponsiveLayout.tsx  # Responsive layout
│   │
│   ├── context/                  # React Context
│   │   ├── AuthContext.tsx       # Authentication
│   │   ├── ContentContext.tsx    # Content
│   │   ├── ThemeContext.tsx      # Theme (light/dark)
│   │   └── NotificationContext.tsx # Notifications
│   │
│   ├── hooks/                    # Custom hooks
│   │   ├── useApi.ts             # API calls
│   │   ├── useAuth.ts            # Authentication
│   │   ├── useContent.ts         # Content
│   │   ├── useMediaQuery.ts      # Responsiveness
│   │   └── useToast.ts           # Notifications
│   │
│   ├── types/                    # TypeScript types
│   │   └── cms.ts                # CMS types
│   │
│   ├── App.tsx                   # Main application
│   ├── main.tsx                  # Entry point
│   └── index.css                 # Global styles
│
├── index.html                    # HTML template
├── package.json                  # Dependencies
├── vite.config.ts                # Vite configuration
└── tailwind.config.js            # Tailwind CSS configuration
```

### 2.2 Main Screens (Routes)

| Path | Component | Description |
|:-----|:----------|:------------|
| `/` | → `/dashboard` | Redirect to dashboard |
| `/login` | `LoginModal` | Login screen |
| `/dashboard` | `DashboardView` | Main dashboard with statistics |
| `/pages` | `PagesManager` | List of all pages |
| `/pages/new` | `PagesManager` | Create new page |
| `/pages/:slug` | `PagesManager` | Edit existing page |
| `/articles` | `PagesManager` | List of all articles |
| `/articles/new` | `PagesManager` | Create new article |
| `/articles/:slug` | `PagesManager` | Edit existing article |
| `/code-editor` | `CodeEditor` | Code editor |
| `/backups` | `BackupManager` | Backup management |
| `/audit` | `AuditTrail` | Audit trail |
| `/settings` | `SettingsView` | System settings (schema-driven) |

### 2.3 Key Frontend Features

#### 🔐 Authentication (`AuthContext`)

```typescript
login(email, password)          // Login
logout()                        // Logout
register(email, password, name) // Registration
resetPassword(email)            // Password reset
verifyResetToken(token, newPassword) // Verify reset token
enable2FA()                     // Enable 2FA
disable2FA()                    // Disable 2FA
verify2FA(code)                 // Verify 2FA code
```

#### 📄 Content (`ContentContext`)

```typescript
getPages()                      // Get all pages
getPage(slug)                   // Get page
savePage(data)                  // Save page
deletePage(slug)                // Delete page
getArticles()                   // Get all articles
getArticle(slug)                // Get article
saveArticle(data)               // Save article
deleteArticle(slug)             // Delete article
```

#### 🔔 Notifications (`NotificationContext`)

```typescript
success(message)                // Success (green)
error(message)                  // Error (red)
warning(message)                // Warning (yellow)
info(message)                   // Info (blue)
```

### 2.4 Environment Variables (`.env`)

```env
# API URL (backend)
VITE_API_BASE_URL=http://localhost:3025

# Timeout for API calls (ms)
VITE_API_TIMEOUT=30000

# Environment
VITE_APP_ENV=development
```

---

## 3. Endpoint ↔ Frontend Component Mapping

| Backend Endpoint | Frontend Component | Action |
|:-----------------|:-------------------|:-------|
| `POST` `/api/auth/login` | `LoginModal` | Login |
| `POST` `/api/auth/register` | `LoginModal` | Registration |
| `POST` `/api/auth/logout` | `Navbar` | Logout |
| `POST` `/api/auth/2fa/verify-login` | `LoginModal` | Complete 2FA login |
| `GET` `/api/auth/me` | `AuthContext` | Load current user |
| `GET` `/api/pages` | `PagesManager` | Display pages |
| `POST` `/api/pages` | `PagesManager` | Create page |
| `PUT` `/api/pages/{slug}` | `PagesManager` | Update page |
| `DELETE` `/api/pages/{slug}` | `PagesManager` | Delete page |
| `GET` `/api/articles` | `PagesManager` | Display articles |
| `POST` `/api/articles` | `PagesManager` | Create article |
| `PUT` `/api/articles/{slug}` | `PagesManager` | Update article |
| `DELETE` `/api/articles/{slug}` | `PagesManager` | Delete article |
| `GET` `/api/admin/users` | `UsersManager` | List users |
| `POST` `/api/admin/users` | `UsersManager` | Create user |
| `PUT` `/api/admin/users/{id}` | `UsersManager` | Update user |
| `DELETE` `/api/admin/users/{id}` | `UsersManager` | Delete user |
| `POST` `/api/auth/2fa/enable` | `TwoFactorSettings` | Enable 2FA |
| `POST` `/api/auth/2fa/disable` | `TwoFactorSettings` | Disable 2FA |
| `POST` `/api/auth/2fa/verify` | `TwoFactorSettings` | Verify 2FA code |
| `GET` `/api/auth/2fa/qr-code` | `TwoFactorSettings` | QR code |
| `GET` `/api/admin/backups` | `BackupManager` | List backups |
| `POST` `/api/admin/backups` | `BackupManager` | Create backup |
| `GET` `/api/admin/backups/{id}/download` | `BackupManager` | Download backup |
| `POST` `/api/admin/backups/{id}/restore` | `BackupManager` | Restore backup |
| `DELETE` `/api/admin/backups/{id}` | `BackupManager` | Delete backup |
| `GET` `/api/admin/audit/content/{contentId}` | `AuditTrail` | Content audit |
| `GET` `/api/admin/audit/user/{userId}` | `AuditTrail` | User audit |
| `GET` `/api/admin/audit/stats` | `AuditTrail` | Audit statistics |
| `GET` `/api/admin/audit/export` | `AuditTrail` | Export audit |
| `GET` `/api/admin/code-editor/files` | `CodeEditor` | List files |
| `GET` `/api/admin/code-editor/file` | `CodeEditor` | Read file |
| `POST` `/api/admin/code-editor/save` | `CodeEditor` | Save file |
| `GET` `/api/admin/code-editor/backups` | `CodeEditor` | File backups |
| `GET` `/api/admin/versions/{contentId}` | `VersionHistory` | Version history |
| `POST` `/api/admin/versions/restore` | `VersionHistory` | Restore version |
| `GET` `/api/admin/versions/compare` | `DiffViewer` | Compare versions |
| `GET` `/api/health` | `App` | Health check |

---

## 📝 Notes

- All protected endpoints require a valid session cookie or JWT token
- CSRF protection is enforced for state-changing operations
- Admin endpoints require the `ADMIN` role
- 2FA setup requires scanning the QR code with an authenticator app (Google Authenticator, Authy, etc.)

---

*Last updated: Documentation v2.0.0*
```

---

## 4. Content Locking API (Iterácia 1)

Systém zamykania obsahu zabraňuje súbežnému prepísaniu toho istého dokumentu.
Stav je vo flat-file registri `backend/storage/app/content/data/locks.json`
(žiadna databáza). Heartbeat 30 s, auto-release po 5 min bez heartbeatu.

### 4.1 Endpointy

| Method | Endpoint | Auth | Popis |
|:------|:---------|:-----|:------|
| `POST` | `/api/locks/acquire` | prihlásený | Získa zámok; vráti `token` pre heartbeat/release |
| `POST` | `/api/locks/heartbeat` | prihlásený | Predĺži zámok (volané každých 30 s) |
| `POST` | `/api/locks/release` | prihlásený | Uvoľní zámok vlastníka |
| `GET` | `/api/locks` | ADMIN / SUPER_ADMIN | Zoznam všetkých aktívnych zámkov |
| `DELETE` | `/api/locks/{resourceId}` | ADMIN / SUPER_ADMIN | Vynútené uvoľnenie zámku |

### 4.2 Príklady

**Acquire** – `POST /api/locks/acquire`
```json
// request
{ "resourceId": "page:o-nas" }

// 201 response
{
  "success": true,
  "message": "Zámok získaný",
  "data": {
    "lock": { "resourceId": "page:o-nas", "lockedBy": "user_…", "lockedByName": "Ján Novák", "acquiredAt": 1752400000, "lastHeartbeat": 1752400000, "expiresAt": 1752400300 },
    "token": "9f2c…",
    "ttl": 300
  }
}
```

**Konflikt** – ak zdroj drží niekto iný, vráti `409`:
```json
{
  "success": false,
  "error": "Zdroj \"page:o-nas\" je uzamknutý používateľom Jana Kováčová.",
  "lock": { "resourceId": "page:o-nas", "lockedByName": "Jana Kováčová", "acquiredAt": 1752399000, "expiresAt": 1752399300 }
}
```

**Heartbeat** – `POST /api/locks/heartbeat` `{ "resourceId": "page:o-nas", "token": "9f2c…" }`
**Release** – `POST /api/locks/release` `{ "resourceId": "page:o-nas", "token": "9f2c…" }`

### 4.3 Mapovanie na frontend

| Backend Endpoint | Frontend | Akcia |
|:-----------------|:---------|:------|
| `POST /api/locks/acquire` | `useContentLock` / `LockIndicator` | Získať zámok pri otvorení editora |
| `POST /api/locks/heartbeat` | `useContentLock` | Predĺženie každých 30 s |
| `POST /api/locks/release` | `useContentLock` | Uvoľnenie pri odchode |
| `GET /api/locks` | *(admin dashboard – Iterácia 6)* | Zoznam zámkov |
| `DELETE /api/locks/{resourceId}` | *(admin dashboard – Iterácia 6)* | Vynútené uvoľnenie |

---

## 5. Auto-Save, revízie a detekcia konfliktov (Iterácia 2)

Optimistické zamykanie na úrovni obsahu + auto-save konceptov. Bez databázy – všetko vo flat-file
(`data/drafts/`, revízny `sha1` odtlačok počítaný za behu).

### 5.1 Koncepty (Drafts) – auto-save

| Method | Endpoint | Auth | Popis |
|:------|:---------|:-----|:------|
| `PUT` | `/api/drafts/{type}/{slug}` | prihlásený | Uloží/prepíše koncept (volané každých 60 s) |
| `GET` | `/api/drafts/{type}/{slug}` | prihlásený | Načíta koncept (obnova rozpracovaného obsahu) |
| `DELETE` | `/api/drafts/{type}/{slug}` | prihlásený | Zahodí koncept |

`{type}` = `page` | `article`.

**Uloženie** – `PUT /api/drafts/page/o-nas`
```json
// request
{ "title": "O nás", "content": "# …", "status": "draft", "baseRevision": "3f1c…" }

// 200 response
{ "success": true, "message": "Koncept uložený", "data": { "type": "page", "slug": "o-nas", "savedAt": 1752400500, "...": "…" } }
```

### 5.2 Revízia obsahu + detekcia konfliktu

Každá odpoveď obsahu (`GET/POST/PUT /api/pages|articles`) teraz obsahuje pole `revision`
(sha1 odtlačok). Klient ho pri úprave pošle späť ako `baseRevision`.

**Update s revíziou** – `PUT /api/pages/o-nas`
```json
{ "title": "O nás", "content": "…", "status": "published", "message": "Úprava úvodu", "baseRevision": "3f1c…" }
```

**Konflikt (409)** – ak sa obsah na disku medzičasom zmenil:
```json
{
  "success": false,
  "error": "Obsah bol medzičasom zmenený iným používateľom.",
  "conflict": {
    "serverContent": "…aktuálny obsah na disku…",
    "serverFrontMatter": { "title": "…" },
    "serverRevision": "a7b9…"
  }
}
```

> `baseRevision` je voliteľný – ak ho klient nepošle, kontrola konfliktu sa preskočí (spätná kompatibilita).
> `message` (voliteľné) sa uloží ako commit správa k verzii.

### 5.3 Mapovanie na frontend

| Backend Endpoint | Frontend | Akcia |
|:-----------------|:---------|:------|
| `PUT /api/drafts/{type}/{slug}` | `useAutoSave` | Auto-save konceptu každých 60 s |
| `GET /api/drafts/{type}/{slug}` | `MarkdownEditor` | Ponuka obnovy neuloženého konceptu |
| `DELETE /api/drafts/{type}/{slug}` | `MarkdownEditor` | Zahodenie konceptu po uložení |
| `PUT /api/pages\|articles/{slug}` (409) | `MarkdownEditor` | Riešenie konfliktu (auto-merge / ConflictResolver) |
| `GET /api/admin/versions/compare` | `DiffViewer` | Porovnanie verzií vedľa seba |

---

## 6. Riešenie konfliktov – 3-way merge (Iterácia 3)

Trojcestné zlúčenie prebieha **na klientovi** (žiadny stav navyše na backende): klient má
`base` (pôvodne načítané) aj `mine` (svoje úpravy) a `theirs` dostane v 409 odpovedi
(`conflict.serverContent`). Nekonfliktné zmeny sa zlúčia automaticky; konfliktné rieši
používateľ v `ConflictResolver`. Po vyriešení sa uloží proti `serverRevision`.

### 6.1 Admin – log konfliktov

| Method | Endpoint | Auth | Popis |
|:------|:---------|:-----|:------|
| `GET` | `/api/admin/conflicts?limit=100` | ADMIN / SUPER_ADMIN | Zoznam najnovších konfliktov |
| `DELETE` | `/api/admin/conflicts` | ADMIN / SUPER_ADMIN | Vyčistenie logu |

**Príklad** – `GET /api/admin/conflicts`
```json
{
  "success": true,
  "data": {
    "conflicts": [
      { "resourceId": "page:o-nas", "userId": "user_…", "userName": "Ján Novák",
        "baseRevision": "3f1c…", "serverRevision": "a7b9…", "occurredAt": 1752400600 }
    ]
  }
}
```

### 6.2 Mapovanie na frontend

| Backend Endpoint | Frontend | Akcia |
|:-----------------|:---------|:------|
| `PUT /api/pages\|articles/{slug}` (409) | `merge3` + `ConflictResolver` | 3-way merge / manuálne riešenie |
| `GET /api/admin/conflicts` | `api/conflicts.ts` *(admin dashboard – Iterácia 6)* | Prehľad konfliktov |
| `DELETE /api/admin/conflicts` | `api/conflicts.ts` *(admin dashboard – Iterácia 6)* | Vyčistenie logu |

---

## 7. Settings, Error Handler, Validácia (Iterácia 4)

Flat-file nastavenia CMS, jednotný JSON error obal a zdieľané validačné pravidlá FE↔BE.
Podrobnosti: `docs/architecture/SETTINGS.md`.

### 7.1 Nastavenia

| Method | Endpoint | Auth | Popis |
|:------|:---------|:-----|:------|
| `GET` | `/api/settings/public` | prihlásený | Verejný výrez (general/content/editor) |
| `GET` | `/api/admin/settings` | ADMIN | Schéma + efektívne hodnoty |
| `GET` | `/api/admin/settings/{group}` | ADMIN | Jedna skupina |
| `PUT` | `/api/admin/settings/{group}` | ADMIN | Uloženie skupiny (422 pri chybe validácie) |
| `DELETE` | `/api/admin/settings` | ADMIN | Reset na predvolené |

### 7.2 Validačné pravidlá

| Method | Endpoint | Auth | Popis |
|:------|:---------|:-----|:------|
| `GET` | `/api/validation/rules` | verejný | Celý katalóg (login, password, content, user) |
| `GET` | `/api/validation/rules/{context}` | verejný | Jedna sada pravidiel |

### 7.3 Mapovanie na frontend

| Backend Endpoint | Frontend | Akcia |
|:-----------------|:---------|:------|
| `GET /api/admin/settings` | `SettingsView` | Generický formulár podľa schémy |
| `PUT /api/admin/settings/{group}` | `SettingsView` | Uloženie + inline 422 chyby |
| `GET /api/settings/public` | `SettingsContext` / `useSettings` | Auto-save interval, editor, siteName |
| `GET /api/validation/rules` | `api/validation.ts` | Stiahnutie zdieľaných pravidiel |
| 422 `ValidationException` | `api/client.ts` | Pole `errors` v odpovedi |

