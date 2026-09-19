import React from 'react';
import { FeatureGallerySection } from '../frontend/FeatureGallerySection';
import { StaffCardsSection } from '../frontend/StaffDirectory';
import { sanitizePublicHtml } from '../../utils/sanitizeHtml';
import { splitPublicHtmlIslands } from '../../utils/publicHtmlIslands';

interface MarkdownRendererProps {
  content: string;
  html?: string;
  className?: string;
}

export const MarkdownRenderer: React.FC<MarkdownRendererProps> = ({
  content,
  html,
  className = 'paginium-prose pg-shortcode-surface',
}) => {
  if (html) {
    const safe = sanitizePublicHtml(html);
    const parts = splitPublicHtmlIslands(safe);
    if (!parts.some((part) => part.kind === 'gallery' || part.kind === 'staff')) {
      return (
        <div
          className={className}
          dangerouslySetInnerHTML={{ __html: safe }}
        />
      );
    }

    return (
      <div className={className}>
        {parts.map((part, index) =>
          part.kind === 'html' ? (
            part.html.trim() === '' ? null : (
              <div
                key={`html-${index}`}
                className="contents"
                dangerouslySetInnerHTML={{ __html: part.html }}
              />
            )
          ) : (
            part.kind === 'staff' ? (
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
          )
        )}
      </div>
    );
  }

  return (
    <pre className={`${className} whitespace-pre-wrap font-sans text-sm`}>{content}</pre>
  );
};

export default MarkdownRenderer;
