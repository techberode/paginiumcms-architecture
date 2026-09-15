import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { AccountView } from './AccountView';
import { renderWithProviders } from '../../test/renderWithProviders';
import { AuthContext } from '../../context/AuthContext';
import type { User } from '../../api/types';
import { authApi } from '../../api/auth';

vi.mock('../../hooks/useToast', () => {
  const toast = {
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  };
  return { useToast: () => toast };
});

vi.mock('../../api/auth', () => ({
  authApi: {
    updateProfile: vi.fn(),
    uploadMyAvatar: vi.fn(),
    assignMyAvatarFromUrl: vi.fn(),
    removeMyAvatar: vi.fn(),
  },
}));

vi.mock('../auth/TwoFactorSettings', () => ({
  TwoFactorSettings: () => <div data-testid="two-factor-settings" />,
}));

vi.mock('../auth/ChangePasswordModal', () => ({
  ChangePasswordModal: ({ open }: { open: boolean }) => (open ? <div data-testid="change-password-modal" /> : null),
}));

vi.mock('./UserAvatarPicker', () => ({
  UserAvatarPicker: () => <div data-testid="account-avatar" />,
}));

const user: User = {
  id: 'user_1',
  email: 'ada@example.com',
  username: 'ada',
  name: 'Ada Lovelace',
  bio: '',
  jobTitle: 'Editor',
  phone: '',
  timezone: '',
  locale: '',
  notifyFailedLogin: true,
  notifySecurityIncident: true,
  roles: ['ADMIN'],
  twoFactorEnabled: false,
  createdAt: 1,
  updatedAt: 1,
};

function renderAccount(path = '/account') {
  const updateUser = vi.fn();
  const view = renderWithProviders(
    <AuthContext.Provider
      value={{
        user,
        loading: false,
        pendingTwoFactor: false,
        twoFactorSetupPending: false,
        login: vi.fn(),
        verifyTwoFactorLogin: vi.fn(),
        logout: vi.fn(),
        register: vi.fn(),
        verifyRegisterOtp: vi.fn(),
        resendRegisterOtp: vi.fn(),
        updateUser,
        refreshUser: vi.fn(),
      }}
    >
      <MemoryRouter initialEntries={[path]}>
        <Routes>
          <Route path="/account" element={<AccountView />} />
          <Route path="/account/public" element={<AccountView />} />
          <Route path="/account/security" element={<AccountView />} />
          <Route path="/account/preferences" element={<AccountView />} />
        </Routes>
      </MemoryRouter>
    </AuthContext.Provider>
  );

  return { ...view, updateUser };
}

describe('AccountView', () => {
  it('shows profile fields including job title', async () => {
    renderAccount('/account');
    expect(await screen.findByTestId('account-profile')).toBeInTheDocument();
    expect(screen.getByTestId('account-name')).toHaveValue('Ada Lovelace');
    expect(screen.getByTestId('account-job-title')).toHaveValue('Editor');
  });

  it('reuses the existing 2FA block on the security tab', async () => {
    renderAccount('/account/security');
    expect(await screen.findByTestId('two-factor-settings')).toBeInTheDocument();
    expect(screen.getByTestId('account-change-password')).toBeInTheDocument();
  });

  it('saves preference toggles', async () => {
    vi.mocked(authApi.updateProfile).mockResolvedValue({
      success: true,
      data: { user: { ...user, locale: 'en', notifyFailedLogin: false } },
    });

    const { updateUser } = renderAccount('/account/preferences');
    fireEvent.click(await screen.findByTestId('account-notify-login'));
    fireEvent.change(screen.getByTestId('account-locale'), { target: { value: 'en' } });
    fireEvent.click(screen.getByTestId('account-preferences-save'));

    await waitFor(() => {
      expect(authApi.updateProfile).toHaveBeenCalledWith({
        locale: 'en',
        notifyFailedLogin: false,
        notifySecurityIncident: true,
      });
    });
    expect(updateUser).toHaveBeenCalled();
  });

  it('saves opt-in public profile flags', async () => {
    vi.mocked(authApi.updateProfile).mockResolvedValue({
      success: true,
      data: { user: { ...user, publish: { contact: true, email: true } } },
    });

    renderAccount('/account/public');
    fireEvent.click(await screen.findByTestId('account-publish-contact'));
    fireEvent.click(screen.getByTestId('account-publish-email'));
    fireEvent.click(screen.getByTestId('account-public-save'));

    await waitFor(() => {
      expect(authApi.updateProfile).toHaveBeenCalledWith(
        expect.objectContaining({
          publish: expect.objectContaining({ contact: true, email: true }),
        })
      );
    });
  });
});
