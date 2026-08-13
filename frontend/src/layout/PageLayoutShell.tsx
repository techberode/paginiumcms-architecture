import React from 'react';
import {
  type PageLayoutTemplateId,
  normalizePageLayoutTemplateId,
} from './pageLayoutTemplates';

interface PageLayoutShellProps {
  layoutTemplate?: string | null;
  hero?: React.ReactNode;
  children: React.ReactNode;
}

export const PageLayoutShell: React.FC<PageLayoutShellProps> = ({
  layoutTemplate,
  hero,
  children,
}) => {
  const templateId: PageLayoutTemplateId = normalizePageLayoutTemplateId(layoutTemplate ?? undefined);

  switch (templateId) {
    case 'single':
      return (
        <div className="pg-layout pg-layout-single" data-testid="page-layout-shell" data-layout-template={templateId}>
          {children}
        </div>
      );

    case 'two-column':
      return (
        <div className="pg-layout pg-layout-two-column" data-testid="page-layout-shell" data-layout-template={templateId}>
          <div className="pg-main">{children}</div>
          <aside className="pg-aside" aria-label="Sidebar">
            <div className="pg-card pg-card-muted">
              <p className="text-sm text-theme-text-muted">Sidebar slot</p>
            </div>
          </aside>
        </div>
      );

    case 'landing':
      return (
        <div className="pg-layout pg-layout-landing" data-testid="page-layout-shell" data-layout-template={templateId}>
          {hero}
          <div className="pg-landing-grid">{children}</div>
        </div>
      );

    case 'blog-article':
      return (
        <article
          className="pg-layout pg-layout-blog-article prose prose-theme max-w-none"
          data-testid="page-layout-shell"
          data-layout-template={templateId}
        >
          {children}
        </article>
      );

    case 'hero-content':
    default:
      return (
        <div className="pg-layout pg-layout-hero-content" data-testid="page-layout-shell" data-layout-template={templateId}>
          {hero}
          <div className="pg-main">{children}</div>
        </div>
      );
  }
};
