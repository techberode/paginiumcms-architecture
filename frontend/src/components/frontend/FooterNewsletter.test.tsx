import { describe, it, expect, vi } from 'vitest';
import { screen, fireEvent } from '@testing-library/react';
import { FooterNewsletter } from './FooterNewsletter';
import { renderWithRouter } from '../../test/renderWithRouter';

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

vi.mock('../../context/SettingsContext', async (importOriginal) => {
  const actual = await importOriginal<typeof import('../../context/SettingsContext')>();
  return {
    ...actual,
    useSettingsContext: () => ({
      settings: {
        newsletter: {
          footerEnabled: true,
          footerHint: 'Custom hint from settings',
          enabledPreferences: ['weekly_digest', 'general_news'],
        },
      },
      loading: false,
      get: vi.fn(),
      reload: vi.fn(),
    }),
  };
});

describe('FooterNewsletter', () => {
  it('renders compact CTA and opens modal on subscribe click', () => {
    renderWithRouter(<FooterNewsletter />);

    expect(screen.getByText('Newsletter')).toBeInTheDocument();
    expect(screen.getByText('Custom hint from settings')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Váš e-mail')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Prihlásiť sa/i })).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: /Prihlásiť sa/i }));

    expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(screen.getByText('Odber noviniek')).toBeInTheDocument();
  });
});
