import type { PlaygroundTemplate } from '../api/playground';

export const PLAYGROUND_BRIDGE_KEY = 'paginium.playground.bridge';
export const PLAYGROUND_EXPORT_KEY = 'paginium.playground.export';
export const PLAYGROUND_BRIDGE_MAX_CHARS = 400_000;

const ALLOWED_EXTENSIONS = new Set(['tsx', 'jsx', 'css', 'html', 'js']);

export type PlaygroundBridgeSource = 'theme-studio' | 'code-editor';

export interface PlaygroundBridgePayload {
  source: PlaygroundBridgeSource;
  path: string;
  content: string;
  returnTo: string;
  sandpackPath: string;
  template: PlaygroundTemplate;
  themeId?: string;
}

export interface PlaygroundBridgeDraft {
  source: PlaygroundBridgeSource;
  path: string;
  content: string;
  returnTo: string;
  themeId?: string;
}

export function playgroundExtensionOf(relativePath: string): string {
  const slash = relativePath.replace(/\\/g, '/').lastIndexOf('/');
  const name = slash >= 0 ? relativePath.slice(slash + 1) : relativePath;
  const dot = name.lastIndexOf('.');
  if (dot < 0) {
    return '';
  }
  return name.slice(dot + 1).toLowerCase();
}

export function isPlaygroundBridgePath(relativePath: string): boolean {
  const path = relativePath.trim();
  if (path === '' || path.includes('\0')) {
    return false;
  }
  const normalized = path.replace(/\\/g, '/');
  if (normalized.startsWith('/') || normalized.includes('://')) {
    return false;
  }
  const segments = normalized.split('/');
  if (segments.some((segment) => segment === '' || segment === '.' || segment === '..')) {
    return false;
  }
  return ALLOWED_EXTENSIONS.has(playgroundExtensionOf(normalized));
}

export function playgroundTemplateForPath(relativePath: string): PlaygroundTemplate {
  const ext = playgroundExtensionOf(relativePath);
  if (ext === 'tsx' || ext === 'jsx') {
    return 'react-ts';
  }
  return 'vanilla';
}

export function sandpackPathForBridge(relativePath: string): string {
  const ext = playgroundExtensionOf(relativePath);
  if (ext === 'css') {
    return '/styles.css';
  }
  if (ext === 'html') {
    return '/index.html';
  }
  if (ext === 'js') {
    return '/index.js';
  }
  if (ext === 'jsx') {
    return '/App.jsx';
  }
  return '/App.tsx';
}

export function sandpackFilesForBridge(relativePath: string, content: string): Record<string, string> {
  const ext = playgroundExtensionOf(relativePath);
  if (ext === 'css') {
    return {
      '/index.html':
        '<!DOCTYPE html>\n<html><head><meta charset="utf-8"><link rel="stylesheet" href="/styles.css"></head><body><main class="preview">CSS preview</main></body></html>\n',
      '/styles.css': content,
      '/index.js': '',
    };
  }
  if (ext === 'html') {
    return {
      '/index.html': content,
      '/styles.css': '',
      '/index.js': '',
    };
  }
  if (ext === 'js') {
    return {
      '/index.html':
        '<!DOCTYPE html>\n<html><head><meta charset="utf-8"></head><body><div id="app"></div><script src="/index.js"></script></body></html>\n',
      '/index.js': content,
      '/styles.css': '',
    };
  }
  if (ext === 'jsx') {
    return { '/App.jsx': content };
  }
  return { '/App.tsx': content };
}

export function buildPlaygroundBridge(draft: PlaygroundBridgeDraft): PlaygroundBridgePayload | null {
  if (!isPlaygroundBridgePath(draft.path)) {
    return null;
  }
  if (draft.content.length > PLAYGROUND_BRIDGE_MAX_CHARS) {
    return null;
  }
  return {
    source: draft.source,
    path: draft.path.trim(),
    content: draft.content,
    returnTo: draft.returnTo,
    sandpackPath: sandpackPathForBridge(draft.path),
    template: playgroundTemplateForPath(draft.path),
    themeId: draft.themeId,
  };
}

function readJson(key: string): PlaygroundBridgePayload | null {
  if (typeof sessionStorage === 'undefined') {
    return null;
  }
  try {
    const raw = sessionStorage.getItem(key);
    if (raw === null || raw === '') {
      return null;
    }
    const parsed: unknown = JSON.parse(raw);
    if (!isPayload(parsed)) {
      sessionStorage.removeItem(key);
      return null;
    }
    if (!isValidPayload(parsed)) {
      sessionStorage.removeItem(key);
      return null;
    }
    return parsed;
  } catch {
    sessionStorage.removeItem(key);
    return null;
  }
}

function writeJson(key: string, payload: PlaygroundBridgePayload): boolean {
  if (typeof sessionStorage === 'undefined' || !isValidPayload(payload)) {
    return false;
  }
  try {
    sessionStorage.setItem(key, JSON.stringify(payload));
    return true;
  } catch {
    return false;
  }
}

function isPayload(value: unknown): value is PlaygroundBridgePayload {
  if (value === null || typeof value !== 'object') {
    return false;
  }
  const record = value as Record<string, unknown>;
  return (
    (record.source === 'theme-studio' || record.source === 'code-editor')
    && typeof record.path === 'string'
    && typeof record.content === 'string'
    && typeof record.returnTo === 'string'
    && typeof record.sandpackPath === 'string'
    && (record.template === 'react-ts' || record.template === 'vanilla' || record.template === 'vue')
    && (record.themeId === undefined || typeof record.themeId === 'string')
  );
}

function isValidPayload(payload: PlaygroundBridgePayload): boolean {
  return (
    isPlaygroundBridgePath(payload.path)
    && payload.content.length <= PLAYGROUND_BRIDGE_MAX_CHARS
    && payload.sandpackPath === sandpackPathForBridge(payload.path)
    && payload.template === playgroundTemplateForPath(payload.path)
    && payload.returnTo.startsWith('/')
    && !payload.returnTo.startsWith('//')
    && !payload.returnTo.includes('\0')
    && !payload.returnTo.includes('\n')
    && !payload.returnTo.includes('\r')
  );
}

export function readPlaygroundBridge(): PlaygroundBridgePayload | null {
  return readJson(PLAYGROUND_BRIDGE_KEY);
}

export function writePlaygroundBridge(payload: PlaygroundBridgePayload): boolean {
  return writeJson(PLAYGROUND_BRIDGE_KEY, payload);
}

export function clearPlaygroundBridge(): void {
  if (typeof sessionStorage === 'undefined') {
    return;
  }
  sessionStorage.removeItem(PLAYGROUND_BRIDGE_KEY);
}

let takenExport: PlaygroundBridgePayload | null | undefined;

export function readPlaygroundExport(): PlaygroundBridgePayload | null {
  if (takenExport !== undefined) {
    return takenExport;
  }
  return readJson(PLAYGROUND_EXPORT_KEY);
}

export function writePlaygroundExport(payload: PlaygroundBridgePayload): boolean {
  takenExport = undefined;
  return writeJson(PLAYGROUND_EXPORT_KEY, payload);
}

export function consumePlaygroundExport(): void {
  if (takenExport === undefined) {
    takenExport = readJson(PLAYGROUND_EXPORT_KEY);
  }
  clearPlaygroundExportStorage();
}

export function clearPlaygroundExport(): void {
  takenExport = undefined;
  clearPlaygroundExportStorage();
}

function clearPlaygroundExportStorage(): void {
  if (typeof sessionStorage === 'undefined') {
    return;
  }
  sessionStorage.removeItem(PLAYGROUND_EXPORT_KEY);
}
