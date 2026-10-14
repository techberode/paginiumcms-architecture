export type PublicBreadcrumbsPlacement = 'above_content' | 'below_hero';

export function resolvePublicBreadcrumbsPlacement(
  layout: { breadcrumbsPosition?: string } | undefined
): PublicBreadcrumbsPlacement {
  return layout?.breadcrumbsPosition === 'below_hero' ? 'below_hero' : 'above_content';
}
