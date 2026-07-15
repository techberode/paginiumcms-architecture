// frontend/src/components/dashboard/HealthPanel.test.tsx
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { HealthPanel } from './HealthPanel';

describe('HealthPanel', () => {
  it('shows loading spinner without data', () => {
    render(<HealthPanel health={null} loading />);
    expect(screen.getByText('System health')).toBeInTheDocument();
  });

  it('renders health summary counts', () => {
    render(
      <HealthPanel
        health={{
          id: 'health-1',
          timestamp: new Date().toISOString(),
          status: 'pass',
          summary: { pass: 5, warn: 1, fail: 0, total: 6, skip: 0 },
          checks: [],
        }}
      />
    );

    expect(screen.getByText('Pass')).toBeInTheDocument();
    expect(screen.getByText('5')).toBeInTheDocument();
    expect(screen.getByText('1')).toBeInTheDocument();
  });
});
