import { afterEach, describe, expect, it } from 'vitest';
import {
  buildPlaygroundBridge,
  clearPlaygroundBridge,
  clearPlaygroundExport,
  isPlaygroundBridgePath,
  PLAYGROUND_BRIDGE_KEY,
  PLAYGROUND_BRIDGE_MAX_CHARS,
  PLAYGROUND_EXPORT_KEY,
  playgroundTemplateForPath,
  readPlaygroundBridge,
  readPlaygroundExport,
  sandpackFilesForBridge,
  sandpackPathForBridge,
  writePlaygroundBridge,
  writePlaygroundExport,
} from './playgroundBridge';

describe('playgroundBridge', () => {
  afterEach(() => {
    clearPlaygroundBridge();
    clearPlaygroundExport();
  });

  it('allows theme and component web assets and rejects PHP', () => {
    expect(isPlaygroundBridgePath('assets/style.css')).toBe(true);
    expect(isPlaygroundBridgePath('layout.html')).toBe(true);
    expect(isPlaygroundBridgePath('src/StatCard.tsx')).toBe(true);
    expect(isPlaygroundBridgePath('src/Widget.jsx')).toBe(true);
    expect(isPlaygroundBridgePath('assets/app.js')).toBe(true);
    expect(isPlaygroundBridgePath('backend/app/Http/Kernel.php')).toBe(false);
    expect(isPlaygroundBridgePath('theme.json')).toBe(false);
    expect(isPlaygroundBridgePath('../assets/style.css')).toBe(false);
    expect(isPlaygroundBridgePath('/etc/passwd.css')).toBe(false);
  });

  it('maps files onto isolated Sandpack entries', () => {
    expect(playgroundTemplateForPath('assets/style.css')).toBe('vanilla');
    expect(playgroundTemplateForPath('src/App.tsx')).toBe('react-ts');
    expect(sandpackPathForBridge('layout.html')).toBe('/index.html');
    expect(sandpackFilesForBridge('assets/style.css', 'body{color:red}')['/styles.css']).toBe('body{color:red}');
    expect(sandpackFilesForBridge('src/App.tsx', 'export default function App(){return null}')['/App.tsx']).toContain(
      'export default',
    );
  });

  it('stores a session payload and refuses a weaker PHP path', () => {
    expect(
      buildPlaygroundBridge({
        source: 'code-editor',
        path: 'backend/app/Modules/Playground/PlaygroundSettings.php',
        content: '<?php',
        returnTo: '/code-editor',
      }),
    ).toBeNull();

    const payload = buildPlaygroundBridge({
      source: 'theme-studio',
      path: 'assets/style.css',
      content: 'body { margin: 0; }',
      returnTo: '/themes/clean-journal/edit',
      themeId: 'clean-journal',
    });
    expect(payload).not.toBeNull();
    expect(writePlaygroundBridge(payload!)).toBe(true);
    expect(sessionStorage.getItem(PLAYGROUND_BRIDGE_KEY)).not.toBeNull();
    expect(readPlaygroundBridge()?.path).toBe('assets/style.css');
  });

  it('rejects oversized exports instead of reporting a lost round trip as successful', () => {
    const payload = buildPlaygroundBridge({
      source: 'code-editor',
      path: 'src/App.tsx',
      content: 'export default null',
      returnTo: '/code-editor',
    });
    expect(payload).not.toBeNull();

    expect(
      writePlaygroundExport({
        ...payload!,
        content: 'x'.repeat(PLAYGROUND_BRIDGE_MAX_CHARS + 1),
      }),
    ).toBe(false);
    expect(sessionStorage.getItem(PLAYGROUND_EXPORT_KEY)).toBeNull();
    expect(readPlaygroundExport()).toBeNull();
  });

  it('rejects tampered bridge metadata and external return paths', () => {
    const payload = buildPlaygroundBridge({
      source: 'theme-studio',
      path: 'assets/style.css',
      content: 'body {}',
      returnTo: '/themes/example/edit',
      themeId: 'example',
    });
    expect(payload).not.toBeNull();

    expect(writePlaygroundBridge({ ...payload!, sandpackPath: '/App.tsx' })).toBe(false);
    expect(writePlaygroundBridge({ ...payload!, returnTo: '//example.test' })).toBe(false);
    expect(sessionStorage.getItem(PLAYGROUND_BRIDGE_KEY)).toBeNull();
  });
});
