import React, { useState } from 'react';
import type { GalleryItem } from '../../api/gallery';
import { resolvePublicMediaUrl } from '../../api/media';
import { useI18n } from '../../context/I18nContext';
import { FeatureGalleryModal } from './FeatureGalleryModal';

export interface FeatureGalleryGridProps {
  items: GalleryItem[];
  showFeatureTags?: boolean;
  modalCaptionStyle?: 'below' | 'overlay' | 'side';
  className?: string;
}

export const FeatureGalleryGrid: React.FC<FeatureGalleryGridProps> = ({
  items,
  showFeatureTags = true,
  modalCaptionStyle = 'below',
  className = '',
}) => {
  const { t } = useI18n();
  const [activeIndex, setActiveIndex] = useState<number | null>(null);

  if (items.length === 0) {
    return (
      <p className="text-center text-theme-text-muted py-10">{t('public.gallery.empty')}</p>
    );
  }

  return (
    <>
      <div className={`grid gap-4 sm:grid-cols-2 lg:grid-cols-3 ${className}`}>
        {items.map((item, index) => (
          <button
            key={item.id}
            type="button"
            className="group text-left rounded-2xl border border-theme-border bg-theme-surface-elevated overflow-hidden shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-theme-accent"
            onClick={() => setActiveIndex(index)}
            aria-label={`${t('public.gallery.openModal')}: ${item.title}`}
          >
            <div className="aspect-video overflow-hidden bg-theme-surface">
              <img
                src={resolvePublicMediaUrl(item.mediaPath)}
                alt={item.title}
                className="h-full w-full object-cover object-top transition group-hover:scale-[1.02]"
                loading="lazy"
              />
            </div>
            <div className="p-4 space-y-2">
              <div className="flex items-start justify-between gap-2">
                <h3 className="font-bold text-theme-text">{item.title}</h3>
                {showFeatureTags && item.featureTag ? (
                  <span className="shrink-0 text-[10px] uppercase tracking-wide font-bold px-2 py-0.5 rounded-full bg-theme-primary/15 text-theme-accent">
                    {item.featureTag}
                  </span>
                ) : null}
              </div>
              {item.description ? (
                <p className="text-sm text-theme-text-muted line-clamp-2">{item.description}</p>
              ) : null}
            </div>
          </button>
        ))}
      </div>

      <FeatureGalleryModal
        items={items}
        activeIndex={activeIndex}
        onClose={() => setActiveIndex(null)}
        onChangeIndex={setActiveIndex}
        showFeatureTags={showFeatureTags}
        captionStyle={modalCaptionStyle}
      />
    </>
  );
};

export default FeatureGalleryGrid;
