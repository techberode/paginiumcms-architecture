---
title: Managing Plugins
description: Importing, enabling, diagnosing, and safely removing plugins
icon: material/puzzle
---

# Plugins — User Guide

> **Administration route:** **Extensions** (`/extensions`)  
> **Recommended role:** privileged administrator; imported PHP code is high risk.

A plugin adds optional functionality without manually editing Core. Import is not equivalent to uploading an image: a plugin may contain PHP routes and hook handlers that execute inside the CMS process after activation.

---

## 1. Before import

- verify the package source and author,
- read `README.md` and `plugin.json`,
- check the required `minCmsVersion`,
- create and verify a backup,
- prefer a staging/dev profile,
- never install a package merely because its name resembles a known plugin.

Do not paste tokens, `.env`, private paths, or whole unknown PHP files containing secrets into an issue report or screenshot.

---

## 2. Expected ZIP package

```text
plugin-id/
├── plugin.json
├── src/
├── routes.php       # optional
├── frontend/        # optional; may require rebuild
├── assets/
└── README.md
```

The manifest ID must match the directory. The ZIP must not contain absolute paths, `../`, a vendor tree, or attempts to write into Core.

---

## 3. Import

1. Open **Extensions**.
2. Choose **Import plugin**.
3. Select the ZIP.
4. Review the validation report.
5. Confirm import.

The CMS checks the manifest, paths, syntax, and forbidden constructs. A successfully imported plugin remains **disabled** by default.

### Common outcomes

| Outcome | Meaning |
|---------|---------|
| import succeeded | files and registry were stored; the plugin may not be running yet |
| `422` policy violation | syntax, forbidden function, namespace, or another rule failed |
| incompatible version | `minCmsVersion` is higher or the manifest is unsupported |
| ID collision | a plugin with the same ID exists; use a supported upgrade flow |
| invalid ZIP | damaged archive or unsafe path |

Manually extracting into `backend/app/Http/Extensions` bypasses audit and import protections and is not recommended.

---

## 4. Enable

After import:

1. open the plugin detail,
2. verify name, ID, version, and capabilities,
3. click **Enable**,
4. run the recommended smoke test,
5. inspect audit and application logs.

Activation registers hooks and routes declared in the manifest. “Enabled” alone does not prove every plugin feature works; it may require configuration, a build step, or an external provider.

### Frontend plugins

When a plugin contains React/TypeScript source, the production Vite bundle may not load it without a controlled rebuild/redeploy. The admin UI should expose this requirement; do not inject remote JavaScript with a `<script>` tag as a shortcut.

---

## 5. Disable

Clicking **Disable**:

- detaches hooks and extension routes at the supported reload/boot boundary,
- retains plugin files and data,
- allows diagnosis without immediate removal.

Disable a plugin first when activation causes HTTP 500 errors, latency, conflicts, or unexpected side effects.

---

## 6. Uninstall

Uninstall removes the plugin registry entry and files. Before confirming:

- disable the plugin,
- create a backup,
- export its authoritative data when supported,
- check whether content or shortcodes still depend on it,
- verify the public fallback.

The CMS must not automatically delete unknown plugin data without an explicit cleanup contract. “Uninstall” therefore may not mean “delete everything the plugin ever created.”

---

## 7. Upgrade

Public Beta may not provide a universal safe upgrade for every plugin. Do not overwrite the existing directory manually. A safe process is:

1. read release notes,
2. back up plugin and data,
3. disable the plugin,
4. use the supported upgrade/import flow,
5. validate manifest and migration,
6. enable and test,
7. roll back on failure.

Downgrading may damage a newer data format.

---

## 8. Troubleshooting

| Symptom | Check |
|---------|-------|
| plugin cannot be enabled | manifest, `minCmsVersion`, unknown hooks, PHP syntax |
| endpoint returns 404 | plugin enabled, route declaration, bootstrap/reload, correct path |
| UI did not appear | frontend capability, build/redeploy, browser console, route/menu contract |
| HTTP 500 after enable | disable plugin, inspect PHP log, class/namespace collision |
| changes are not visible | cache/reload, worker restart, frontend build |
| import says Permission denied | ownership and group-write storage directories; do not use `chmod 777` |

For support, provide CMS version, plugin ID/version, exact action, timestamp, request ID, and a redacted relevant log. Never include secrets.

---

## 9. Reference plugin

The repository includes `hello-widget`. After enabling it, a smoke endpoint may be available:

```http
GET /api/extensions/hello-widget/ping
```

The expected result is a successful `pong` response. The reference plugin verifies the runtime contract; it does not prove every external package is safe.

---

## 10. Administrator security rules

- do not install plugins from unverified links,
- do not bypass policy scanning through manual copying,
- do not enable a plugin merely to suppress a warning,
- do not reuse one dev token across machines,
- lock Developer Mode after testing,
- monitor audit, outbound traffic, and new routes,
- keep the minimum number of plugins enabled in production.

---

## Related documents

- [Plugin architecture](../architecture/PLUGINS.md)
- [Extension Code Policy](../developer/EXTENSION_CODE_POLICY.md)
- [Code Editor](CODE_EDITOR.md)
- [Developer Mode](DEVELOPER_MODE.md)
- [Backups and administration](ADMIN_GUIDE.md)
