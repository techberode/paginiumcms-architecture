// frontend/src/components/common/MarkdownRenderer.tsx
import React from 'react';

interface MarkdownRendererProps {
  content: string;
  html?: string;
  className?: string;
}

export const MarkdownRenderer: React.FC<MarkdownRendererProps> = ({
  content,
  html,
  className = 'paginium-prose',
}) => {
  if (html) {
    return (
      <div
        className={className}
        dangerouslySetInnerHTML={{ __html: html }}
      />
    );
  }

  return (
    <pre className={`${className} whitespace-pre-wrap font-sans text-sm`}>{content}</pre>
  );
};

export default MarkdownRenderer;
