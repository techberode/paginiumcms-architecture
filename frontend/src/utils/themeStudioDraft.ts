import { languageForThemePath, tabForThemePath, type ThemeStudioTab } from './themeStudioFiles';

export const THEME_STUDIO_DRAFT_ID = 'new';

export interface ThemeStudioDraftFile {
  relativePath: string;
  language: string;
  tab: ThemeStudioTab;
  content: string;
}

const DRAFT_THEME_JSON = `{
  "manifestVersion": 1,
  "id": "untitled-theme",
  "name": "Untitled theme",
  "version": "0.1.0",
  "minCmsVersion": "2.1.0",
  "description": "",
  "slots": ["header", "main", "footer"],
  "templates": ["default"],
  "supports": ["appearance-tokens", "branding", "navigation"]
}
`;

const DRAFT_DEFAULT_HTML = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{title}}</title>
</head>
<body class="pg-theme-untitled pg-template-default">
  {{> header}}
  <main class="pg-main" id="main-content">{{content}}</main>
  {{> footer}}
</body>
</html>
`;

const DRAFT_HEADER_HTML = `<header class="pg-header">
  <a href="/" class="pg-brand">{{siteName}}</a>
</header>
`;

const DRAFT_FOOTER_HTML = `<footer class="pg-footer">
  <p>{{siteName}}</p>
</footer>
`;

const DRAFT_CSS = `/* Theme tokens and layout — persist ships in 88g. */
body {
  margin: 0;
  font-family: system-ui, sans-serif;
}

.pg-main {
  max-width: 72rem;
  margin: 0 auto;
  padding: 1.5rem;
}
`;

export function themeStudioDraftFiles(): ThemeStudioDraftFile[] {
  const contents: Array<[string, string]> = [
    ['theme.json', DRAFT_THEME_JSON],
    ['templates/default.html', DRAFT_DEFAULT_HTML],
    ['partials/header.html', DRAFT_HEADER_HTML],
    ['partials/footer.html', DRAFT_FOOTER_HTML],
    ['assets/theme.css', DRAFT_CSS],
  ];

  return contents.map(([relativePath, content]) => ({
    relativePath,
    content,
    language: languageForThemePath(relativePath),
    tab: tabForThemePath(relativePath),
  }));
}
