import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { AdminNavPlacementField } from './AdminNavPlacementField';
import { renderWithProviders } from '../../test/renderWithProviders';

describe('AdminNavPlacementField', () => {
  it('marks the current placement and lets hover-select the other', () => {
    const onChange = vi.fn();
    renderWithProviders(
      <AdminNavPlacementField label="Menu placement" value="side" onChange={onChange} />
    );

    expect(screen.getByRole('radio', { name: /bočné menu|side menu/i })).toHaveAttribute(
      'aria-checked',
      'true'
    );
    expect(screen.getByText(/aktívne|selected/i)).toBeInTheDocument();

    fireEvent.click(screen.getByRole('radio', { name: /horné|top dropdown/i }));
    expect(onChange).toHaveBeenCalledWith('top');
  });
});
