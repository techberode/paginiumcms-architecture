import React, { useEffect, useState } from 'react';
import { Eye, Loader2 } from 'lucide-react';
import { contentApi } from '../../api/content';
import { useI18n } from '../../context/I18nContext';
import { MarkdownRenderer } from '../common/MarkdownRenderer';

interface AdminBodyPreviewPanelProps {
  body: string;
  bodyFormat: 'markdown' | 'html';
  className?: string;
  debounceMs?: number;
}

export const AdminBodyPreviewPanel: React.FC<AdminBodyPreviewPanelProps> = ({
  body,
  bodyFormat,
  className = '',
  debounceMs = 400,
}) => {
  const { t } = useI18n();
  const [html, setHtml] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!body.trim()) {
      setHtml(null);
      setError(null);
      setLoading(false);
      return;
    }

    setLoading(true);
    setError(null);

    const timer = window.setTimeout(() => {
      void contentApi
        .renderPreview({ body, bodyFormat })
        .then((rendered) => {
          setHtml(rendered);
          setError(null);
        })
        .catch(() => {
          setHtml(null);
          setError(t('platform.preview.renderFailed'));
        })
        .finally(() => {
          setLoading(false);
        });
    }, debounceMs);

    return () => {
      window.clearTimeout(timer);
    };
  }, [body, bodyFormat, debounceMs, t]);

  return (
    <div
      className={`rounded-xl border border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-950/40 ${className}`.trim()}
      data-testid="admin-body-preview-panel"
    >
      <div className="flex items-center gap-2 border-b border-slate-200 px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-slate-700">
        <Eye className="h-3.5 w-3.5" />
        {t('platform.preview.title')}
      </div>
      <div className="min-h-[12rem] p-4">
        {loading ? (
          <div className="flex items-center gap-2 text-sm text-slate-500">
            <Loader2 className="h-4 w-4 animate-spin" />
            {t('platform.preview.loading')}
          </div>
        ) : error ? (
          <p className="text-sm text-red-600 dark:text-red-400">{error}</p>
        ) : !body.trim() ? (
          <p className="text-sm text-slate-500">{t('platform.preview.empty')}</p>
        ) : html ? (
          <MarkdownRenderer content="" html={html} className="paginium-prose pg-shortcode-surface max-w-none" />
        ) : null}
      </div>
    </div>
  );
};

export default AdminBodyPreviewPanel;
