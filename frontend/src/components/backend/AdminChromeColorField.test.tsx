import { describe, expect, it } from 'vitest';
import { screen } from '@testing-library/react';
import { AdminChromeColorField } from './AdminChromeColorField';
import { renderWithProviders } from '../../test/renderWithProviders';

describe('AdminChromeColorField', () => {
  it('renders eight color choices and reports the selected name', () => {
    renderWithProviders(
      <AdminChromeColorField id="sidebar-color" label="Sidebar color" value="navy" onChange={() => undefined} />
    );

    expect(screen.getAllByRole('radio')).toHaveLength(8);
    expect(screen.getByRole('radio', { name: /navy|námornícka/i })).toHaveAttribute('aria-checked', 'true');
  });
});
