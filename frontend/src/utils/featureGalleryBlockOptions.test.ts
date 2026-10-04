import { describe, expect, it } from 'vitest';
import {
  galleryGridColumnClass,
  resolveFeatureGalleryBlockOptions,
} from './featureGalleryBlockOptions';

describe('resolveFeatureGalleryBlockOptions', () => {
  it('uses global settings when block attrs are empty', () => {
    expect(
      resolveFeatureGalleryBlockOptions({}, { layout: 'slider', modalCaptionStyle: 'side' })
    ).toEqual({
      layout: 'slider',
      columns: '3',
      modalCaptionStyle: 'side',
      effectPreset: 'subtle',
      autoplayEnabled: true,
      autoplayIntervalMs: 6000,
    });
  });

  it('overrides layout and columns per block', () => {
    expect(
      resolveFeatureGalleryBlockOptions(
        { layout: 'grid', columns: '4', 'modal-caption-style': 'overlay' },
        { layout: 'slider', modalCaptionStyle: 'below' }
      )
    ).toMatchObject({
      layout: 'grid',
      columns: '4',
      modalCaptionStyle: 'overlay',
    });
  });

  it('ignores invalid enum values', () => {
    expect(
      resolveFeatureGalleryBlockOptions({ layout: 'masonry', columns: '9' }, { layout: 'grid' })
    ).toMatchObject({
      layout: 'grid',
      columns: '3',
    });
  });
});

describe('galleryGridColumnClass', () => {
  it('maps column presets to tailwind grids', () => {
    expect(galleryGridColumnClass('2')).toContain('sm:grid-cols-2');
    expect(galleryGridColumnClass('4')).toContain('lg:grid-cols-4');
  });
});
