import { describe, expect, it } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { renderWithProviders } from '../../test/renderWithProviders';
import { AdminInboxList, AdminInboxListHeader, AdminInboxRow } from './AdminInboxList';
import { AdminListToolbar } from './AdminListToolbar';

describe('admin list chrome (It.93c)', () => {
  it('uses admin card tokens on inbox and toolbar', () => {
    const { container } = renderWithProviders(
      <div>
        <AdminListToolbar search="" onSearchChange={() => undefined} />
        <AdminInboxList>
          <AdminInboxListHeader allSelected={false} onToggleAll={() => undefined} />
          <AdminInboxRow
            id="1"
            index={0}
            expanded={false}
            onToggleExpand={() => undefined}
            selected={false}
            onToggleSelect={() => undefined}
            summary="Hello"
            detail="Detail"
          />
        </AdminInboxList>
      </div>
    );

    expect(container.querySelector('.border-admin-border')).toBeTruthy();
    expect(container.querySelector('.bg-admin-card')).toBeTruthy();
    expect(container.querySelector('.admin-row-hover')).toBeTruthy();
    expect(container.querySelector('.hover\\:bg-admin-sidebar-active')).toBeNull();
  });

  it('clears search from the toolbar button', () => {
    let query = 'draft';
    renderWithProviders(
      <AdminListToolbar search={query} onSearchChange={(value) => { query = value; }} />
    );
    fireEvent.click(screen.getByTestId('list-search-clear'));
    expect(query).toBe('');
  });
});
