import { describe, it, expect, vi, beforeEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { TranslationSettingsPanel } from './TranslationSettingsPanel';
import { renderWithProviders } from '../../test/renderWithProviders';
import { fastUser } from '../../test/userEvent';

const mocks = vi.hoisted(() => ({
  status: vi.fn(),
  testConnection: vi.fn(),
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  },
}));

vi.mock('../../api/contentTranslations', () => ({
  contentTranslationsApi: {
    status: mocks.status,
    testConnection: mocks.testConnection,
  },
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => mocks.toast,
}));

describe('TranslationSettingsPanel', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.status.mockResolvedValue({
      success: true,
      data: {
        enabled: true,
        provider: 'libretranslate',
        quota: { day: '2026-09-19', used: 12, limit: 1000, remaining: 988 },
      },
    });
    mocks.testConnection.mockResolvedValue({
      success: true,
      data: { ok: true, provider: 'libretranslate' },
    });
  });

  it('shows quota and tests the provider connection', async () => {
    renderWithProviders(<TranslationSettingsPanel />, { locale: 'en' });

    expect(await screen.findByTestId('translation-settings-panel')).toBeInTheDocument();
    expect(screen.getByText(/own \(or compatible\) instance/i)).toBeInTheDocument();
    expect(screen.getByText(/DeepL and Google/i)).toBeInTheDocument();
    expect(screen.getByText(/12 \/ 1000/)).toBeInTheDocument();

    await fastUser.click(screen.getByRole('button', { name: 'Test connection' }));
    await waitFor(() => {
      expect(mocks.testConnection).toHaveBeenCalled();
    });
    expect(mocks.toast.success).toHaveBeenCalled();
  });
});
