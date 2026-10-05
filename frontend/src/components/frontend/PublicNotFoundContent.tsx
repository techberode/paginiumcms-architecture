import React from 'react';
import { PageRenderer } from './PageRenderer';
import { PublicSystemErrorPanel } from './PublicSystemErrorPanel';
import { usePublicSite } from '../../context/PublicSiteContext';
import { useSettingsContext } from '../../context/SettingsContext';
import { PUBLIC_SPINNER } from '../../theme/publicUiClasses';
import { resolvePublicErrorPage } from '../../utils/publicErrorPages';

export interface PublicNotFoundContentProps {
  /** Slug or path hint shown in the built-in panel when no custom page is configured. */
  missingSlug?: string;
}

/**
 * Unified public 404: optional custom page from Settings → layout.notFoundPageSlug,
 * otherwise the built-in panel. Waits for settings + page list before deciding.
 */
export const PublicNotFoundContent: React.FC<PublicNotFoundContentProps> = ({ missingSlug }) => {
  const { getPageBySlug, loading: siteLoading } = usePublicSite();
  const { settings, loading: settingsLoading } = useSettingsContext();

  if (siteLoading || settingsLoading) {
    return (
      <div className="min-h-[50vh] flex items-center justify-center">
        <div className={PUBLIC_SPINNER} />
      </div>
    );
  }

  const resolved = resolvePublicErrorPage('notFound', settings.layout, getPageBySlug, missingSlug);
  if (resolved.page) {
    return <PageRenderer page={resolved.page} />;
  }

  return <PublicSystemErrorPanel kind="notFound" missingSlug={missingSlug} />;
};
