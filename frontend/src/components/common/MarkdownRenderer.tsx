// frontend/src/components/common/MarkdownRenderer.tsx
import React from 'react';
import { sanitizePublicHtml } from '../../utils/sanitizeHtml';

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
    return (
      <div
        className={className}
        dangerouslySetInnerHTML={{ __html: sanitizePublicHtml(html) }}
      />
    );
  }

  return (
    <pre className={`${className} whitespace-pre-wrap font-sans text-sm`}>{content}</pre>
  );
};

export default MarkdownRenderer;
