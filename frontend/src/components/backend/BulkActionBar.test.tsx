// frontend/src/components/backend/BulkActionBar.test.tsx
import { describe, it, expect, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { BulkActionBar } from './BulkActionBar';
import { renderWithRouter } from '../../test/renderWithRouter';

describe('BulkActionBar', () => {
  it('shows selected of total when totalCount is provided', () => {
    renderWithRouter(
      <BulkActionBar
        count={3}
        totalCount={47}
        onClear={vi.fn()}
        actions={[{ id: 'delete', label: 'Delete', variant: 'danger', onClick: vi.fn() }]}
      />
    );

    expect(screen.getByText(/3 z 47 vybraných/i)).toBeInTheDocument();
  });
});
