import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { AccountPublicProfileForm, type AccountPublicDraft } from './AccountPublicProfileForm';
import { renderWithProviders } from '../../test/renderWithProviders';
import { authApi } from '../../api/auth';

const toast = {
  success: vi.fn(),
  error: vi.fn(),
  warning: vi.fn(),
  info: vi.fn(),
};

vi.mock('../../hooks/useToast', () => ({
  useToast: () => toast,
}));

vi.mock('../../api/auth', () => ({
  authApi: {
    verifySocialAccount: vi.fn(),
  },
}));

function emptyDraft(overrides: Partial<AccountPublicDraft> = {}): AccountPublicDraft {
  return {
    address: { street: '', city: '', postal: '', country: '' },
    experience: [],
    education: [],
    socialAccounts: [
      {
        id: 'soc-1',
        platform: 'telegram',
        url: '@desk',
        label: 'Telegram',
        directChat: true,
        notify: false,
      },
    ],
    publish: {
      address: false,
      experience: false,
      education: false,
      phone: false,
      email: false,
      socials: false,
      contact: false,
      support: false,
    },
    chatEnabled: false,
    deskMailEnabled: false,
    deskBubbleEnabled: true,
    deskBubbleAnchor: 'right',
    deskBubbleX: 92,
    deskBubbleY: 50,
    ...overrides,
  };
}

describe('AccountPublicProfileForm', () => {
  it('verifies a social link and stamps verifiedAt on the draft', async () => {
    vi.mocked(authApi.verifySocialAccount).mockResolvedValue({
      success: true,
      data: {
        platform: 'telegram',
        normalizedUrl: 'https://t.me/desk',
        verifiedAt: 1_700_000_000,
        message: 'Chat link format is valid',
      },
    });

    const onChange = vi.fn();
    renderWithProviders(
      <AccountPublicProfileForm draft={emptyDraft()} onChange={onChange} onSave={vi.fn()} saving={false} />,
      { locale: 'en' }
    );

    fireEvent.click(screen.getByTestId('account-social-verify-soc-1'));

    await waitFor(() => {
      expect(authApi.verifySocialAccount).toHaveBeenCalledWith('telegram', '@desk');
    });
    expect(onChange).toHaveBeenCalledWith(
      expect.objectContaining({
        socialAccounts: [
          expect.objectContaining({
            id: 'soc-1',
            url: 'https://t.me/desk',
            verifiedAt: 1_700_000_000,
          }),
        ],
      })
    );
    expect(toast.success).toHaveBeenCalled();
  });

  it('fills the platform prefix when a brand icon is clicked', () => {
    const onChange = vi.fn();
    renderWithProviders(
      <AccountPublicProfileForm draft={emptyDraft()} onChange={onChange} onSave={vi.fn()} saving={false} />,
      { locale: 'en' }
    );

    fireEvent.click(screen.getByTestId('account-social-platform-soc-1-github'));

    expect(onChange).toHaveBeenCalledWith(
      expect.objectContaining({
        socialAccounts: [
          expect.objectContaining({
            platform: 'github',
            url: 'https://github.com/',
            verifiedAt: undefined,
          }),
        ],
      })
    );
  });

  it('clears verification when the URL changes', () => {
    const onChange = vi.fn();
    renderWithProviders(
      <AccountPublicProfileForm
        draft={emptyDraft({
          socialAccounts: [
            {
              id: 'soc-1',
              platform: 'telegram',
              url: 'https://t.me/desk',
              label: 'Telegram',
              directChat: true,
              notify: false,
              verifiedAt: 1_700_000_000,
            },
          ],
        })}
        onChange={onChange}
        onSave={vi.fn()}
        saving={false}
      />,
      { locale: 'en' }
    );

    fireEvent.change(screen.getByTestId('account-social-url-soc-1'), { target: { value: '@other' } });

    expect(onChange).toHaveBeenCalledWith(
      expect.objectContaining({
        socialAccounts: [expect.objectContaining({ url: '@other', verifiedAt: undefined })],
      })
    );
  });

  it('toggles Messages chat on the public card', () => {
    const onChange = vi.fn();
    renderWithProviders(
      <AccountPublicProfileForm draft={emptyDraft()} onChange={onChange} onSave={vi.fn()} saving={false} />,
      { locale: 'en' }
    );

    fireEvent.click(screen.getByTestId('account-chat-enabled'));
    expect(onChange).toHaveBeenCalledWith(expect.objectContaining({ chatEnabled: true }));
    fireEvent.click(screen.getByTestId('account-desk-mail-enabled'));
    expect(onChange).toHaveBeenCalledWith(expect.objectContaining({ deskMailEnabled: true }));
  });

  it('toggles the desk bubble and picks a fixed edge', () => {
    const onChange = vi.fn();
    renderWithProviders(
      <AccountPublicProfileForm draft={emptyDraft()} onChange={onChange} onSave={vi.fn()} saving={false} />,
      { locale: 'en' }
    );

    fireEvent.click(screen.getByTestId('account-desk-bubble-enabled'));
    expect(onChange).toHaveBeenCalledWith(expect.objectContaining({ deskBubbleEnabled: false }));
    fireEvent.click(screen.getByTestId('account-desk-anchor-top'));
    expect(onChange).toHaveBeenCalledWith(expect.objectContaining({ deskBubbleAnchor: 'top' }));
  });
});
