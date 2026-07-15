// frontend/src/components/dashboard/AnalyticsChart.test.tsx
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { AnalyticsChart } from './AnalyticsChart';

describe('AnalyticsChart', () => {
  it('renders empty state', () => {
    render(<AnalyticsChart data={[]} />);
    expect(screen.getByText('No analytics data yet.')).toBeInTheDocument();
  });

  it('renders chart bars for data points', () => {
    render(
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
