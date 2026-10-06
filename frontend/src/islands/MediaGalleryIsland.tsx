import React, { useCallback, useMemo, useState } from 'react';
import { resolvePublicMediaThumbnailUrl, MEDIA_THUMB_WIDTH } from '../api/media';
import { PaginiumMediaGallery } from '../components/frontend/mediaGallery/PaginiumMediaGallery';
import type { GalleryModalCaptionStyle } from '../utils/featureGalleryBlockOptions';
import { useI18n } from '../context/I18nContext';
import {
  mediaGalleryMarkupToSlides,
  parseMediaGalleryMarkup,
} from '../utils/mediaGalleryMarkup';
import type { IslandProps } from './publicIslandDefinitions';

const CAPTIONS: readonly GalleryModalCaptionStyle[] = ['below', 'overlay', 'side'];

function pickCaption(raw: string | undefined): GalleryModalCaptionStyle {
  const value = raw?.trim() ?? '';
  return (CAPTIONS as readonly string[]).includes(value) ? (value as GalleryModalCaptionStyle) : 'below';
}

function columnClass(columns: string | undefined): string {
  const value = columns?.trim() ?? '3';
  if (value === '2' || value === '4') {
    return `pg-media-gallery-cols-${value}`;
  }
  return 'pg-media-gallery-cols-3';
}

export function MediaGalleryIsland({
  attrs,
  innerHtml,
}: {
  attrs: IslandProps;
  innerHtml?: string;
}): React.ReactElement {
  const { t } = useI18n();
  const heading = attrs.title?.trim() ?? '';
  const layout = attrs.layout?.trim() === 'masonry' ? 'masonry' : 'grid';
  const columns = columnClass(attrs.columns);
  const captionStyle = pickCaption(attrs.modalCaptionStyle);

  const items = useMemo(() => parseMediaGalleryMarkup(innerHtml ?? ''), [innerHtml]);
  const slides = useMemo(() => mediaGalleryMarkupToSlides(items), [items]);
  const [activeIndex, setActiveIndex] = useState<number | null>(null);

  const close = useCallback(() => setActiveIndex(null), []);

  if (items.length === 0) {
    return (
      <p className="pg-media-gallery-empty text-center text-theme-text-muted py-10">
        {t('public.gallery.empty')}
      </p>
    );
  }

  const gridClass =
    layout === 'masonry'
      ? `pg-media-gallery-grid ${columns} pg-media-gallery-grid--masonry`
      : `pg-media-gallery-grid ${columns}`;

  return (
    <section className="pg-media-gallery pg-island pg-island--media-gallery">
      {heading !== '' ? <h2 className="pg-media-gallery-title">{heading}</h2> : null}
      <div className={gridClass}>
        {items.map((item, index) => (
          <button
            key={`${item.mediaPath}-${index}`}
            type="button"
            className={`pg-media-gallery-item pg-media-gallery-item--interactive group text-left w-full${
              item.mediaType === 'video' ? ' pg-media-gallery-item--video' : ''
            }`}
            onClick={() => setActiveIndex(index)}
            aria-label={`${t('public.gallery.openModal')}: ${item.title || item.alt}`}
          >
            {item.mediaType === 'video' ? (
              <video
                className="pg-media-gallery-video"
                src={item.src}
                muted
                playsInline
                preload="metadata"
                aria-hidden
              />
            ) : (
              <img
                src={resolvePublicMediaThumbnailUrl(item.src, MEDIA_THUMB_WIDTH.gallery)}
                alt={item.alt}
                className="pg-media-gallery-image"
                loading="lazy"
                decoding="async"
              />
            )}
            {item.title !== '' ? (
              <span className="pg-media-gallery-item-caption">{item.title}</span>
            ) : null}
          </button>
        ))}
      </div>

      <PaginiumMediaGallery
        slides={slides}
        activeIndex={activeIndex}
        onClose={close}
        onChangeIndex={setActiveIndex}
        captionStyle={captionStyle}
      />
    </section>
  );
}
