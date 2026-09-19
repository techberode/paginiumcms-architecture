import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor, within } from '@testing-library/react';
import { ContactRoutingPanel } from './ContactRoutingPanel';
import { renderWithProviders } from '../../test/renderWithProviders';
import { messagesApi } from '../../api/messages';
import { teamsApi } from '../../api/teams';

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({ success: vi.fn(), error: vi.fn(), warning: vi.fn(), info: vi.fn() }),
}));

vi.mock('../../api/messages', () => ({
  messagesApi: {
    routing: vi.fn(),
    saveRouting: vi.fn(),
  },
}));

vi.mock('../../api/teams', () => ({
  teamsApi: {
    list: vi.fn(),
  },
}));

describe('ContactRoutingPanel', () => {
  it('saves subject routing for a team', async () => {
    vi.mocked(messagesApi.routing).mockResolvedValue({
      enabled: false,
      routes: [],
    });
    vi.mocked(messagesApi.saveRouting).mockResolvedValue(true);
    vi.mocked(teamsApi.list).mockResolvedValue({
      teams: [
        {
          id: 'team_1',
          name: 'Helpdesk',
          type: 'support',
          memberUserIds: ['user_1'],
          members: [],
          createdAt: 1,
          updatedAt: 1,
        },
      ],
      types: ['editorial', 'support', 'ops', 'custom'],
      users: [{ id: 'user_1', name: 'Ada', username: 'ada', email: 'ada@example.com', active: true }],
    });

    renderWithProviders(<ContactRoutingPanel subjects={'Technická podpora\nVšeobecný dotaz'} />, { locale: 'en' });

    expect(await screen.findByTestId('contact-routing-enabled')).toBeInTheDocument();
    fireEvent.click(screen.getByTestId('contact-routing-enabled'));
    const card = await screen.findByTestId('contact-route-Technická podpora');
    fireEvent.click(within(card).getByLabelText('Helpdesk'));
    fireEvent.click(screen.getByRole('button', { name: 'Save routing' }));

    await waitFor(() => {
      expect(messagesApi.saveRouting).toHaveBeenCalledWith(
        expect.objectContaining({
          enabled: true,
          routes: expect.arrayContaining([
            expect.objectContaining({
              subject: 'Technická podpora',
              teamIds: ['team_1'],
            }),
          ]),
        })
      );
    });
  });

  it('groups people into team accordions', async () => {
    vi.mocked(messagesApi.routing).mockResolvedValue({ enabled: false, routes: [] });
    vi.mocked(teamsApi.list).mockResolvedValue({
      teams: [
        {
          id: 'team_1',
          name: 'Helpdesk',
          type: 'support',
          memberUserIds: ['user_1'],
          members: [],
          createdAt: 1,
          updatedAt: 1,
        },
      ],
      types: ['support'],
      users: [
        { id: 'user_1', name: 'Ada', username: 'ada', email: 'ada@example.com', active: true },
        { id: 'user_2', name: 'Bob', username: 'bob', email: 'bob@example.com', active: true },
      ],
    });

    renderWithProviders(<ContactRoutingPanel subjects={'Všeobecný dotaz'} />, { locale: 'en' });
    const card = await screen.findByTestId('contact-route-Všeobecný dotaz');
    const group = within(card).getByTestId('contact-routing-people-team_1');
    expect(group).toBeInTheDocument();
    expect(within(card).getByTestId('contact-routing-people-other')).toHaveTextContent('No team');
    fireEvent.click(within(group).getByText('Helpdesk'));
    fireEvent.click(within(group).getByLabelText('Ada'));
    expect(within(group).getByLabelText('Ada')).toBeChecked();
  });
});
