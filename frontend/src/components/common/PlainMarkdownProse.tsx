import React, { useMemo } from 'react';
import { plainMarkdownToHtml } from '../../utils/contentEditor';
import { sanitizePublicHtml } from '../../utils/sanitizeHtml';

interface PlainMarkdownProseProps {
  markdown: string;
  className?: string;
}

export const PlainMarkdownProse: React.FC<PlainMarkdownProseProps> = ({
  markdown,
  className = 'pg-legal-prose text-sm leading-relaxed text-theme-text-muted paginium-prose max-w-none',
}) => {
  const html = useMemo(() => {
    const trimmed = markdown.trim();
    if (trimmed === '') {
      return '';
    }
    return sanitizePublicHtml(plainMarkdownToHtml(trimmed));
  }, [markdown]);

  if (html === '') {
    return null;
  }

  return <div className={className} dangerouslySetInnerHTML={{ __html: html }} />;
};

export default PlainMarkdownProse;
