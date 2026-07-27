import { describe, it, expect, vi } from 'vitest';
import { screen } from '@testing-library/react';
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
        },
      },
      loading: false,
      get: vi.fn(),
      reload: vi.fn(),
    }),
  };
});

describe('FooterNewsletter', () => {
  it('renders signup form when footer newsletter is enabled', () => {
    renderWithRouter(<FooterNewsletter />);

    expect(screen.getByText('Newsletter')).toBeInTheDocument();
    expect(screen.getByText('Custom hint from settings')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Váš e-mail')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Prihlásiť sa' })).toBeInTheDocument();
  });
});
