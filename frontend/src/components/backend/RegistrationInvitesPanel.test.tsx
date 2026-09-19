import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { RegistrationInvitesPanel } from './RegistrationInvitesPanel';
import { renderWithProviders } from '../../test/renderWithProviders';
import { registrationInvitesApi } from '../../api/registrationInvites';

vi.mock('../../hooks/useToast', () => {
  const toast = { success: vi.fn(), error: vi.fn(), warning: vi.fn(), info: vi.fn() };
  return { useToast: () => toast };
});

vi.mock('../../api/registrationInvites', () => ({
  registrationInvitesApi: {
    list: vi.fn(async () => []),
    create: vi.fn(async () => ({
      success: true,
      data: {
        invite: {
          id: 'inv_aaaaaaaaaaaa',
          email: 'dev@example.com',
          teamId: '',
          source: 'admin',
          createdBy: 'user_1',
          createdAt: 1,
          expiresAt: 2,
          usedAt: 0,
          userId: '',
        },
        token: 'ab',
        url: '/register?invite=ab',
        mailed: true,
      },
    })),
    revoke: vi.fn(),
    peek: vi.fn(),
  },
}));

vi.mock('../../api/teams', () => ({
  teamsApi: {
    list: vi.fn(async () => ({
      teams: [{ id: 'team_aabbccddee', name: 'Web development', type: 'external', memberUserIds: [], members: [] }],
      types: ['external'],
      users: [],
    })),
  },
}));

describe('RegistrationInvitesPanel', () => {
  it('issues a one-time invite', async () => {
    renderWithProviders(<RegistrationInvitesPanel />);

    expect(await screen.findByTestId('registration-invites')).toBeInTheDocument();
    fireEvent.change(screen.getByTestId('registration-invite-email'), { target: { value: 'dev@example.com' } });
    fireEvent.click(screen.getByTestId('registration-invite-send'));

    await waitFor(() => {
      expect(registrationInvitesApi.create).toHaveBeenCalled();
    });
  });
});
