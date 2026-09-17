import React, { useEffect, useState } from 'react';
import { Eye, Loader2 } from 'lucide-react';
import { contentApi } from '../../api/content';
import { useI18n } from '../../context/I18nContext';
import { MarkdownRenderer } from '../common/MarkdownRenderer';
import { buildContentPreviewSrcDoc } from '../../utils/contentPreviewSrcDoc';
import {
  THEME_STUDIO_PREVIEW_REFERRER,
  THEME_STUDIO_PREVIEW_SANDBOX,
} from '../../utils/themeStudioPreview';
import { useEditorWorkspaceActive } from './EditorWorkspaceFrame';

interface AdminBodyPreviewPanelProps {
  body: string;
  bodyFormat: 'markdown' | 'html' | 'tiptap_json';
  className?: string;
  debounceMs?: number;
  /** Empty-sandbox iframe (page outline / developer). Inline HTML stays for snippet managers. */
  sandbox?: boolean;
  /** Stretch the iframe to the remaining column instead of a 72vh box. */
  fillViewport?: boolean;
  onOpenFullPreview?: () => void;
}

export const AdminBodyPreviewPanel: React.FC<AdminBodyPreviewPanelProps> = ({
  body,
  bodyFormat,
  className = '',
  debounceMs = 400,
  sandbox = false,
  fillViewport = false,
  onOpenFullPreview,
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
      className={`${fillViewport ? 'flex h-full min-h-0 flex-col' : ''} min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-950/40 ${className}`.trim()}
      data-testid="admin-body-preview-panel"
    >
      <div className="flex shrink-0 items-center justify-between gap-2 border-b border-slate-200 px-4 py-2 dark:border-slate-700">
        <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
          <Eye className="h-3.5 w-3.5" />
          {t('platform.preview.title')}
        </div>
        {onOpenFullPreview ? (
          <button
            type="button"
            onClick={onOpenFullPreview}
            data-testid="admin-body-preview-open"
            className="inline-flex items-center rounded-lg bg-indigo-600 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-white hover:bg-indigo-500"
          >
            {t('platform.preview.openFull')}
          </button>
        ) : null}
      </div>
      <div
        className={
          fillViewport ? 'flex min-h-0 min-w-0 flex-1 flex-col p-4' : 'min-h-[12rem] min-w-0 p-4'
        }
      >
        {loading ? (
          <div className="flex items-center gap-2 text-sm text-slate-500">
            <Loader2 className="h-4 w-4 animate-spin" />
            {t('platform.preview.loading')}
          </div>
        ) : error ? (
          <p className="text-sm text-red-600 dark:text-red-400">{error}</p>
        ) : !body.trim() ? (
          <p className="text-sm text-slate-500">{t('platform.preview.empty')}</p>
        ) : html && sandbox ? (
          <iframe
            title={t('platform.preview.frame')}
            sandbox={THEME_STUDIO_PREVIEW_SANDBOX}
            srcDoc={buildContentPreviewSrcDoc(html)}
            referrerPolicy={THEME_STUDIO_PREVIEW_REFERRER}
            className={
              fillViewport
                ? 'h-full min-h-[12rem] w-full min-w-0 max-w-full flex-1 rounded-lg bg-white'
                : 'h-[min(72vh,46rem)] w-full min-w-0 max-w-full rounded-lg bg-white'
            }
            data-testid="admin-body-preview-frame"
          />
        ) : html ? (
          <MarkdownRenderer content="" html={html} className="paginium-prose pg-shortcode-surface max-w-none" />
        ) : null}
      </div>
    </div>
  );
};

export function PageLivePreviewSplit({
  body,
  bodyFormat,
  children,
  onOpenFullPreview,
}: {
  body: string;
  bodyFormat: 'markdown' | 'html' | 'tiptap_json';
  children: React.ReactNode;
  onOpenFullPreview?: () => void;
}): React.ReactElement {
  const fillViewport = useEditorWorkspaceActive();

  return (
    <div
      className={
        fillViewport
          ? 'isolate grid h-full min-h-0 min-w-0 flex-1 overflow-hidden gap-4 xl:grid-cols-2 xl:items-stretch'
          : 'isolate grid min-w-0 overflow-hidden gap-4 xl:grid-cols-2 xl:items-start'
      }
      data-testid="page-live-preview-split"
      data-fill-viewport={fillViewport ? 'true' : undefined}
    >
      <div
        className={
          fillViewport
            ? 'relative z-10 min-h-0 min-w-0 overflow-auto'
            : 'relative z-10 min-w-0 overflow-hidden'
        }
      >
        {children}
      </div>
      <AdminBodyPreviewPanel
        body={body}
        bodyFormat={bodyFormat}
        sandbox
        fillViewport={fillViewport}
        onOpenFullPreview={onOpenFullPreview}
        className={
          fillViewport
            ? 'relative z-0 min-h-0 min-w-0 overflow-hidden'
            : 'relative z-0 min-w-0 overflow-hidden xl:sticky xl:top-4'
        }
      />
    </div>
  );
};

export default AdminBodyPreviewPanel;
