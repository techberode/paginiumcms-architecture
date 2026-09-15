import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { AdminGradientField } from './AdminGradientField';
import { renderWithProviders } from '../../test/renderWithProviders';

describe('AdminGradientField', () => {
  it('toggles gradient and picks a direction', () => {
    const onEnabledChange = vi.fn();
    const onDirectionChange = vi.fn();
    renderWithProviders(
      <AdminGradientField
        id="gradient"
        label="Gradient"
        enabled={false}
        direction="to-bottom"
        onEnabledChange={onEnabledChange}
        onDirectionChange={onDirectionChange}
      />
    );

    fireEvent.click(screen.getByRole('checkbox'));
    expect(onEnabledChange).toHaveBeenCalledWith(true);

    fireEvent.click(screen.getByTitle('Doprava'));
    expect(onDirectionChange).toHaveBeenCalledWith('to-right');
  });
});
