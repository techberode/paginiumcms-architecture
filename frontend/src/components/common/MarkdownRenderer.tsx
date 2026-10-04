import React from 'react';
import { PublicIslandHost } from '../../islands/publicIslandRegistry';
import { sanitizePublicHtml } from '../../utils/sanitizeHtml';
import { hasPublicHtmlIslands, splitPublicHtmlIslands } from '../../utils/publicHtmlIslands';
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
    if (!hasPublicHtmlIslands(safe)) {
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
          ) : (
            <PublicIslandHost
              key={`island-${part.id}-${index}`}
              id={part.id}
              attrs={part.attrs}
              innerHtml={part.innerHtml}
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
