import { describe, expect, it } from 'vitest';
import { ADMIN_SIDE_NAV_ACTIVE, ADMIN_SIDE_NAV_IDLE } from './adminUiClasses';

describe('adminUiClasses content pickers', () => {
  it('keeps settings group nav on canvas tokens, not sidebar chrome fills', () => {
    expect(ADMIN_SIDE_NAV_ACTIVE).toContain('admin-choice-on');
    expect(ADMIN_SIDE_NAV_IDLE).toContain('admin-choice');
    expect(ADMIN_SIDE_NAV_ACTIVE).not.toContain('admin-sidebar-active');
    expect(ADMIN_SIDE_NAV_IDLE).not.toContain('admin-sidebar-hover');
  });
});
