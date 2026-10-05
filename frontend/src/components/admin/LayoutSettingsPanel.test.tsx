import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { LayoutSettingsPanel } from './LayoutSettingsPanel';
import { renderWithProviders } from '../../test/renderWithProviders';

vi.mock('../../hooks/useAuth', () => ({
  useAuth: () => ({ user: { roles: ['ADMIN'] } }),
}));

describe('LayoutSettingsPanel', () => {
  it('shows builder-mode help for the selected outline card', () => {
    const setValue = vi.fn();
    const values: Record<string, unknown> = {
      builderMode: 'templates',
      defaultTemplate: 'hero-content',
      developerRequiresAdmin: true,
    };

    renderWithProviders(
      <LayoutSettingsPanel
        watch={(name) => values[name]}
        setValue={(name, value, options) => {
          values[name] = value;
          setValue(name, value, options);
        }}
      />
    );

    expect(screen.getByTestId('layout-builder-help').textContent ?? '').toMatch(
      /named structure|pomenovanú štruktúru/i
    );

    fireEvent.click(screen.getByTestId('layout-builder-card-outline'));
    expect(setValue).toHaveBeenCalledWith('builderMode', 'outline', {
      shouldDirty: true,
      shouldValidate: true,
    });
  });

  it('exposes custom 404 and 500 slug fields', () => {
    const values: Record<string, unknown> = {
      builderMode: 'templates',
      defaultTemplate: 'hero-content',
      developerRequiresAdmin: true,
      notFoundPageSlug: '404',
    };

    renderWithProviders(
      <LayoutSettingsPanel
        watch={(name) => values[name]}
        setValue={(name, value) => {
          values[name] = value;
        }}
      />
    );

    expect(screen.getByTestId('layout-not-found-page-slug')).toHaveValue('404');
    fireEvent.change(screen.getByTestId('layout-not-found-page-slug'), { target: { value: 'strateny' } });
    expect(values.notFoundPageSlug).toBe('strateny');
  });
});
