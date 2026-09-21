import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { DeskNotificationBeacon } from './DeskNotificationBeacon';
import { DeskInboxProvider } from '../../context/DeskInboxContext';
import { renderWithProviders } from '../../test/renderWithProviders';
import { authApi } from '../../api/auth';
import { AuthContext } from '../../context/AuthContext';
import type { User } from '../../api/types';

vi.mock('../../api/auth', () => ({
  authApi: {
    desk: vi.fn(),
  },
}));

const user: User = {
  id: 'user_1',
  email: 'ada@example.com',
  name: 'Ada',
  roles: ['EDITOR'],
  twoFactorEnabled: false,
  createdAt: 1,
  updatedAt: 1,
  deskBubbleEnabled: false,
};

function renderBeacon() {
  return renderWithProviders(
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
        <DeskInboxProvider>
          <DeskNotificationBeacon variant="header" />
        </DeskInboxProvider>
      </MemoryRouter>
    </AuthContext.Provider>,
    { locale: 'en' }
  );
}

describe('DeskNotificationBeacon', () => {
  beforeEach(() => {
    vi.mocked(authApi.desk).mockResolvedValue({
      success: true,
      data: {
        chatEnabled: true,
        online: false,
        lastSeen: 0,
        inSupportTeam: true,
        canPublicChat: true,
        hasDesk: true,
        canReplyComments: true,
        deskCount: 2,
        deskBubbleEnabled: false,
        items: [
          {
            kind: 'comment',
            id: 'comment_1',
            title: 'Reader',
            preview: 'Please clarify',
            href: '/comments#comment-comment_1',
            createdAt: '2026-09-19T00:00:00+00:00',
          },
          {
            kind: 'message',
            id: 'msg_1',
            title: 'Support',
            preview: 'Notebook',
            href: '/messages#message-msg_1',
            createdAt: '2026-09-19T00:00:00+00:00',
          },
        ],
      },
    });
  });

  it('shows a pulsing count when the bubble is off and opens the queue', async () => {
    renderBeacon();

    expect(await screen.findByTestId('desk-beacon-count')).toHaveTextContent('2');
    fireEvent.click(screen.getByTestId('desk-beacon-button'));
    expect(await screen.findByTestId('desk-beacon-panel')).toHaveTextContent('Please clarify');
  });

  it('stays hidden while the floating bubble is enabled', async () => {
    vi.mocked(authApi.desk).mockResolvedValue({
      success: true,
      data: {
        chatEnabled: true,
        online: false,
        lastSeen: 0,
        inSupportTeam: true,
        canPublicChat: true,
        hasDesk: true,
        canReplyComments: true,
        deskCount: 1,
        deskBubbleEnabled: true,
        items: [
          {
            kind: 'comment',
            id: 'comment_1',
            title: 'Reader',
            preview: 'Hi',
            href: '/comments#comment-comment_1',
            createdAt: '2026-09-19T00:00:00+00:00',
          },
        ],
      },
    });

    renderBeacon();

    await waitFor(() => {
      expect(authApi.desk).toHaveBeenCalled();
    });
    expect(screen.queryByTestId('desk-beacon')).not.toBeInTheDocument();
  });
});
