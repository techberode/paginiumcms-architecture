// frontend/src/components/dashboard/HealthPanel.test.tsx
import { describe, it, expect } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '../../test/renderWithProviders';
import { HealthPanel } from './HealthPanel';

describe('HealthPanel', () => {
  it('shows loading spinner without data', () => {
    renderWithProviders(<HealthPanel health={null} loading />);
    expect(screen.getByText('Zdravie systému')).toBeInTheDocument();
  });

  it('renders health summary counts', () => {
    renderWithProviders(
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

    expect(screen.getByText('OK')).toBeInTheDocument();
    expect(screen.getByText('5')).toBeInTheDocument();
    expect(screen.getByText('1')).toBeInTheDocument();
  });
});
