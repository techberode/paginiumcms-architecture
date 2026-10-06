import React, { useCallback, useEffect, useRef, useState } from 'react';
import type { GalleryItem } from '../../api/gallery';
import { useI18n } from '../../context/I18nContext';
import { FeatureGalleryMediaThumb } from './FeatureGalleryMediaThumb';
import { FeatureGalleryModal } from './FeatureGalleryModal';
import {
  galleryGridColumnClass,
  type GalleryGridColumns,
} from '../../utils/featureGalleryBlockOptions';

export interface FeatureGalleryGridProps {
  items: GalleryItem[];
  showFeatureTags?: boolean;
  modalCaptionStyle?: 'below' | 'overlay' | 'side';
  columns?: GalleryGridColumns;
  /** It.65 Phase 3 — open modal at this index when items load (`?slide=`). */
  initialModalIndex?: number | null;
  /** Sync `?slide=` when modal opens or slide changes (58f-i-k). */
  onActiveItemChange?: (item: GalleryItem | null) => void;
  className?: string;
}

export const FeatureGalleryGrid: React.FC<FeatureGalleryGridProps> = ({
  items,
  showFeatureTags = true,
  modalCaptionStyle = 'below',
  columns = '3',
  initialModalIndex = null,
  onActiveItemChange,
  className = '',
}) => {
  const { t } = useI18n();
  const [activeIndex, setActiveIndex] = useState<number | null>(null);
  const deepLinkApplied = useRef(false);

  const setModalIndex = useCallback(
    (index: number | null) => {
      setActiveIndex(index);
      onActiveItemChange?.(index !== null && items[index] !== undefined ? items[index] : null);
    },
    [items, onActiveItemChange]
  );

  useEffect(() => {
    if (deepLinkApplied.current || initialModalIndex === null || initialModalIndex === undefined) {
      return;
    }
    if (initialModalIndex < 0 || initialModalIndex >= items.length) {
      return;
    }
    deepLinkApplied.current = true;
    setModalIndex(initialModalIndex);
  }, [initialModalIndex, items.length, setModalIndex]);

  if (items.length === 0) {
    return (
      <p className="text-center text-theme-text-muted py-10">{t('public.gallery.empty')}</p>
    );
  }

  return (
    <>
      <div className={`${galleryGridColumnClass(columns)} ${className}`.trim()}>
        {items.map((item, index) => (
          <button
            key={item.id}
            type="button"
            className="group text-left rounded-2xl border border-theme-border bg-theme-surface-elevated overflow-hidden shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-theme-accent"
            onClick={() => setModalIndex(index)}
            aria-label={`${t('public.gallery.openModal')}: ${item.title}`}
          >
            <div className="aspect-video overflow-hidden bg-theme-surface">
              <FeatureGalleryMediaThumb
                item={item}
                variant="grid"
                imageClassName="h-full w-full object-cover object-top transition group-hover:scale-[1.02]"
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
        onClose={() => setModalIndex(null)}
        onChangeIndex={(index) => setModalIndex(index)}
        showFeatureTags={showFeatureTags}
        captionStyle={modalCaptionStyle}
      />
    </>
  );
};

export default FeatureGalleryGrid;
