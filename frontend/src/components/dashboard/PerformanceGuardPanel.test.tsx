// frontend/src/components/dashboard/PerformanceGuardPanel.test.tsx
import React from 'react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { renderWithProviders } from '../../test/renderWithProviders';
import { PerformanceGuardPanel } from './PerformanceGuardPanel';
import type { ApmOverview } from '../../api/metrics';

const clearApmSamples = vi.fn();

vi.mock('../../api/metrics', async (importOriginal) => {
  const actual = await importOriginal<typeof import('../../api/metrics')>();
  return {
    ...actual,
    clearApmSamples: (...args: unknown[]) => clearApmSamples(...args),
  };
});

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
    toast: {},
  }),
}));

const enabledOverview: ApmOverview = {
  config: {
    enabled: true,
    sample_rate: 1,
    latency_ms_warning: 200,
    latency_ms_critical: 500,
    breach_count: 3,
    window_minutes: 10,
    remediation_mode: 'suggest',
  },
  summary: {
    sample_count: 42,
    error_rate: 0,
    p50_ms: 10,
    p95_ms: 120,
    p99_ms: 200,
    cache_hits: 0,
    cache_misses: 0,
    storage_reads: 3,
    storage_writes: 0,
    by_route: [],
  },
  recent_breaches: [],
  host_metrics_note: 'Host metrics note',
};

const renderPanel = (ui: React.ReactElement) =>
  renderWithProviders(<MemoryRouter>{ui}</MemoryRouter>);

describe('PerformanceGuardPanel', () => {
  beforeEach(() => {
    clearApmSamples.mockReset();
    vi.stubGlobal('confirm', vi.fn(() => true));
  });

  it('renders disabled state', () => {
    renderPanel(
      <PerformanceGuardPanel
        overview={{
          ...enabledOverview,
          config: { ...enabledOverview.config, enabled: false },
        }}
      />
    );

    expect(screen.getByText(/APM je vypnuté/i)).toBeInTheDocument();
  });

  it('clears samples when confirmed', async () => {
    const onRefresh = vi.fn();
    clearApmSamples.mockResolvedValue(true);
    const user = userEvent.setup();

    renderPanel(
      <PerformanceGuardPanel overview={enabledOverview} onRefresh={onRefresh} />
    );

    await user.click(screen.getByRole('button', { name: 'Vymazať vzorky' }));

    expect(clearApmSamples).toHaveBeenCalledTimes(1);
    expect(onRefresh).toHaveBeenCalledTimes(1);
  });

  it('hides clear button when there is nothing to clear', () => {
    renderPanel(
      <PerformanceGuardPanel
        overview={{
          ...enabledOverview,
          summary: { ...enabledOverview.summary, sample_count: 0 },
        }}
      />
    );

    expect(screen.queryByRole('button', { name: 'Vymazať vzorky' })).not.toBeInTheDocument();
  });
});
