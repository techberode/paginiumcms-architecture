import { describe, it, expect, vi } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { FeatureGalleryTagFilter } from './FeatureGalleryTagFilter';
import { renderWithProviders } from '../../test/renderWithProviders';

describe('FeatureGalleryTagFilter', () => {
  it('calls onChange for all and tag chips', async () => {
    const user = userEvent.setup();
    const onChange = vi.fn();
    renderWithProviders(
      <FeatureGalleryTagFilter tags={['analytics', 'newsletter']} activeTag={null} onChange={onChange} />
    );

    await user.click(screen.getByRole('button', { name: 'analytics' }));
    expect(onChange).toHaveBeenCalledWith('analytics');

    await user.click(screen.getByRole('button', { name: /All|Všetko/i }));
    expect(onChange).toHaveBeenCalledWith(null);
  });
});
