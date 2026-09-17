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
  variant?: 'embedded' | 'page' | 'preview' | 'block';
  /** Override items (admin live preview). */
  previewItems?: GalleryItem[];
  /** Pin filter to this It.65 featureTag (outline `[feature-gallery tag="…"]`). */
  featureTag?: string;
  /** Optional heading for in-body blocks. */
  heading?: string;
}

/**
 * Public feature gallery section.
 * It.58f-f outline block `feature-gallery` hydrates this component
 * (same `GET /api/gallery/public` — no duplicate storage).
 */
export const FeatureGallerySection: React.FC<FeatureGallerySectionProps> = ({
  variant = 'embedded',
  previewItems,
  featureTag,
  heading,
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
  const pinnedTag = featureTag?.trim() || null;

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
    if (pinnedTag) {
      setActiveTag(pinnedTag);
      setDeepLinkModalIndex(null);
      setDeepLinkReady(true);
      return;
    }
    const slide = searchParams.get('slide');
    const resolved = resolveGallerySlideDeepLink(items, slide);
    setActiveTag(resolved.activeTag);
    setDeepLinkModalIndex(resolved.modalIndex);
    setDeepLinkReady(true);
  }, [items, loading, pinnedTag, searchParams, variant]);

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
    const pin = pinnedTag ?? activeTag;
    if (!pin) {
      return items;
    }
    return items.filter((item) => item.featureTag === pin);
  }, [activeTag, items, pinnedTag]);

  const handleTagChange = (tag: string | null) => {
    if (pinnedTag) {
      return;
    }
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

  if (variant !== 'preview' && variant !== 'block' && !gallerySettings?.enabled) {
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
  const headingText = heading?.trim() ?? '';

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

  const sectionClass =
    variant === 'page'
      ? 'py-10'
      : variant === 'preview'
        ? 'py-2'
        : variant === 'block'
          ? 'py-8'
          : 'py-12 border-t border-theme-border';

  return (
    <section className={sectionClass}>
      <div className={`mx-auto px-4 ${variant === 'preview' ? 'max-w-full' : 'container max-w-6xl'}`}>
        {variant === 'preview' ? (
          <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {t('gallery.preview.title')}
          </p>
        ) : headingText !== '' ? (
          <div className="mb-8 text-center max-w-2xl mx-auto">
            <h2 className="text-2xl sm:text-3xl font-black text-theme-text">{headingText}</h2>
          </div>
        ) : variant !== 'block' ? (
          <div className="mb-8 text-center max-w-2xl mx-auto">
            <h2 className="text-2xl sm:text-3xl font-black text-theme-text">
              {variant === 'page' ? t('public.gallery.pageTitle') : t('public.gallery.sectionTitle')}
            </h2>
            <p className="mt-2 text-theme-text-muted">{t('public.gallery.sectionSubtitle')}</p>
          </div>
        ) : null}
        {showFeatureTags && !pinnedTag ? (
          <FeatureGalleryTagFilter tags={tags} activeTag={activeTag} onChange={handleTagChange} />
        ) : null}
        {body}
      </div>
    </section>
  );
};

export default FeatureGallerySection;
