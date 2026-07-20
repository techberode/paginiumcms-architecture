import { describe, it, expect, vi, beforeEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { Route, Routes } from 'react-router-dom';
import { AuditTrail } from './AuditTrail';
import { renderWithRouter } from '../../test/renderWithRouter';

const mocks = vi.hoisted(() => ({
  get: vi.fn(),
  toast: {
    error: vi.fn(),
    success: vi.fn(),
  },
}));

vi.mock('../../hooks/useApi', () => ({
  useApi: () => ({ get: mocks.get }),
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => mocks.toast,
}));

describe('AuditTrail deep links', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.get.mockResolvedValue({
      success: true,
      data: { events: [{ timestamp: '2026-07-20T08:00:00Z', log: { message: 'Updated page', severity: 'INFO', context: { action: 'update' } } }] },
    });
  });

  it('loads content audit from /audit/content/:contentId', async () => {
    renderWithRouter(
      <Routes>
        <Route path="/audit/content/:contentId" element={<AuditTrail />} />
      </Routes>,
      { routerProps: { initialEntries: ['/audit/content/page-home'] } }
    );

    await waitFor(() => {
      expect(mocks.get).toHaveBeenCalledWith('/api/admin/audit/content/page-home');
    });

    expect(await screen.findByText('Content Audit Trail')).toBeInTheDocument();
    expect(await screen.findByText('Updated page')).toBeInTheDocument();
  });

  it('loads user audit from /audit/user/:userId', async () => {
    renderWithRouter(
      <Routes>
        <Route path="/audit/user/:userId" element={<AuditTrail />} />
      </Routes>,
      { routerProps: { initialEntries: ['/audit/user/editor-1'] } }
    );

    await waitFor(() => {
      expect(mocks.get).toHaveBeenCalledWith('/api/admin/audit/user/editor-1');
    });

    expect(await screen.findByText('User Audit Trail')).toBeInTheDocument();
  });
});
