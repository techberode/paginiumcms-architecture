// frontend/src/components/dashboard/LocksPanel.test.tsx
import { describe, it, expect, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '../../test/renderWithProviders';
import { LocksPanel } from './LocksPanel';

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
    toast: {},
  }),
}));

describe('LocksPanel', () => {
  it('renders empty state', () => {
    renderWithProviders(<LocksPanel locks={[]} onRefresh={vi.fn()} />);
    expect(screen.getByText('Žiadne aktívne zámky obsahu.')).toBeInTheDocument();
  });

  it('lists active locks', () => {
    renderWithProviders(
      <LocksPanel
        locks={[
          {
            resourceId: 'page:home',
            lockedBy: 'user-1',
            lockedByName: 'Admin',
            acquiredAt: Math.floor(Date.now() / 1000),
            lastHeartbeat: Math.floor(Date.now() / 1000),
            expiresAt: Math.floor(Date.now() / 1000) + 300,
          },
        ]}
        onRefresh={vi.fn()}
      />
    );

    expect(screen.getByText('page:home')).toBeInTheDocument();
    expect(screen.getByText(/Admin/)).toBeInTheDocument();
  });
});
