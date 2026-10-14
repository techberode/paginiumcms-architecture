import React, { useMemo } from 'react';
import { PageRenderer } from './PageRenderer';
import { PublicSystemErrorPanel } from './PublicSystemErrorPanel';
import { BrokenContentReportForm } from './BrokenContentReportForm';
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
  const referer = useMemo(() => (typeof document !== 'undefined' ? document.referrer : ''), []);
  const reportEnabled = settings.layout?.brokenContentReportOn404 !== false;

  if (siteLoading || settingsLoading) {
    return (
      <div className="min-h-[50vh] flex items-center justify-center">
        <div className={PUBLIC_SPINNER} />
      </div>
    );
  }

  const resolved = resolvePublicErrorPage('notFound', settings.layout, getPageBySlug, missingSlug);
  const reportBlock =
    reportEnabled && missingSlug ? (
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <BrokenContentReportForm reportedPath={`/${missingSlug.replace(/^\/+/, '')}`} referer={referer} />
      </div>
    ) : null;

  if (resolved.page) {
    return (
      <>
        <PageRenderer page={resolved.page} />
        {reportBlock}
      </>
    );
  }

  return (
    <>
      <PublicSystemErrorPanel kind="notFound" missingSlug={missingSlug} />
      {reportBlock}
    </>
  );
};
