import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { renderWithProviders } from '../../test/renderWithProviders';
import { PlaygroundView } from './PlaygroundView';
import { playgroundApi, type PlaygroundConfig } from '../../api/playground';
import { themesApi } from '../../api/themes';
import {
  buildPlaygroundBridge,
  clearPlaygroundBridge,
  clearPlaygroundExport,
  PLAYGROUND_EXPORT_KEY,
  writePlaygroundBridge,
} from '../../utils/playgroundBridge';

vi.mock('../../api/playground', () => ({
  playgroundApi: {
    get: vi.fn(),
    importGit: vi.fn(),
  },
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  }),
}));

vi.mock('../../api/themes', () => ({
  themesApi: {
    validate: vi.fn(),
    normalize: vi.fn(),
  },
}));

vi.mock('./PlaygroundWorkbench', () => ({
  default: ({ onFilesChange }: { onFilesChange?: (files: Record<string, string>) => void }) => (
    <button
      type="button"
      data-testid="playground-sandpack"
      onClick={() => onFilesChange?.({ '/App.tsx': 'export default function Edited() { return null; }' })}
    />
  ),
}));

const enabledConfig: PlaygroundConfig = {
  enabled: true,
  demoBlocked: false,
  defaultTemplate: 'react-ts' as const,
  templates: ['react-ts', 'vanilla', 'vue'],
  packs: [
    {
      packId: 'paginium-starter',
      title: 'Paginium starter',
      source: { type: 'bundled' },
      modules: [],
      enabled: true,
      files: { '/App.tsx': 'export default function App() { return null; }' },
    },
  ],
  gitConfigured: false,
  gitRepoUrl: '',
  gitRef: 'main',
};

describe('PlaygroundView', () => {
  beforeEach(() => {
    vi.mocked(playgroundApi.get).mockReset();
    vi.mocked(playgroundApi.importGit).mockReset();
    vi.mocked(themesApi.validate).mockReset();
    vi.mocked(themesApi.normalize).mockReset();
    clearPlaygroundBridge();
    clearPlaygroundExport();
  });

  afterEach(() => {
    clearPlaygroundBridge();
    clearPlaygroundExport();
  });

  it('points SUPER_ADMIN to settings when playground is off', async () => {
    vi.mocked(playgroundApi.get).mockResolvedValue({
      ...enabledConfig,
      enabled: false,
    });

    const { getByTestId } = renderWithProviders(
      <MemoryRouter>
        <PlaygroundView />
      </MemoryRouter>
    );
    await waitFor(() => {
      expect(getByTestId('playground-disabled')).toBeInTheDocument();
    });
    expect(getByTestId('playground-disabled').textContent ?? '').toMatch(/settings|nastaven/i);
  });

  it('renders the sandpack shell when enabled', async () => {
    vi.mocked(playgroundApi.get).mockResolvedValue(enabledConfig);

    const { getByTestId } = renderWithProviders(
      <MemoryRouter>
        <PlaygroundView />
      </MemoryRouter>
    );
    await waitFor(() => {
      expect(getByTestId('playground-enabled')).toBeInTheDocument();
    });
    expect(getByTestId('playground-template')).toBeInTheDocument();
    expect(getByTestId('playground-pack')).toBeInTheDocument();
  });

  it('exports a Theme Studio CSS snippet through validate, not save', async () => {
    const payload = buildPlaygroundBridge({
      source: 'theme-studio',
      path: 'assets/style.css',
      content: 'body { margin: 0; }',
      returnTo: '/themes/clean-journal/edit',
      themeId: 'clean-journal',
    });
    expect(payload).not.toBeNull();
    writePlaygroundBridge(payload!);

    vi.mocked(playgroundApi.get).mockResolvedValue(enabledConfig);
    vi.mocked(themesApi.validate).mockResolvedValue({
      ok: true,
      result: { valid: true, relativePath: 'assets/style.css', markers: [] },
    });

    const { getByTestId } = renderWithProviders(
      <MemoryRouter initialEntries={['/playground']}>
        <PlaygroundView />
      </MemoryRouter>
    );

    await waitFor(() => {
      expect(getByTestId('playground-bridge')).toBeInTheDocument();
    });
    expect(getByTestId('playground-bridge').textContent ?? '').toContain('assets/style.css');

    fireEvent.click(getByTestId('playground-export'));

    await waitFor(() => {
      expect(themesApi.validate).toHaveBeenCalledWith({
        themeId: 'clean-journal',
        relativePath: 'assets/style.css',
        content: 'body { margin: 0; }',
      });
    });
    expect(themesApi.normalize).not.toHaveBeenCalled();
    expect(sessionStorage.getItem(PLAYGROUND_EXPORT_KEY)).toContain('assets/style.css');
  });

  it('keeps the bridge open when Theme Studio validation cannot be reached', async () => {
    const payload = buildPlaygroundBridge({
      source: 'theme-studio',
      path: 'assets/style.css',
      content: 'body { margin: 0; }',
      returnTo: '/themes/clean-journal/edit',
      themeId: 'clean-journal',
    });
    expect(payload).not.toBeNull();
    writePlaygroundBridge(payload!);

    vi.mocked(playgroundApi.get).mockResolvedValue(enabledConfig);
    vi.mocked(themesApi.validate).mockRejectedValue(new Error('network'));

    const { getByTestId } = renderWithProviders(
      <MemoryRouter initialEntries={['/playground']}>
        <PlaygroundView />
      </MemoryRouter>
    );

    await waitFor(() => {
      expect(getByTestId('playground-bridge')).toBeInTheDocument();
    });
    fireEvent.click(getByTestId('playground-export'));

    await waitFor(() => {
      expect(themesApi.validate).toHaveBeenCalled();
      expect(getByTestId('playground-export')).not.toBeDisabled();
    });
    expect(sessionStorage.getItem(PLAYGROUND_EXPORT_KEY)).toBeNull();
    expect(getByTestId('playground-bridge')).toBeInTheDocument();
  });

  it('exports the edited Sandpack buffer instead of the original Code Editor content', async () => {
    const payload = buildPlaygroundBridge({
      source: 'code-editor',
      path: 'src/App.tsx',
      content: 'export default function Original() { return null; }',
      returnTo: '/code-editor',
    });
    expect(payload).not.toBeNull();
    writePlaygroundBridge(payload!);
    vi.mocked(playgroundApi.get).mockResolvedValue(enabledConfig);

    const { getByTestId } = renderWithProviders(
      <MemoryRouter initialEntries={['/playground']}>
        <PlaygroundView />
      </MemoryRouter>
    );
    await waitFor(() => {
      expect(getByTestId('playground-bridge')).toBeInTheDocument();
    });

    fireEvent.click(getByTestId('playground-sandpack'));
    fireEvent.click(getByTestId('playground-export'));

    await waitFor(() => {
      expect(sessionStorage.getItem(PLAYGROUND_EXPORT_KEY)).toContain('function Edited');
    });
  });

  it('blocks HTML export when normalization does not return a valid result', async () => {
    const payload = buildPlaygroundBridge({
      source: 'theme-studio',
      path: 'templates/default.html',
      content: '<main>Original</main>',
      returnTo: '/themes/clean-journal/edit',
      themeId: 'clean-journal',
    });
    expect(payload).not.toBeNull();
    writePlaygroundBridge(payload!);
    vi.mocked(playgroundApi.get).mockResolvedValue(enabledConfig);
    vi.mocked(themesApi.validate).mockResolvedValue({
      ok: true,
      result: { valid: true, relativePath: 'templates/default.html', markers: [] },
    });
    vi.mocked(themesApi.normalize).mockResolvedValue({
      ok: false,
      result: null,
      error: 'normalize_failed',
    });

    const { getByTestId } = renderWithProviders(
      <MemoryRouter initialEntries={['/playground']}>
        <PlaygroundView />
      </MemoryRouter>
    );
    await waitFor(() => {
      expect(getByTestId('playground-bridge')).toBeInTheDocument();
    });
    fireEvent.click(getByTestId('playground-export'));

    await waitFor(() => {
      expect(themesApi.normalize).toHaveBeenCalled();
    });
    expect(sessionStorage.getItem(PLAYGROUND_EXPORT_KEY)).toBeNull();
    expect(getByTestId('playground-bridge')).toBeInTheDocument();
  });

  it('imports a git pack from configured settings', async () => {
    vi.mocked(playgroundApi.get)
      .mockResolvedValueOnce({
        ...enabledConfig,
        gitConfigured: true,
        gitRepoUrl: 'https://github.com/acme/widgets',
        gitRef: 'v1',
      })
      .mockResolvedValueOnce({
        ...enabledConfig,
        gitConfigured: true,
        gitRepoUrl: 'https://github.com/acme/widgets',
        gitRef: 'v1',
        packs: [
          ...enabledConfig.packs,
          {
            packId: 'acme-widgets',
            title: 'Acme widgets',
            source: { type: 'git' },
            modules: [],
            enabled: true,
          },
        ],
      });
    vi.mocked(playgroundApi.importGit).mockResolvedValue({
      packId: 'acme-widgets',
      title: 'Acme widgets',
      enabled: true,
    });

    const { getByTestId } = renderWithProviders(
      <MemoryRouter>
        <PlaygroundView />
      </MemoryRouter>
    );
    await waitFor(() => {
      expect(getByTestId('playground-import-git')).not.toBeDisabled();
    });
    fireEvent.click(getByTestId('playground-import-git'));
    await waitFor(() => {
      expect(playgroundApi.importGit).toHaveBeenCalled();
    });
  });
});
