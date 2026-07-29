// frontend/src/components/dashboard/AnalyticsChart.test.tsx
import { describe, it, expect } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '../../test/renderWithProviders';
import { AnalyticsChart } from './AnalyticsChart';

describe('AnalyticsChart', () => {
  it('renders empty state when no data', () => {
    renderWithProviders(<AnalyticsChart data={[]} />);
    expect(screen.getByText(/žiadne dáta|No analytics data yet/i)).toBeInTheDocument();
  });

  it('renders empty state when all points are zero', () => {
    renderWithProviders(
      <AnalyticsChart
        data={[
          { date: '2026-07-01', visits: 0, page_views: 0 },
          { date: '2026-07-02', visits: 0, page_views: 0 },
        ]}
      />
    );
    expect(screen.getByText(/žiadne dáta|No analytics data yet/i)).toBeInTheDocument();
  });

  it('renders chart bars for data points', () => {
    renderWithProviders(
      <AnalyticsChart
        data={[
          { date: '2026-07-01', visits: 10, page_views: 12 },
          { date: '2026-07-02', visits: 20, page_views: 25 },
        ]}
      />
    );
    expect(screen.getByText('10')).toBeInTheDocument();
    expect(screen.getByText('20')).toBeInTheDocument();
  });
});
