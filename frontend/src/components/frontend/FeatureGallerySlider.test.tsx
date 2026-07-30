import { describe, it, expect, vi } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { FeatureGallerySlider } from './FeatureGallerySlider';
import { renderWithProviders } from '../../test/renderWithProviders';

vi.mock('../../api/media', () => ({
  resolvePublicMediaUrl: (path: string) => path,
}));

const items = [
  {
    id: 'gallery_1',
    title: 'Analytics',
    description: 'Dashboard overview',
    mediaPath: '/storage/media/analytics.png',
    featureTag: 'analytics',
    linkUrl: null,
    sortOrder: 0,
    status: 'published' as const,
    publishedAt: '2026-07-29T00:00:00+00:00',
    createdAt: '2026-07-29T00:00:00+00:00',
    updatedAt: '2026-07-29T00:00:00+00:00',
  },
  {
    id: 'gallery_2',
    title: 'Newsletter',
    description: 'Footer subscribe',
    mediaPath: '/storage/media/newsletter.png',
    featureTag: 'newsletter',
    linkUrl: null,
    sortOrder: 1,
    status: 'published' as const,
    publishedAt: '2026-07-29T00:00:00+00:00',
    createdAt: '2026-07-29T00:00:00+00:00',
    updatedAt: '2026-07-29T00:00:00+00:00',
  },
];

describe('FeatureGallerySlider', () => {
  it('renders carousel region and opens modal', async () => {
    const user = userEvent.setup();
    renderWithProviders(
      <FeatureGallerySlider items={items} autoplayEnabled={false} effectPreset="minimal" />
    );

    expect(screen.getByRole('region')).toBeInTheDocument();
    await user.click(screen.getByRole('button', { name: /Analytics/i }));
    expect(screen.getByRole('dialog', { name: 'Analytics' })).toBeInTheDocument();
  });

  it('navigates with next control', async () => {
    const user = userEvent.setup();
    renderWithProviders(
      <FeatureGallerySlider items={items} autoplayEnabled={false} effectPreset="minimal" />
    );

    await user.click(screen.getByRole('button', { name: /Next screenshot|Ďalší screenshot/i }));
    const newsletterSlide = screen.getByRole('button', { name: /Newsletter/i });
    expect(newsletterSlide).toHaveAttribute('aria-current', 'true');
  });
});
