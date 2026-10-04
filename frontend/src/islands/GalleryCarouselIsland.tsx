import React, { useEffect, useMemo, useState } from 'react';
import { listPublicGalleryItems, type GalleryItem } from '../api/gallery';
import { useSettingsContext } from '../context/SettingsContext';
import {
  FeatureGallerySlider,
  type GalleryEffectPreset,
  type GalleryLayout,
} from '../components/frontend/FeatureGallerySlider';
import type { GalleryModalCaptionStyle } from '../utils/featureGalleryBlockOptions';
import { PUBLIC_SPINNER } from '../theme/publicUiClasses';
import type { IslandProps } from './publicIslandDefinitions';

const EFFECTS: readonly GalleryEffectPreset[] = ['subtle', 'cinematic', 'minimal'];
const CAPTIONS: readonly GalleryModalCaptionStyle[] = ['below', 'overlay', 'side'];

function pickEffect(raw: string | undefined): GalleryEffectPreset {
  const value = raw?.trim() ?? '';
  return (EFFECTS as readonly string[]).includes(value) ? (value as GalleryEffectPreset) : 'subtle';
}

function pickCaption(
  raw: string | undefined,
  fallback: GalleryModalCaptionStyle | undefined
): GalleryModalCaptionStyle {
  const value = raw?.trim() ?? '';
  if ((CAPTIONS as readonly string[]).includes(value)) {
    return value as GalleryModalCaptionStyle;
  }
  return fallback ?? 'below';
}

export function GalleryCarouselIsland({ attrs }: { attrs: IslandProps }): React.ReactElement {
  const { settings } = useSettingsContext();
  const gallerySettings = settings.gallery;
  const [items, setItems] = useState<GalleryItem[]>([]);
  const [loading, setLoading] = useState(true);

  const pinnedTag = attrs.tag?.trim() || null;
  const heading = attrs.title?.trim() ?? '';
  const layout: GalleryLayout = attrs.layout?.trim() === 'hero-strip' ? 'hero-strip' : 'slider';
  const effectPreset = pickEffect(attrs.effect);
  const autoplayEnabled = attrs.autoplay !== 'false';
  const autoplayIntervalMs = gallerySettings?.autoplayIntervalMs ?? 6000;
  const modalCaptionStyle = pickCaption(
    attrs.modalCaptionStyle,
    gallerySettings?.modalCaptionStyle as GalleryModalCaptionStyle | undefined
  );
  const showFeatureTags = gallerySettings?.showFeatureTags !== false;

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

  const filteredItems = useMemo(() => {
    if (!pinnedTag) {
      return items;
    }
    return items.filter((item) => item.featureTag === pinnedTag);
  }, [items, pinnedTag]);

  if (loading) {
    return (
      <div className="flex justify-center py-12">
        <div className={PUBLIC_SPINNER} />
      </div>
    );
  }

  return (
    <section className="pg-gallery-carousel py-8">
      <div className="container mx-auto max-w-6xl px-4">
        {heading !== '' ? (
          <div className="mb-8 text-center max-w-2xl mx-auto">
            <h2 className="text-2xl sm:text-3xl font-black text-theme-text">{heading}</h2>
          </div>
        ) : null}
        <FeatureGallerySlider
          items={filteredItems}
          showFeatureTags={showFeatureTags && !pinnedTag}
          layout={layout}
          effectPreset={effectPreset}
          autoplayEnabled={autoplayEnabled}
          autoplayIntervalMs={autoplayIntervalMs}
          modalCaptionStyle={modalCaptionStyle}
        />
      </div>
    </section>
  );
}
