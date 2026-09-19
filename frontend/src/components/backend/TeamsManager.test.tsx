import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { TeamsManager } from './TeamsManager';
import { renderWithProviders } from '../../test/renderWithProviders';
import { teamsApi } from '../../api/teams';

vi.mock('../../hooks/useToast', () => {
  const toast = {
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  };
  return { useToast: () => toast };
});

vi.mock('../../api/teams', async (importOriginal) => {
  const actual = await importOriginal<typeof import('../../api/teams')>();
  return {
    ...actual,
    teamsApi: {
    list: vi.fn(async () => ({
      teams: [
        {
          id: 'team_aabbccddee',
          name: 'Helpdesk',
          type: 'support',
          memberUserIds: ['user_1'],
          members: [{ id: 'user_1', name: 'Ada', username: 'ada', email: 'ada@example.com', active: true }],
          createdAt: 1,
          updatedAt: 1,
        },
        {
          id: 'team_external01',
          name: 'Web development',
          type: 'external',
          memberUserIds: [],
          members: [],
          createdAt: 1,
          updatedAt: 1,
        },
      ],
      types: ['editorial', 'support', 'ops', 'external', 'custom'],
      users: [
        { id: 'user_1', name: 'Ada', username: 'ada', email: 'ada@example.com', active: true },
        { id: 'user_2', name: 'Bob', username: 'bob', email: 'bob@example.com', active: false },
      ],
    })),
      create: vi.fn(),
      update: vi.fn(),
      remove: vi.fn(),
    },
  };
});

describe('TeamsManager', () => {
  it('lists teams and opens the member picker', async () => {
    renderWithProviders(<TeamsManager />);

    expect(await screen.findByTestId('teams-manager')).toBeInTheDocument();
    const card = await screen.findByTestId('team-card-team_aabbccddee');
    expect(card).toHaveTextContent('Support');
    fireEvent.click(card);

    await waitFor(() => {
      expect(screen.getByTestId('team-type')).toHaveValue('support');
    });
    expect(screen.queryByTestId('team-name')).not.toBeInTheDocument();
    expect(screen.getByTestId('team-member-user_1')).toBeChecked();
    expect(screen.getByTestId('team-member-user_2')).not.toBeChecked();
    expect(screen.getByTestId('team-chat-enabled')).toBeChecked();
    expect(screen.getByTestId('team-reply-mail-enabled')).not.toBeChecked();
    fireEvent.click(screen.getByTestId('team-reply-mail-enabled'));
    expect(screen.getByTestId('team-reply-mail')).toBeInTheDocument();
    expect(teamsApi.list).toHaveBeenCalled();
  });

  it('shows a custom name field when type is custom', async () => {
    renderWithProviders(<TeamsManager />);

    fireEvent.click(await screen.findByRole('button', { name: 'Nový tím' }));
    fireEvent.change(screen.getByTestId('team-type'), { target: { value: 'custom' } });

    const name = await screen.findByTestId('team-name');
    expect(name).toHaveAttribute('placeholder', 'napr. Marketing');
    fireEvent.change(name, { target: { value: 'Marketing' } });
    expect(name).toHaveValue('Marketing');
  });

  it('requires a purpose name for an external team', async () => {
    renderWithProviders(<TeamsManager />);

    fireEvent.click(await screen.findByRole('button', { name: 'Nový tím' }));
    fireEvent.change(screen.getByTestId('team-type'), { target: { value: 'external' } });

    const name = await screen.findByTestId('team-name');
    expect(name).toHaveAttribute('placeholder', 'napr. Vývoj webu');
    expect(screen.getByTestId('team-color-custom')).toBeInTheDocument();
    expect(screen.getByTestId('team-card-team_external01')).toHaveTextContent('Externý');
  });
});
