# Code policy for extensions, themes, and untrusted surfaces

PaginiumCMS separates **core** PHP (Code Editor, trusted paths) from **untrusted** artifacts: plugins, themes, layout shortcodes, snippets, and Monaco-validated JSON.

## Policy engine

| Component | Path |
|-----------|------|
| `CodePolicyEngine` | `backend/app/Core/CodePolicy/Services/CodePolicyEngine.php` |
| `UntrustedPolicyScanner` | `backend/app/Core/CodePolicy/Services/UntrustedPolicyScanner.php` |
| Settings group | **Settings → Code policy** (`codePolicy` in `SettingsSchema.php`) |

### Fail-closed rule

Untrusted paths are **always** scanned, even when **Enable code policy checks (core)** is off. The settings toggle only affects trusted/core Code Editor writes.

Untrusted path markers include:

- `backend/app/Http/Extensions/` (plugins)
- `themes/`
- `data/layout/`, `data/shortcodes/`, `data/plugins/`
- logical `untrusted://` prefix used by layout/snippet managers

### Checks applied

| Check | Core (optional) | Untrusted (mandatory) |
|-------|-------------------|-------------------------|
| PHP syntax | yes | yes |
| Max file size | `maxFileSizeKb` (default 512 KB) | `untrustedMaxFileSizeKb` (default 256 KB, capped by max) |
| Forbidden functions | configurable list | list + `include`/`require`/`unserialize`/`call_user_func*` |
| `declare(strict_types=1)` | no | yes for PHP |
| Extension namespace | if strict mode | `PaginiumCMS\Http\Extensions\{id}` on class files under `src/` |

## Import pipelines

| Pipeline | Scanner wired | When |
|----------|---------------|------|
| Plugin ZIP import | `PluginImporter` → `PluginPolicyScanner` → `UntrustedPolicyScanner` | Before files are moved into `Http/Extensions/` |
| Theme ZIP import | `ThemeImporter` → `UntrustedPolicyScanner` | Before theme tree is installed |
| Shortcode save | `ShortcodeDefinitionManager` → `validateUntrusted()` | Every admin save |
| Snippet save | repository layer + content sanitization | Every admin save |
| Code Editor save | `CodeEditorManager` → `validate()` | Per path; respects `codePolicy.enabled` for core |

Failed policy → HTTP 422 with grouped errors; import is aborted (no partial install for plugins/themes).

## Operator settings

Configure under **Settings → Code policy**:

- **Enable code policy checks** — core Code Editor only
- **Strict extension namespace rules** — enforce `PaginiumCMS\Http\Extensions\{id}` namespaces
- **Max file size (KB)** — core cap
- **Max untrusted file size (KB)** — plugins/themes/shortcodes cap
- **Forbidden PHP functions** — comma-separated; untrusted trees always get extra hard blocks

## Security status (implemented)

| Control | Status |
|---------|--------|
| Plugin/theme ZIP policy scan | Implemented |
| Extension namespace + strict_types | Implemented |
| Shortcode JSON policy on save | Implemented |
| Settings UI + i18n for all codePolicy keys | Implemented |
| Origin probe `it67_untrusted` | Implemented |
| PHPUnit `CodePolicyEngineTest`, importer tests | Implemented |

## Related docs

- [Core hardening](CORE_HARDENING.md)
- [Plugins user guide](../user/PLUGINS.md)
- [Developer mode](../user/DEVELOPER_MODE.md)
