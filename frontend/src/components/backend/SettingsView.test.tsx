import { describe, it, expect, vi, beforeEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { Route, Routes, useSearchParams } from 'react-router-dom';
import { SettingsView } from './SettingsView';
import { renderWithRouter } from '../../test/renderWithRouter';
import { fastUser } from '../../test/userEvent';

const mocks = vi.hoisted(() => ({
  getSettings: vi.fn(),
  updateSettingsGroup: vi.fn(),
  reloadGlobalSettings: vi.fn(),
  applyPreview: vi.fn(),
  clearPreview: vi.fn(),
  clearPreviewGroup: vi.fn(),
  toast: {
    success: vi.fn(),
    error: vi.fn(),
  },
}));

vi.mock('../../api/settings', () => ({
  getSettings: mocks.getSettings,
  updateSettingsGroup: mocks.updateSettingsGroup,
  rulesFromSchema: (group: { fields?: Array<{ key: string; type?: string }> }) => {
    const rules: Record<string, string[]> = {};
    for (const field of group.fields ?? []) {
      rules[field.key] = field.type ? [field.type] : ['string'];
    }
    return rules;
  },
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => mocks.toast,
}));

vi.mock('../../hooks/useSettings', () => ({
  useSettings: () => ({
    reload: mocks.reloadGlobalSettings,
    applyPreview: mocks.applyPreview,
    clearPreview: mocks.clearPreview,
    clearPreviewGroup: mocks.clearPreviewGroup,
    hasUnsavedPreview: false,
    settings: { general: { siteName: 'Paginium' } },
  }),
}));

vi.mock('../../hooks/useAuth', () => ({
  useAuth: () => ({ user: { roles: ['ADMIN'] } }),
}));

vi.mock('./CacheManagerPanel', () => ({
  CacheManagerPanel: () => <div data-testid="cache-panel" />,
}));

const schema = {
  general: { label: 'General', fields: [{ key: 'siteName', type: 'string', label: 'Site name' }] },
  logging: { label: 'Logging', fields: [{ key: 'retentionDays', type: 'int', label: 'Retention days' }] },
};

function renderSettings(initialEntry: string) {
  return renderWithRouter(
    <Routes>
      <Route path="/settings" element={<SettingsView />} />
    </Routes>,
    { routerProps: { initialEntries: [initialEntry] } }
  );
}

describe('SettingsView deep links', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.getSettings.mockResolvedValue({
      schema,
      values: {
        general: { siteName: 'Paginium' },
        logging: { retentionDays: 14 },
      },
    });
  });

  it('opens the requested settings group from ?group=', async () => {
    renderSettings('/settings?category=system&group=logging');

    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'Logy' })).toHaveClass('admin-tab-on');
    });
    expect(screen.getByLabelText('Retencia logov (dni)')).toBeInTheDocument();
  });

  it('syncs ?group= when switching tabs', async () => {
    let latestSearch = '';

    renderWithRouter(
      <>
        <Routes>
          <Route path="/settings" element={<SettingsView />} />
        </Routes>
        <SearchParamsProbe onChange={(value) => { latestSearch = value; }} />
      </>,
      { routerProps: { initialEntries: ['/settings?category=system&group=general'] } }
    );

    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'Všeobecné' })).toHaveClass('admin-tab-on');
    });

    await fastUser.click(screen.getByRole('button', { name: 'Logy' }));

    await waitFor(() => {
      expect(latestSearch).toBe('category=system&group=logging');
    });
  });
});

describe('SettingsView apply preview', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.getSettings.mockResolvedValue({
      schema,
      values: {
        general: { siteName: 'Paginium' },
        logging: { retentionDays: 14 },
      },
    });
    mocks.updateSettingsGroup.mockResolvedValue({
      success: true,
      data: { values: { siteName: 'Paginium' } },
    });
  });

  it('applies a live preview without calling the save API', async () => {
    const view = renderSettings('/settings?category=system&group=general');

    await waitFor(() => {
      expect(screen.getAllByRole('button', { name: /použiť/i }).length).toBeGreaterThan(0);
      expect(screen.getByDisplayValue('Paginium')).toBeInTheDocument();
    });

    await fastUser.click(screen.getAllByRole('button', { name: /použiť/i })[0]);

    await waitFor(() => {
      expect(mocks.applyPreview).toHaveBeenCalledWith({ general: { siteName: 'Paginium' } });
    });
    expect(mocks.updateSettingsGroup).not.toHaveBeenCalled();
    expect(mocks.toast.success).toHaveBeenCalled();

    view.unmount();
    expect(mocks.clearPreview).toHaveBeenCalled();
  });

  it('persists with save and reloads public settings', async () => {
    renderSettings('/settings?category=system&group=general');

    await waitFor(() => {
      expect(screen.getAllByRole('button', { name: /uložiť zmeny/i }).length).toBeGreaterThan(0);
      expect(screen.getByDisplayValue('Paginium')).toBeInTheDocument();
    });

    await fastUser.click(screen.getAllByRole('button', { name: /uložiť zmeny/i })[0]);

    await waitFor(() => {
      expect(mocks.updateSettingsGroup).toHaveBeenCalledWith('general', { siteName: 'Paginium' });
    });
    expect(mocks.clearPreviewGroup).toHaveBeenCalledWith('general');
    expect(mocks.reloadGlobalSettings).toHaveBeenCalled();
  });
});

function SearchParamsProbe({ onChange }: { onChange: (value: string) => void }) {
  const [params] = useSearchParams();
  onChange(params.toString());
  return null;
}
