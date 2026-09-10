export const THEME_STUDIO_SLOTS = ['header', 'main', 'footer', 'sidebar'] as const;

export type ThemeStudioSlot = (typeof THEME_STUDIO_SLOTS)[number];

export interface ThemeStudioSlotStatus {
  id: ThemeStudioSlot;
  found: boolean;
  sourcePath: string;
}

const SLOT_PATH: Record<ThemeStudioSlot, string> = {
  header: 'partials/header.html',
  main: 'templates/default.html',
  footer: 'partials/footer.html',
  sidebar: 'partials/sidebar.html',
};

export function detectThemeStudioSlots(buffers: Record<string, string>): ThemeStudioSlotStatus[] {
  const html = Object.entries(buffers)
    .filter(([path]) => path.toLowerCase().endsWith('.html'))
    .map(([, body]) => body)
    .join('\n');

  return THEME_STUDIO_SLOTS.map((id) => ({
    id,
    found: slotFound(id, html),
    sourcePath: slotSourcePath(id, buffers),
  }));
}

export function insertIntoMainContent(html: string, markup: string): string {
  const snippet = markup.trim();
  if (snippet === '') {
    return html;
  }

  const token = '{{content}}';
  const tokenAt = html.indexOf(token);
  if (tokenAt !== -1) {
    return `${html.slice(0, tokenAt)}${snippet}\n${html.slice(tokenAt)}`;
  }

  const mainClose = html.search(/<\/main>/i);
  if (mainClose !== -1) {
    return `${html.slice(0, mainClose)}${snippet}\n${html.slice(mainClose)}`;
  }

  return `${html}\n${snippet}\n`;
}

function slotFound(id: ThemeStudioSlot, html: string): boolean {
  if (id === 'header') {
    return /\{\{>\s*header\s*\}\}/.test(html) || /<header\b/i.test(html);
  }
  if (id === 'main') {
    return /\{\{\s*content\s*\}\}/.test(html) || /<main\b/i.test(html);
  }
  if (id === 'footer') {
    return /\{\{>\s*footer\s*\}\}/.test(html) || /<footer\b/i.test(html);
  }

  return /\{\{>\s*sidebar\s*\}\}/.test(html) || /<aside\b/i.test(html);
}

function slotSourcePath(id: ThemeStudioSlot, buffers: Record<string, string>): string {
  const preferred = SLOT_PATH[id];
  if (buffers[preferred] !== undefined) {
    return preferred;
  }
  if (buffers['templates/default.html'] !== undefined) {
    return 'templates/default.html';
  }

  return preferred;
}
