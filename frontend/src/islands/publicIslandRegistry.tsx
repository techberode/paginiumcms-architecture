import React from 'react';
import { FeatureGallerySection } from '../components/frontend/FeatureGallerySection';
import { StaffCardsSection } from '../components/frontend/StaffDirectory';
import { GalleryCarouselIsland } from './GalleryCarouselIsland';
import { MediaGalleryIsland } from './MediaGalleryIsland';
import { PricingTableIsland } from './PricingTableIsland';
import { StatsRowIsland } from './StatsRowIsland';
import type { IslandProps } from './publicIslandDefinitions';

export type PublicIslandComponent = React.ComponentType<{ attrs: IslandProps; innerHtml?: string }>;

const FeatureGalleryIsland: PublicIslandComponent = ({ attrs }) => (
  <FeatureGallerySection
    variant="block"
    featureTag={attrs.tag !== '' ? attrs.tag : undefined}
    heading={attrs.title !== '' ? attrs.title : undefined}
    blockAttrs={attrs}
  />
);

const StaffCardsIsland: PublicIslandComponent = ({ attrs }) => (
  <StaffCardsSection
    mode={attrs.mode !== '' ? attrs.mode : undefined}
    user={attrs.user !== '' ? attrs.user : undefined}
    type={attrs.type !== '' ? attrs.type : undefined}
    team={attrs.team !== '' ? attrs.team : undefined}
  />
);

/** First-party public hydration map (core bundle only). */
export const PUBLIC_ISLANDS: Record<string, PublicIslandComponent> = {
  'feature-gallery': FeatureGalleryIsland,
  'gallery-carousel': GalleryCarouselIsland,
  'media-gallery': MediaGalleryIsland,
  'staff-cards': StaffCardsIsland,
  'pricing-table': PricingTableIsland,
  'stats-row': StatsRowIsland,
};

export function PublicIslandHost({
  id,
  attrs,
  innerHtml,
}: {
  id: string;
  attrs: IslandProps;
  innerHtml?: string;
}): React.ReactElement | null {
  const Component = PUBLIC_ISLANDS[id];
  if (Component === undefined) {
    return null;
  }

  return <Component attrs={attrs} innerHtml={innerHtml} />;
}
