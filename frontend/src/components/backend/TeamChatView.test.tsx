import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { TeamChatView } from './TeamChatView';
import { renderWithRouter } from '../../test/renderWithRouter';
import { teamChatApi } from '../../api/teamChat';

vi.mock('../../hooks/useToast', () => {
  const toast = { success: vi.fn(), error: vi.fn(), warning: vi.fn(), info: vi.fn() };
  return { useToast: () => toast };
});

vi.mock('../../api/teamChat', () => ({
  teamChatApi: {
    rooms: vi.fn(async () => [{ id: 'team_aabbccddee', name: 'Developers' }]),
    messages: vi.fn(async () => [
      {
        id: 'tcm_1',
        teamId: 'team_aabbccddee',
        authorUserId: 'user_1',
        authorName: 'Ada',
        kind: 'text',
        body: 'Hello **team**',
        createdAt: 1,
      },
    ]),
    post: vi.fn(async () => ({ success: true, data: { message: { id: 'tcm_2' } } })),
    upload: vi.fn(),
    downloadUrl: (teamId: string, fileId: string) => `/api/team-chat/${teamId}/files/${fileId}`,
  },
}));

describe('TeamChatView', () => {
  it('lists rooms and posts markdown', async () => {
    renderWithRouter(<TeamChatView />);

    expect(await screen.findByTestId('team-chat')).toBeInTheDocument();
    expect(await screen.findByTestId('team-chat-room-team_aabbccddee')).toHaveTextContent('Developers');
    expect(await screen.findByTestId('team-chat-messages')).toHaveTextContent('Ada');

    fireEvent.change(screen.getByTestId('team-chat-body'), { target: { value: 'Ship it' } });
    fireEvent.click(screen.getByTestId('team-chat-send'));

    await waitFor(() => {
      expect(teamChatApi.post).toHaveBeenCalledWith('team_aabbccddee', {
        kind: 'text',
        body: 'Ship it',
        language: '',
      });
    });
  });
});
