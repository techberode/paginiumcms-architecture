import { describe, it, expect, vi } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { FeatureGalleryGrid } from './FeatureGalleryGrid';
import { renderWithProviders } from '../../test/renderWithProviders';

vi.mock('../../api/media', () => ({
  resolvePublicMediaUrl: (path: string) => path,
  resolvePublicMediaThumbnailUrl: (path: string, width: number) =>
    width > 0 ? `${path}?w=${width}` : path,
  MEDIA_THUMB_WIDTH: { card: 480, hero: 960, avatar: 128, gallery: 640 },
}));

describe('FeatureGalleryGrid', () => {
  it('opens modal on item click', async () => {
    const user = userEvent.setup();
    renderWithProviders(
      <FeatureGalleryGrid
        items={[
          {
            id: 'gallery_1',
            title: 'Analytics',
            description: 'Dashboard overview',
            mediaPath: '/storage/media/analytics.png',
            featureTag: 'analytics',
            linkUrl: null,
            sortOrder: 0,
            status: 'published',
            publishedAt: '2026-07-29T00:00:00+00:00',
            createdAt: '2026-07-29T00:00:00+00:00',
            updatedAt: '2026-07-29T00:00:00+00:00',
          },
        ]}
      />
    );

    await user.click(screen.getByRole('button', { name: /Analytics/i }));
    const dialog = screen.getByRole('dialog', { name: 'Analytics' });
    expect(dialog).toBeInTheDocument();
    expect(dialog).toHaveTextContent('Dashboard overview');
  });
});
