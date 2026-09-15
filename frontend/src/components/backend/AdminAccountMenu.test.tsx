import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { AdminAccountMenu } from './AdminAccountMenu';
import { renderWithProviders } from '../../test/renderWithProviders';
import { AuthContext } from '../../context/AuthContext';
import type { User } from '../../api/types';

const user: User = {
  id: 'user_1',
  email: 'ada@example.com',
  username: 'ada',
  name: 'Ada Lovelace',
  roles: ['ADMIN'],
  twoFactorEnabled: false,
  createdAt: 1,
  updatedAt: 1,
};

describe('AdminAccountMenu', () => {
  it('opens the current-user menu and offers account edit', () => {
    renderWithProviders(
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
          updateUser: vi.fn(),
          refreshUser: vi.fn(),
        }}
      >
        <MemoryRouter>
          <AdminAccountMenu variant="header" />
        </MemoryRouter>
      </AuthContext.Provider>
    );

    fireEvent.click(screen.getByTestId('admin-account-trigger'));
    const menu = screen.getByTestId('admin-account-dropdown');
    expect(menu).toBeInTheDocument();
    expect(menu).toHaveClass('admin-account-dropdown');
    expect(screen.getByRole('menuitem', { name: /upraviť účet|edit account/i })).toBeInTheDocument();
  });
});
