# Clean Journal — starter theme package

Import this ZIP from **Admin → Themes → Import ZIP**. Every file is scanned by the CMS code policy engine before installation.

## Structure

- `theme.json` — manifest (required)
- `templates/` — HTML layout shells with `{{content}}` placeholder
- `partials/` — reusable header/footer fragments
- `frontend/tokens.css` — optional public appearance token overrides

## Security

- No PHP or remote script URLs in the package
- HTML/CSS only in this starter; policy rejects forbidden constructs on import
