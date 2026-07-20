import { describe, it, expect, vi, beforeEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { Route, Routes, useSearchParams } from 'react-router-dom';
import React from 'react';
import { SettingsView } from './SettingsView';
import { renderWithRouter } from '../../test/renderWithRouter';
import { fastUser } from '../../test/userEvent';

const mocks = vi.hoisted(() => ({
  getSettings: vi.fn(),
  updateSettingsGroup: vi.fn(),
  reloadGlobalSettings: vi.fn(),
  toast: {
    success: vi.fn(),
    error: vi.fn(),
  },
}));

vi.mock('../../api/settings', () => ({
  getSettings: mocks.getSettings,
  updateSettingsGroup: mocks.updateSettingsGroup,
  rulesFromSchema: () => ({}),
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => mocks.toast,
}));

vi.mock('../../hooks/useSettings', () => ({
  useSettings: () => ({ reload: mocks.reloadGlobalSettings }),
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
    renderSettings('/settings?group=logging');

    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'Logging' })).toHaveClass('border-indigo-600');
    });
    expect(screen.getByLabelText('Retention days')).toBeInTheDocument();
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
      { routerProps: { initialEntries: ['/settings?group=general'] } }
    );

    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'General' })).toHaveClass('border-indigo-600');
    });

    await fastUser.click(screen.getByRole('button', { name: 'Logging' }));

    await waitFor(() => {
      expect(latestSearch).toBe('group=logging');
    });
  });
});

function SearchParamsProbe({ onChange }: { onChange: (value: string) => void }) {
  const [params] = useSearchParams();
  onChange(params.toString());
  return null;
}
