import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { KanbanBoardView } from './KanbanBoardView';
import { renderWithProviders } from '../../test/renderWithProviders';
import { supportKanbanApi } from '../../api/kanban';

vi.mock('../../hooks/useToast', () => {
  const toast = { success: vi.fn(), error: vi.fn(), warning: vi.fn(), info: vi.fn() };
  return { useToast: () => toast };
});

vi.mock('../../api/kanban', () => ({
  supportKanbanApi: {
    list: vi.fn(async () => ({
      board: {
        schema: 'support-board@1',
        columns: [
          { id: 'open', label: 'Open', color: '#2c7be5' },
          { id: 'pending', label: 'Pending', color: '#f59e0b' },
        ],
        labels: [],
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
          labelIds: [],
          dueAt: null,
          createdAt: 1,
          updatedAt: 1,
        },
      ],
      agents: [],
    })),
    saveBoard: vi.fn(),
    createTicket: vi.fn(),
    updateTicket: vi.fn(async () => ({ success: true, data: { ticket: {} } })),
    removeTicket: vi.fn(),
  },
}));

describe('KanbanBoardView', () => {
  it('renders columns and a ticket card', async () => {
    renderWithProviders(<KanbanBoardView />);
    expect(await screen.findByTestId('kanban-board')).toBeInTheDocument();
    expect(await screen.findByTestId('kanban-card-tkt_aabbccddee')).toHaveTextContent('Broken form');
    expect(supportKanbanApi.list).toHaveBeenCalled();
  });

  it('opens an opaque ticket dialog', async () => {
    renderWithProviders(<KanbanBoardView />);
    fireEvent.click(await screen.findByTestId('kanban-card-tkt_aabbccddee'));
    const panel = await screen.findByTestId('kanban-ticket-modal');
    expect(panel).toHaveClass('admin-modal-panel');
    expect(panel).toHaveClass('bg-white');
  });

  it('opens settings tab', async () => {
    renderWithProviders(<KanbanBoardView />);
    fireEvent.click(await screen.findByRole('button', { name: /settings|nastavenia/i }));
    await waitFor(() => {
      expect(screen.getByTestId('kanban-save-board')).toBeInTheDocument();
    });
  });
});
