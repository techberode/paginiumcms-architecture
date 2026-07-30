import React, { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { listPublicGalleryItems, type GalleryItem } from '../../api/gallery';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';
import { FeatureGalleryGrid } from './FeatureGalleryGrid';
import { FeatureGallerySlider } from './FeatureGallerySlider';
import { FeatureGalleryTagFilter } from './FeatureGalleryTagFilter';
import { resolveGallerySlideDeepLink } from '../../utils/gallerySlideDeepLink';
import { PUBLIC_SPINNER } from '../../theme/publicUiClasses';

export interface FeatureGallerySectionProps {
  variant?: 'embedded' | 'page' | 'preview';
  /** Override items (admin live preview). */
  previewItems?: GalleryItem[];
}

/**
 * Public feature gallery section.
 * It.58a entry point: a future layout block `featureGallery` should render this
 * component (same `GET /api/gallery/public` — no duplicate storage).
 */
export const FeatureGallerySection: React.FC<FeatureGallerySectionProps> = ({
  variant = 'embedded',
  previewItems,
}) => {
  const { t } = useI18n();
  const { settings } = useSettingsContext();
  const gallerySettings = settings.gallery;
  const [searchParams, setSearchParams] = useSearchParams();
  const [items, setItems] = useState<GalleryItem[]>(previewItems ?? []);
  const [loading, setLoading] = useState(previewItems === undefined);
  const [activeTag, setActiveTag] = useState<string | null>(null);
  const [deepLinkModalIndex, setDeepLinkModalIndex] = useState<number | null>(null);
  const [deepLinkReady, setDeepLinkReady] = useState(variant === 'preview');

  useEffect(() => {
    if (previewItems !== undefined) {
      setItems(previewItems);
      setLoading(false);
      return;
    }
    let active = true;
    setLoading(true);
    void listPublicGalleryItems()
      .then((data) => {
        if (active) {
          setItems(data);
        }
      })
      .finally(() => {
        if (active) {
          setLoading(false);
        }
      });
    return () => {
      active = false;
    };
  }, [previewItems]);

  useEffect(() => {
    if (variant === 'preview' || loading) {
      return;
    }
    const slide = searchParams.get('slide');
    const resolved = resolveGallerySlideDeepLink(items, slide);
    setActiveTag(resolved.activeTag);
    setDeepLinkModalIndex(resolved.modalIndex);
    setDeepLinkReady(true);
  }, [items, loading, searchParams, variant]);

  const tags = useMemo(() => {
    const set = new Set<string>();
    for (const item of items) {
      const tag = item.featureTag?.trim();
      if (tag) {
        set.add(tag);
      }
    }
    return Array.from(set).sort((a, b) => a.localeCompare(b));
  }, [items]);

  const filteredItems = useMemo(() => {
    if (!activeTag) {
      return items;
    }
    return items.filter((item) => item.featureTag === activeTag);
  }, [activeTag, items]);

  const handleTagChange = (tag: string | null) => {
    setActiveTag(tag);
    setDeepLinkModalIndex(null);
    if (variant === 'preview') {
      return;
    }
    const next = new URLSearchParams(searchParams);
    if (tag) {
      next.set('slide', tag);
    } else {
      next.delete('slide');
    }
    setSearchParams(next, { replace: true });
  };

  if (variant !== 'preview' && !gallerySettings?.enabled) {
    return null;
  }

  if (loading || !deepLinkReady) {
    return (
      <div className="flex justify-center py-12">
        <div className={PUBLIC_SPINNER} />
      </div>
    );
  }

  const showFeatureTags = gallerySettings?.showFeatureTags !== false;
  const layout = gallerySettings?.layout ?? 'grid';
  const effectPreset = gallerySettings?.effectPreset ?? 'subtle';
  const autoplayEnabled = gallerySettings?.autoplayEnabled !== false;
  const autoplayIntervalMs = gallerySettings?.autoplayIntervalMs ?? 6000;
  const modalCaptionStyle = gallerySettings?.modalCaptionStyle ?? 'below';

  const body =
    layout === 'slider' || layout === 'hero-strip' ? (
      <FeatureGallerySlider
        items={filteredItems}
        showFeatureTags={showFeatureTags}
        layout={layout}
        effectPreset={effectPreset}
        autoplayEnabled={variant === 'preview' ? false : autoplayEnabled}
        autoplayIntervalMs={autoplayIntervalMs}
        modalCaptionStyle={modalCaptionStyle}
        initialModalIndex={deepLinkModalIndex}
      />
    ) : (
      <FeatureGalleryGrid
        items={filteredItems}
        showFeatureTags={showFeatureTags}
        modalCaptionStyle={modalCaptionStyle}
        initialModalIndex={deepLinkModalIndex}
      />
    );

  return (
    <section
      className={
        variant === 'page'
          ? 'py-10'
          : variant === 'preview'
            ? 'py-2'
            : 'py-12 border-t border-theme-border'
      }
    >
      <div className={`mx-auto px-4 ${variant === 'preview' ? 'max-w-full' : 'container max-w-6xl'}`}>
        {variant !== 'preview' ? (
          <div className="mb-8 text-center max-w-2xl mx-auto">
            <h2 className="text-2xl sm:text-3xl font-black text-theme-text">
              {variant === 'page' ? t('public.gallery.pageTitle') : t('public.gallery.sectionTitle')}
            </h2>
            <p className="mt-2 text-theme-text-muted">{t('public.gallery.sectionSubtitle')}</p>
          </div>
        ) : (
          <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {t('gallery.preview.title')}
          </p>
        )}
        {showFeatureTags ? (
          <FeatureGalleryTagFilter tags={tags} activeTag={activeTag} onChange={handleTagChange} />
        ) : null}
        {body}
      </div>
    </section>
  );
};

export default FeatureGallerySection;
