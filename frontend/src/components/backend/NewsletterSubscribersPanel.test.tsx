import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { NewsletterSubscribersPanel } from './NewsletterSubscribersPanel';
import { renderWithRouter } from '../../test/renderWithRouter';

const mocks = vi.hoisted(() => ({
  list: vi.fn(),
  exportCsv: vi.fn(),
  sendStatus: vi.fn(),
}));

vi.mock('../../utils/debugLog', () => ({
  debugLog: vi.fn(),
  debugLogApi: vi.fn(),
  debugLogProvider: vi.fn(),
  isDebugEnabled: () => false,
  shouldSendDebugToBackend: () => false,
  initDebugMonitoring: vi.fn(),
  logFrontendStartup: vi.fn(),
  resetDebugMonitoringForTests: vi.fn(),
}));

vi.mock('./NewsletterSettingsPanel', () => ({
  NewsletterSettingsPanel: () => null,
}));

vi.mock('../../api/newsletter', () => ({
  listNewsletterSubscribers: mocks.list,
  exportNewsletterSubscribersCsv: mocks.exportCsv,
  fetchNewsletterSendStatus: mocks.sendStatus,
  sendNewsletterWeeklyDigestNow: vi.fn(),
  sendNewsletterTestEmail: vi.fn(),
}));

vi.mock('../../hooks/useAuth', () => ({
  useAuth: () => ({ user: { roles: ['ADMIN'] } }),
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
    toast: {},
  }),
}));

vi.mock('../../hooks/useAdminListPageSize', () => ({
  useAdminListPageSize: () => [20, vi.fn()],
}));

describe('NewsletterSubscribersPanel', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.list.mockResolvedValue({
      items: [
        {
          id: 'nl_1',
          email: 'user@example.com',
          subscribedAt: '2026-07-27T10:00:00+00:00',
          source: 'footer',
        },
      ],
      count: 1,
      bySource: { footer: 1 },
    });
    mocks.sendStatus.mockResolvedValue({
      configured: true,
      sendEnabled: false,
      weeklyDigestEnabled: false,
      newArticleEnabled: false,
      lastWeeklyDigestAt: null,
    });
  });

  afterEach(async () => {
    await Promise.resolve();
  });

  it('renders subscriber table', async () => {
    renderWithRouter(<NewsletterSubscribersPanel />);

    expect(screen.getByText('Newsletter — odberatelia')).toBeInTheDocument();

    await waitFor(() => {
      expect(mocks.list).toHaveBeenCalled();
    });

    await waitFor(() => {
      expect(screen.getByText('user@example.com')).toBeInTheDocument();
    });

    expect(mocks.list).toHaveBeenCalled();
  });
});
