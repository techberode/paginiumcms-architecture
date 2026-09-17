import { describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { FeatureGallerySection } from './FeatureGallerySection';
import { renderWithProviders } from '../../test/renderWithProviders';
import type { GalleryItem } from '../../api/gallery';

vi.mock('../../api/gallery', () => ({
  listPublicGalleryItems: vi.fn(),
}));

vi.mock('../../api/media', () => ({
  resolvePublicMediaUrl: (path: string) => path,
  resolvePublicMediaThumbnailUrl: (path: string, width: number) =>
    width > 0 ? `${path}?w=${width}` : path,
  MEDIA_THUMB_WIDTH: { card: 480, hero: 960, avatar: 128, gallery: 640 },
}));

import { listPublicGalleryItems } from '../../api/gallery';

const items: GalleryItem[] = [
  {
    id: 'gallery_1',
    title: 'Analytics',
    description: 'Dashboard overview',
    mediaPath: '/storage/media/analytics.png',
    featureTag: 'web',
    linkUrl: null,
    sortOrder: 0,
    status: 'published',
    publishedAt: '2026-07-29T00:00:00+00:00',
    createdAt: '2026-07-29T00:00:00+00:00',
    updatedAt: '2026-07-29T00:00:00+00:00',
  },
  {
    id: 'gallery_2',
    title: 'Poster',
    description: 'Print work',
    mediaPath: '/storage/media/poster.png',
    featureTag: 'print',
    linkUrl: null,
    sortOrder: 1,
    status: 'published',
    publishedAt: '2026-07-29T00:00:00+00:00',
    createdAt: '2026-07-29T00:00:00+00:00',
    updatedAt: '2026-07-29T00:00:00+00:00',
  },
];

describe('FeatureGallerySection', () => {
  it('renders an in-body block without home chrome and pins the tag', async () => {
    vi.mocked(listPublicGalleryItems).mockResolvedValue(items);

    renderWithProviders(
      <MemoryRouter>
        <FeatureGallerySection variant="block" featureTag="web" heading="Selected work" />
      </MemoryRouter>
    );

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Selected work' })).toBeInTheDocument();
    });
    expect(screen.getByText('Analytics')).toBeInTheDocument();
    expect(screen.queryByText('Poster')).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /web/i })).not.toBeInTheDocument();
  });
});
