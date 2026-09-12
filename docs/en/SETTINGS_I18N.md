# Settings field i18n (admin UI)

> **Scope:** labels, help text, tooltips, and notes for fields rendered from `SettingsSchema.php` in **Settings →** groups.  
> **Related:** Translation Editor (`/translations`, It.18), `frontend/src/i18n/modules/settings/`.

---

## How it works

`SettingsView` reads field metadata from the backend schema (`label`, `help`, …) and resolves user-facing strings through small helpers in `frontend/src/i18n/modules/settings/helpers.ts`:

| Helper | i18n key pattern | Fallback |
|--------|------------------|----------|
| `translateSettingFieldLabel` | `settings.fields.{group}.{fieldKey}.label` | `field.label` from schema |
| `translateSettingFieldHelp` | `settings.fields.{group}.{fieldKey}.help` | `field.help` from schema |
| `translateSettingFieldTooltip` | `settings.fields.{group}.{fieldKey}.tooltip` | none (optional) |
| `translateSettingFieldNote` | `settings.fields.{group}.{fieldKey}.note` | none (optional) |

Example for `content.draftFullEditorState`:

- Label: `settings.fields.content.draftFullEditorState.label`
- Help: `settings.fields.content.draftFullEditorState.help`
- Note (amber hint under checkbox): `settings.fields.content.draftFullEditorState.note`

The helpers are **TypeScript only** — they are not translated. Only the message keys in the `settings` i18n module are translated.

---

## Editing via Translation Editor

1. Open **Translations** (`/translations`).
2. **Source:** Frontend (Admin UI).
3. **Module:** `settings`.
4. **Locale:** `sk`, `en`, or another registered locale.
5. Edit the nested object under `fields.{group}.{fieldKey}` (e.g. `fields.content.draftFullEditorState.note`).
6. Save — file path: `frontend/src/i18n/modules/settings/{locale}.ts`.

After save, reload Settings (or any admin page) to see the new copy. No PHP or schema change is required when only wording changes.

---

## Adding a new settings field (checklist)

1. **Backend** — add the field to `SettingsSchema.php` (`label` + `help` in Slovak as API fallback).
2. **Frontend i18n** — add matching keys under `settings.fields.{group}.{fieldKey}` in `sk.ts` and `en.ts`.
3. **Optional** — `tooltip` (info icon on label) or `note` (secondary hint, e.g. recommendation text).
4. **Tests** — extend `frontend/src/i18n/modules/settings/settings.test.ts` if the key path is non-obvious.
5. **Translation Editor** — new locales can be scaffolded from the UI; copy `fields.*` branches for each group you expose.

---

## Fallback behaviour

- If an i18n **label** or **help** key is missing, the UI shows the Slovak string from `SettingsSchema.php`.
- If **tooltip** or **note** is missing, nothing is shown for that slot (no schema fallback).
- Enum option labels use `settings.enum.{fieldKey}.{option}` via `translateSettingEnumOption`.

---

## Custom settings panels

Some groups use dedicated panels (`NewsletterSettingsPanel`, `PrivacyCookieSettingsPanel`, …) but still call the same helpers with a fixed `groupKey` (e.g. `'privacy'`). New keys follow the same `settings.fields.{group}.{key}.*` convention.
