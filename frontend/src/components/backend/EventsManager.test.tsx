import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { EventsManager } from './EventsManager';
import { renderWithProviders } from '../../test/renderWithProviders';
import { eventsApi } from '../../api/events';

vi.mock('../../hooks/useToast', () => {
  const toast = {
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  };
  return { useToast: () => toast };
});

vi.mock('../../api/events', () => ({
  eventsApi: {
    list: vi.fn(async () => ({
      events: [
        {
          schema: 'site-event@1',
          id: 'event_aabbccddee',
          title: 'Open day',
          slug: 'open-day',
          startsAt: 1_800_000_000,
          endsAt: null,
          location: 'Hall A',
          body: '',
          status: 'published',
          projectPlanId: null,
          createdAt: 1,
          updatedAt: 1,
        },
      ],
      statuses: ['draft', 'published'],
    })),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  },
  EVENT_STATUSES: ['draft', 'published'],
}));

vi.mock('../../api/projectPlanner', () => ({
  projectPlannerApi: {
    list: vi.fn(async () => ({ success: true, data: { plans: [], count: 0 } })),
  },
}));

describe('EventsManager', () => {
  it('lists events and opens the editor', async () => {
    renderWithProviders(<EventsManager />);

    expect(await screen.findByTestId('events-manager')).toBeInTheDocument();
    const row = await screen.findByTestId('event-row-event_aabbccddee');
    expect(row).toHaveTextContent('Open day');
    fireEvent.click(row);

    await waitFor(() => {
      expect(screen.getByTestId('event-title')).toHaveValue('Open day');
    });
    expect(screen.getByTestId('event-status')).toHaveValue('published');
    expect(eventsApi.list).toHaveBeenCalled();
  });

  it('switches to the month calendar', async () => {
    renderWithProviders(<EventsManager />);
    fireEvent.click(await screen.findByTestId('events-view-calendar'));
    expect(await screen.findByTestId('events-calendar')).toBeInTheDocument();
  });
});
