import React from 'react';
import type { GalleryItem } from '../../api/gallery';
import { MEDIA_THUMB_WIDTH, resolvePublicMediaThumbnailUrl, resolvePublicMediaUrl } from '../../api/media';
import { inferPublicMediaKind } from '../../utils/mediaKindFromPath';

export interface FeatureGalleryMediaThumbProps {
  item: GalleryItem;
  /** Grid cards use thumbnails; slider uses full asset. */
  variant: 'grid' | 'slider';
  imageClassName: string;
  loading?: 'eager' | 'lazy';
  draggable?: boolean;
}

export const FeatureGalleryMediaThumb: React.FC<FeatureGalleryMediaThumbProps> = ({
  item,
  variant,
  imageClassName,
  loading = 'lazy',
  draggable = false,
}) => {
  const kind = inferPublicMediaKind(item.mediaPath);

  if (kind === 'video') {
    const src = resolvePublicMediaUrl(item.mediaPath);
    return (
      <div className="feature-gallery-thumb feature-gallery-thumb--video relative h-full w-full">
        <video
          className={imageClassName}
          src={src}
          muted
          playsInline
          preload="metadata"
          aria-hidden
        />
        <span className="feature-gallery-thumb__play" aria-hidden />
      </div>
    );
  }

  const src =
    variant === 'grid'
      ? resolvePublicMediaThumbnailUrl(item.mediaPath, MEDIA_THUMB_WIDTH.gallery)
      : resolvePublicMediaUrl(item.mediaPath);

  return (
    <img
      src={src}
      alt={item.title}
      className={imageClassName}
      loading={loading}
      draggable={draggable}
    />
  );
};
