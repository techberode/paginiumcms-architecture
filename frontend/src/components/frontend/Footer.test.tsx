import { describe, it, expect, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { Footer } from './Footer';
import { renderWithRouter } from '../../test/renderWithRouter';

vi.mock('../../context/PublicSiteContext', () => ({
  usePublicSite: () => ({
    navigation: [],
    siteTitle: 'PaginiumCMS',
    siteTagline: 'Flat-file CMS',
    footerText: '© Test',
  }),
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
    toast: {},
  }),
}));

vi.mock('../../api/newsletter', () => ({
  subscribeFooterNewsletter: vi.fn(),
}));

function mockSettings(overrides: Record<string, unknown> = {}) {
  return {
    settings: {
      demo: { enabled: false, url: 'https://demo.paginiumcms.com' },
      newsletter: { footerEnabled: false },
      ...overrides,
    },
    loading: false,
    get: vi.fn(),
    reload: vi.fn(),
  };
}

vi.mock('../../context/SettingsContext', async (importOriginal) => {
  const actual = await importOriginal<typeof import('../../context/SettingsContext')>();
  return {
    ...actual,
    useSettingsContext: vi.fn(() => mockSettings()),
  };
});

describe('Footer demo link', () => {
  it('opens demo URL in a new tab on production instance', async () => {
    const { useSettingsContext } = await import('../../context/SettingsContext');
    vi.mocked(useSettingsContext).mockReturnValue(mockSettings());

    renderWithRouter(<Footer />);

    const link = screen.getByRole('link', { name: /demo\.paginiumcms\.com/i });
    expect(link).toHaveAttribute('href', 'https://demo.paginiumcms.com');
    expect(link).toHaveAttribute('target', '_blank');
    expect(link).toHaveAttribute('rel', 'noopener noreferrer');
  });

  it('keeps demo link with target blank when footer newsletter is enabled', async () => {
    const { useSettingsContext } = await import('../../context/SettingsContext');
    vi.mocked(useSettingsContext).mockReturnValue(
      mockSettings({ newsletter: { footerEnabled: true, footerHint: '' } })
    );

    renderWithRouter(<Footer />);

    const link = screen.getByRole('link', { name: /demo\.paginiumcms\.com/i });
    expect(link).toHaveAttribute('target', '_blank');
    expect(screen.getByText('Newsletter')).toBeInTheDocument();
  });

  it('hides demo link on demo instance', async () => {
    const { useSettingsContext } = await import('../../context/SettingsContext');
    vi.mocked(useSettingsContext).mockReturnValue(
      mockSettings({ demo: { enabled: true, url: 'https://demo.paginiumcms.com' } })
    );

    renderWithRouter(<Footer />);

    expect(screen.queryByRole('link', { name: /demo\.paginiumcms\.com/i })).not.toBeInTheDocument();
  });
});
