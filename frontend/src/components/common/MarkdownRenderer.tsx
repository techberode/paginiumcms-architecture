import React from 'react';
import { FeatureGallerySection } from '../frontend/FeatureGallerySection';
import { StaffCardsSection } from '../frontend/StaffDirectory';
import { sanitizePublicHtml } from '../../utils/sanitizeHtml';
import { splitPublicHtmlIslands } from '../../utils/publicHtmlIslands';
import { ProseImageLightboxHost } from './ProseImageLightboxHost';

interface MarkdownRendererProps {
  content: string;
  html?: string;
  className?: string;
  /** Click-to-zoom for inline `/storage/` images (articles, pages). */
  enableImageLightbox?: boolean;
}

function ProseRoot({
  className,
  enableImageLightbox,
  children,
}: {
  className: string;
  enableImageLightbox: boolean;
  children: React.ReactNode;
}): React.ReactElement {
  if (enableImageLightbox) {
    return <ProseImageLightboxHost className={className}>{children}</ProseImageLightboxHost>;
  }

  return <div className={className}>{children}</div>;
}

export const MarkdownRenderer: React.FC<MarkdownRendererProps> = ({
  content,
  html,
  className = 'paginium-prose pg-shortcode-surface',
  enableImageLightbox = false,
}) => {
  if (html) {
    const safe = sanitizePublicHtml(html);
    const parts = splitPublicHtmlIslands(safe);
    if (!parts.some((part) => part.kind === 'gallery' || part.kind === 'staff')) {
      return (
        <ProseRoot className={className} enableImageLightbox={enableImageLightbox}>
          <div dangerouslySetInnerHTML={{ __html: safe }} />
        </ProseRoot>
      );
    }

    return (
      <ProseRoot className={className} enableImageLightbox={enableImageLightbox}>
        {parts.map((part, index) =>
          part.kind === 'html' ? (
            part.html.trim() === '' ? null : (
              <div
                key={`html-${index}`}
                className="contents"
                dangerouslySetInnerHTML={{ __html: part.html }}
              />
            )
          ) : part.kind === 'staff' ? (
            <StaffCardsSection
              key={`staff-${index}`}
              mode={part.mode}
              user={part.user}
              type={part.type}
              team={part.team}
            />
          ) : (
            <FeatureGallerySection
              key={`gallery-${index}`}
              variant="block"
              featureTag={part.tag || undefined}
              heading={part.title || undefined}
            />
          )
        )}
      </ProseRoot>
    );
  }

  return (
    <pre className={`${className} whitespace-pre-wrap font-sans text-sm`}>{content}</pre>
  );
};

export default MarkdownRenderer;
