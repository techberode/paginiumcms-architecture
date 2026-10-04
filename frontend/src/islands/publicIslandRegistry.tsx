import React from 'react';
import { FeatureGallerySection } from '../components/frontend/FeatureGallerySection';
import { StaffCardsSection } from '../components/frontend/StaffDirectory';
import { GalleryCarouselIsland } from './GalleryCarouselIsland';
import type { IslandProps } from './publicIslandDefinitions';

export type PublicIslandComponent = React.ComponentType<{ attrs: IslandProps }>;

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
  'staff-cards': StaffCardsIsland,
};

export function PublicIslandHost({
  id,
  attrs,
}: {
  id: string;
  attrs: IslandProps;
}): React.ReactElement | null {
  const Component = PUBLIC_ISLANDS[id];
  if (Component === undefined) {
    return null;
  }

  return <Component attrs={attrs} />;
}
