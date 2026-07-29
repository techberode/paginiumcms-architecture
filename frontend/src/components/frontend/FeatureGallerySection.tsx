import React, { useEffect, useState } from 'react';
import { listPublicGalleryItems, type GalleryItem } from '../../api/gallery';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';
import { FeatureGalleryGrid } from './FeatureGalleryGrid';
import { PUBLIC_SPINNER } from '../../theme/publicUiClasses';

export interface FeatureGallerySectionProps {
  variant?: 'embedded' | 'page';
}

export const FeatureGallerySection: React.FC<FeatureGallerySectionProps> = ({ variant = 'embedded' }) => {
  const { t } = useI18n();
  const { settings } = useSettingsContext();
  const gallerySettings = settings.gallery;
  const [items, setItems] = useState<GalleryItem[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
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
  }, []);

  if (!gallerySettings?.enabled) {
    return null;
  }

  if (loading) {
    return (
      <div className="flex justify-center py-12">
        <div className={PUBLIC_SPINNER} />
      </div>
    );
  }

  const showFeatureTags = gallerySettings.showFeatureTags !== false;

  return (
    <section className={variant === 'page' ? 'py-10' : 'py-12 border-t border-theme-border'}>
      <div className="container mx-auto px-4 max-w-6xl">
        <div className="mb-8 text-center max-w-2xl mx-auto">
          <h2 className="text-2xl sm:text-3xl font-black text-theme-text">
            {variant === 'page' ? t('public.gallery.pageTitle') : t('public.gallery.sectionTitle')}
          </h2>
          <p className="mt-2 text-theme-text-muted">{t('public.gallery.sectionSubtitle')}</p>
        </div>
        <FeatureGalleryGrid items={items} showFeatureTags={showFeatureTags} />
      </div>
    </section>
  );
};

export default FeatureGallerySection;
