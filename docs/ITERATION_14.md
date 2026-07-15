# Iteration 14 – Code Policy & Code Editor Foundation

**Status:** Complete  
**Version:** 2.0.3 (pending tag)

## Summary

Iteration 14 fixes broken Code Editor path resolution, introduces a **CodePolicyEngine** for safe in-browser edits, wires Developer Mode unlock in the frontend, and aligns allowed edit paths with the extension architecture (`Http/Extensions`, theme views).

## Backend

### Code policy (`Core/CodePolicy/`)
- `CodePolicyEngine` – size, syntax, security, and extension namespace rules
- `SecurityScanner` – PHP token scan (`T_EVAL`, `T_STRING` function calls)
- `CodePolicyViolationException` – HTTP 422 with grouped `errors`
- Settings group `codePolicy` in `SettingsSchema` (enabled, strictMode, maxFileSizeKb, forbiddenPhpFunctions)

### Code editor fixes (`Core/CodeEditor/`)
- `CodeEditorManager` – correct `projectRoot` (5 levels up), path normalization, `FileInfo[]` from `listFiles`
- `FileBackup` – uses project root
- Allowed paths: `backend/app/Modules`, `backend/app/Http/Extensions`, `backend/resources/views/themes`, `backend/config`
- Forbidden: `backend/app/Core`, `backend/bootstrap`, `backend/vendor`

### HTTP
- `CodeEditorController` – 422 on policy violations; default directory in list response
- `ApiErrorHandler` maps `CodePolicyViolationException` → 422
- DI in `Http/Config/services.php` for policy + editor stack

### Directories
- `backend/app/Http/Extensions/` (plugin/extension PHP)
- `backend/resources/views/themes/` (theme templates)

## Frontend

| File | Role |
|---|---|
| `api/developer.ts` | Developer gate status + unlock (`totp_code` / `token`) |
| `components/CodeEditor/DeveloperUnlockGate.tsx` | Unlock form before Code Editor |
| `components/CodeEditor/CodeEditor.tsx` | Policy error display on save; default `directory` query |

## Tests

- `CodePolicyEngineTest` – blocks `eval`, allows valid PHP
- `CodeEditorManagerTest` – project root paths, traversal rejection, `FileInfo` shape

## Deferred (Iteration 15+)

- `PluginManager` (depends on this policy layer)
- Monaco editor, create/delete files, full hierarchical FileTree
- WYSIWYG integration (Iteration 8 scope)

## Next (Iteration 8 or 15)

- Media manager FE
- Plugin loader on top of `Http/Extensions`
- Monaco / advanced FileTree (Iteration 16)
