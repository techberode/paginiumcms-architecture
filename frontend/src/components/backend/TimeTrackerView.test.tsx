import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { TimeTrackerView } from './TimeTrackerView';
import { renderWithProviders } from '../../test/renderWithProviders';

vi.mock('../../hooks/useToast', () => {
  const toast = {
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  };
  return { useToast: () => toast };
});

vi.mock('../../api/timeEntries', () => ({
  timeEntriesApi: {
    list: vi.fn(async () => ({
      entries: [
        {
          schema: 'time-entry@1',
          id: 'time_aabbccddee',
          userId: 'user_1',
          target: 'event',
          planId: null,
          planItemId: null,
          eventId: 'event_aabbccddee',
          startedAt: Math.floor(Date.now() / 1000) - 90,
          endedAt: Math.floor(Date.now() / 1000),
          seconds: 90,
          note: 'Kickoff',
          label: 'Open day',
          createdAt: 1,
          updatedAt: 1,
        },
      ],
      running: null,
      summary: { todaySeconds: 90, weekSeconds: 90 },
      canSeeTeam: false,
      targets: ['planItem', 'event'],
    })),
    targets: vi.fn(async () => ({
      events: [{ id: 'event_aabbccddee', title: 'Open day' }],
      plans: [{ id: 'launch-site', title: 'Launch', items: [{ id: 'item-home', title: 'Home' }] }],
      pages: [],
      articles: [],
    })),
    start: vi.fn(),
    stop: vi.fn(),
    updateNote: vi.fn(),
    remove: vi.fn(),
  },
  TIME_TARGETS: ['planItem', 'event', 'content'],
}));

describe('TimeTrackerView', () => {
  it('shows the timer and a logged entry', async () => {
    renderWithProviders(<TimeTrackerView />);

    expect(await screen.findByTestId('time-tracker')).toBeInTheDocument();
    expect(screen.getByTestId('time-tracker-clock')).toHaveTextContent('00:00:00');
    expect(await screen.findByTestId('time-row-time_aabbccddee')).toHaveTextContent('Open day');
    fireEvent.change(screen.getByTestId('time-target'), { target: { value: 'event' } });
    expect(screen.getByTestId('time-event')).toHaveValue('event_aabbccddee');
  });
});
