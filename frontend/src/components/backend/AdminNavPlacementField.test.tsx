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

    const selected = screen.getByRole('radio', { name: /bočné menu|side menu/i });
    const idle = screen.getByRole('radio', { name: /horné|top dropdown/i });
    expect(selected.className).toContain('admin-choice-on');
    expect(selected.className).not.toContain('admin-sidebar-active');
    expect(idle.className).toContain('admin-choice');
    expect(idle.className).not.toContain('admin-sidebar-hover');

    fireEvent.click(idle);
    expect(onChange).toHaveBeenCalledWith('top');
  });
});
