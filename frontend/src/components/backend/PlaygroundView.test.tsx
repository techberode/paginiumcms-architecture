import { describe, expect, it, vi, beforeEach } from 'vitest';
import { waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { renderWithProviders } from '../../test/renderWithProviders';
import { PlaygroundView } from './PlaygroundView';
import { playgroundApi } from '../../api/playground';

vi.mock('../../api/playground', () => ({
  playgroundApi: {
    get: vi.fn(),
  },
}));

vi.mock('./PlaygroundWorkbench', () => ({
  default: () => <div data-testid="playground-sandpack" />,
  PlaygroundWorkbench: () => <div data-testid="playground-sandpack" />,
}));

describe('PlaygroundView', () => {
  beforeEach(() => {
    vi.mocked(playgroundApi.get).mockReset();
  });

  it('points SUPER_ADMIN to settings when playground is off', async () => {
    vi.mocked(playgroundApi.get).mockResolvedValue({
      enabled: false,
      demoBlocked: false,
      defaultTemplate: 'react-ts',
      templates: ['react-ts', 'vanilla', 'vue'],
      packs: [{ packId: 'paginium-starter', title: 'Paginium starter', source: { type: 'bundled' }, modules: [], enabled: true }],
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
    vi.mocked(playgroundApi.get).mockResolvedValue({
      enabled: true,
      demoBlocked: false,
      defaultTemplate: 'react-ts',
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
    });

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
});
