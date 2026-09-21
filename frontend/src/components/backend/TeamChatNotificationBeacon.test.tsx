import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { TeamChatNotificationBeacon } from './TeamChatNotificationBeacon';
import { renderWithRouter } from '../../test/renderWithRouter';

vi.mock('../../hooks/useTeamChatInbox', () => ({
  useTeamChatInbox: () => ({
    ready: true,
    hasAccess: true,
    count: 2,
    items: [
      {
        teamId: 'team_a',
        teamName: 'Support',
        unread: 1,
        preview: 'Hello',
        lastMessageAt: 1,
      },
      {
        teamId: 'team_b',
        teamName: 'Devs',
        unread: 1,
        preview: 'Patch ready',
        lastMessageAt: 2,
      },
    ],
  }),
}));

describe('TeamChatNotificationBeacon', () => {
  it('opens a queue for multiple rooms', async () => {
    renderWithRouter(<TeamChatNotificationBeacon variant="header" />);

    fireEvent.click(screen.getByTestId('team-chat-beacon-button'));
    expect(await screen.findByTestId('team-chat-beacon-panel')).toBeInTheDocument();
    expect(screen.getByText('Support')).toBeInTheDocument();
    expect(screen.getByText('Devs')).toBeInTheDocument();
  });
});
