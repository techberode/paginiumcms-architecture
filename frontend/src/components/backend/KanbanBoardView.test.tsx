import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { KanbanBoardView } from './KanbanBoardView';
import { renderWithRouter } from '../../test/renderWithRouter';
import { teamKanbanApi } from '../../api/kanban';

vi.mock('../../hooks/useToast', () => {
  const toast = { success: vi.fn(), error: vi.fn(), warning: vi.fn(), info: vi.fn() };
  return { useToast: () => toast };
});

vi.mock('../../hooks/useConfirm', () => ({
  useConfirm: () => vi.fn(async () => true),
}));

const mockBoard = {
  board: {
    schema: 'support-board@1',
    columns: [
      { id: 'open', label: 'Open', color: '#2c7be5' },
      { id: 'pending', label: 'Pending', color: '#f59e0b' },
    ],
    labels: [{ id: 'urgent', name: 'Urgent', color: '#ef4444' }],
  },
  tickets: [
    {
      schema: 'support-ticket@1',
      id: 'tkt_aabbccddee',
      subject: 'Broken form',
      body: '',
      columnId: 'open',
      order: 0,
      assigneeUserId: null,
      requesterName: '',
      requesterEmail: 'a@example.com',
      messageId: null,
      labelIds: ['urgent'],
      dueAt: 1_800_000_000,
      internalNotes: [],
      createdAt: 1,
      updatedAt: 1,
    },
  ],
  agents: [],
  cannedReplies: [{ id: 'cnd_aabbccddee', title: 'Need logs', body: 'Please attach the error log.' }],
  team: {
    id: 'team_aabbccddee',
    name: 'Support',
    color: '#2563eb',
    canManageBoard: true,
  },
};

vi.mock('../../api/kanban', () => ({
  teamKanbanApi: {
    listTeams: vi.fn(async () => ({
      ok: true,
      teams: [
        {
          id: 'team_aabbccddee',
          name: 'Support',
          color: '#2563eb',
          type: 'support',
          canManageBoard: true,
        },
      ],
    })),
    load: vi.fn(async () => ({
      ok: true,
      teams: mockBoard.team ? [{ ...mockBoard.team, type: 'support' }] : [],
      data: mockBoard,
    })),
    saveBoard: vi.fn(),
    saveCanned: vi.fn(async () => ({ success: true, data: { cannedReplies: [] } })),
    createTicket: vi.fn(),
    updateTicket: vi.fn(async () => ({ success: true, data: { ticket: {} } })),
    addNote: vi.fn(async () => ({
      success: true,
      data: {
        ticket: {
          schema: 'support-ticket@1',
          id: 'tkt_aabbccddee',
          subject: 'Broken form',
          body: '',
          columnId: 'open',
          order: 0,
          assigneeUserId: null,
          requesterName: '',
          requesterEmail: 'a@example.com',
          messageId: null,
          labelIds: ['urgent'],
          dueAt: 1_800_000_000,
          internalNotes: [
            { id: 'nte_aabbccddee', body: 'Called the customer', authorUserId: 'user_1', createdAt: 2 },
          ],
          createdAt: 1,
          updatedAt: 2,
        },
      },
    })),
    removeTicket: vi.fn(),
  },
}));

describe('KanbanBoardView', () => {
  it('renders columns and a ticket card', async () => {
    renderWithRouter(<KanbanBoardView />, {
      routerProps: { initialEntries: ['/kanban?team=team_aabbccddee'] },
    });
    expect(await screen.findByTestId('kanban-board')).toBeInTheDocument();
    expect(await screen.findByTestId('kanban-card-tkt_aabbccddee')).toHaveTextContent('Broken form');
    expect(await screen.findByTestId('kanban-team-badge')).toHaveTextContent('Support');
    expect(await screen.findByTestId('kanban-label-urgent')).toBeInTheDocument();
    expect(teamKanbanApi.load).toHaveBeenCalled();
  });

  it('opens ticket modal with due date and canned reply', async () => {
    renderWithRouter(<KanbanBoardView />, {
      routerProps: { initialEntries: ['/kanban?team=team_aabbccddee'] },
    });
    fireEvent.click(await screen.findByTestId('kanban-open-tkt_aabbccddee'));
    const panel = await screen.findByTestId('kanban-ticket-modal');
    expect(panel).toBeInTheDocument();
  });

  it('shows settings tab for team leaders', async () => {
    renderWithRouter(<KanbanBoardView />, {
      routerProps: { initialEntries: ['/kanban?team=team_aabbccddee'] },
    });
    await screen.findByTestId('kanban-board');
    fireEvent.click(screen.getByRole('button', { name: /nastavenia|settings/i }));
    await waitFor(() => {
      expect(screen.getByTestId('kanban-save-board')).toBeInTheDocument();
    });
    expect(screen.getByTestId('kanban-add-canned')).toBeInTheDocument();
  });

  it('adds internal note from modal', async () => {
    renderWithRouter(<KanbanBoardView />, {
      routerProps: { initialEntries: ['/kanban?team=team_aabbccddee'] },
    });
    fireEvent.click(await screen.findByTestId('kanban-open-tkt_aabbccddee'));
    expect(await screen.findByTestId('kanban-due-input')).toBeInTheDocument();
    fireEvent.change(screen.getByTestId('kanban-canned-select'), { target: { value: 'cnd_aabbccddee' } });
    fireEvent.click(screen.getByTestId('kanban-insert-canned'));
    fireEvent.change(screen.getByTestId('kanban-note-input'), { target: { value: 'Called the customer' } });
    fireEvent.click(screen.getByTestId('kanban-add-note'));
    await waitFor(() => expect(teamKanbanApi.addNote).toHaveBeenCalled());
  });
});
