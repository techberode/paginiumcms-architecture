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

vi.mock('./NewsletterSubscribeModal', () => ({
  NewsletterSubscribeModal: ({ isOpen }: { isOpen: boolean }) =>
    isOpen ? (
      <div role="dialog">
        <h2>Odber noviniek</h2>
      </div>
    ) : null,
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
  it('renders inline email field and opens modal on submit', () => {
    renderWithRouter(<FooterNewsletter />);

    expect(screen.getByText('Newsletter')).toBeInTheDocument();
    expect(screen.getByText('Custom hint from settings')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Váš e-mail')).toBeInTheDocument();
    expect(screen.queryByText('Vybrať typy odberu…')).not.toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: /Prihlásiť sa/i }));

    expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(screen.getByText('Odber noviniek')).toBeInTheDocument();
  });

  it('opens modal when Enter is pressed in email field', () => {
    renderWithRouter(<FooterNewsletter />);

    const input = screen.getByPlaceholderText('Váš e-mail');
    fireEvent.change(input, { target: { value: 'test@example.com' } });
    fireEvent.keyDown(input, { key: 'Enter' });

    expect(screen.getByRole('dialog')).toBeInTheDocument();
  });
});
