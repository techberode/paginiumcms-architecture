import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { RegistrationOptionsPanel } from './RegistrationOptionsPanel';
import { renderWithProviders } from '../../test/renderWithProviders';
import { registrationOptionsApi } from '../../api/registrationOptions';

vi.mock('../../hooks/useToast', () => {
  const toast = { success: vi.fn(), error: vi.fn(), warning: vi.fn(), info: vi.fn() };
  return { useToast: () => toast };
});

vi.mock('../../api/registrationOptions', () => ({
  registrationOptionsApi: {
    list: vi.fn(async () => ({
      options: [
        {
          id: 'regopt_aaaaaaaa',
          label: 'Publicist',
          roleId: 'USER',
          enabled: true,
          requireAdminApproval: true,
          assignTeamId: '',
          welcomeMailEnabled: true,
          welcomeMailSubject: 'Hi',
          welcomeMailBody: 'Welcome',
        },
      ],
      roles: [{ id: 'USER', name: 'User' }],
    })),
    save: vi.fn(async (options) => ({ success: true, data: { options } })),
  },
}));

vi.mock('../../api/teams', () => ({
  teamsApi: {
    list: vi.fn(async () => ({
      teams: [{ id: 'team_aabbccddee', name: 'External', type: 'external', memberUserIds: [], members: [] }],
      types: ['external'],
      users: [],
    })),
  },
}));

describe('RegistrationOptionsPanel', () => {
  it('loads types and saves an edited label', async () => {
    renderWithProviders(<RegistrationOptionsPanel />);

    expect(await screen.findByTestId('registration-options')).toBeInTheDocument();
    const label = await screen.findByTestId('registration-option-label-0');
    expect(label).toHaveValue('Publicist');
    fireEvent.change(label, { target: { value: 'Publicista' } });
    fireEvent.click(screen.getByTestId('registration-options-save'));

    await waitFor(() => {
      expect(registrationOptionsApi.save).toHaveBeenCalled();
    });
  });
});
