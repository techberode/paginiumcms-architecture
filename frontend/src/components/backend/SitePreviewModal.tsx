import React, { useEffect, useMemo, useState } from 'react';
import { Calendar, Maximize2, Minimize2, Tag, User, X } from 'lucide-react';
import type { Article, Page } from '../../api/types';
import { contentApi } from '../../api/content';
import { Navbar } from '../frontend/Navbar';
import { Footer } from '../frontend/Footer';
import { PageRenderer } from '../frontend/PageRenderer';
import { MarkdownRenderer } from '../common/MarkdownRenderer';
import { resolveContentPreviewImage } from '../../utils/contentPreviewImage';
import { formatContentDateLabels } from '../../utils/contentDates';
import { markdownToHtml } from '../../utils/contentEditor';
import { previewFrameMaxWidth } from '../../utils/sitePreview';
import { useI18n } from '../../context/I18nContext';

export type SitePreviewScale = 'fullscreen' | '100' | '75' | '50';

export interface SitePreviewDraft {
  type: 'page' | 'article';
  title: string;
  slug: string;
  template?: string;
  content: string;
  html?: string;
  contentFormat?: 'markdown' | 'html' | 'tiptap_json';
  author?: string;
  tags?: string[];
  seoDescription?: string;
  createdAt?: string;
  updatedAt?: string;
}

export interface SitePreviewModalProps {
  open: boolean;
  onClose: () => void;
  draft: SitePreviewDraft | null;
  /** Active editor locale tab — preview does not imply published status. */
  previewLocale?: string;
  previewLocaleStatus?: string;
}

const SCALE_VALUES: SitePreviewScale[] = ['fullscreen', '100', '75', '50'];

function buildPreviewPage(
  draft: SitePreviewDraft,
  html: string,
  untitled: string,
  defaultAuthor: string
): Page {
  const now = new Date().toISOString();
  return {
    id: `preview-${draft.slug || 'draft'}`,
    title: draft.title || untitled,
    slug: draft.slug || 'preview',
    content: draft.content,
    html,
    status: 'published',
    author: draft.author || defaultAuthor,
    createdAt: draft.createdAt || now,
    updatedAt: draft.updatedAt || now,
    template: draft.template || 'default',
    frontMatter: {
      template: draft.template || 'default',
      description: draft.seoDescription || '',
    },
  };
}

function buildPreviewArticle(
  draft: SitePreviewDraft,
  html: string,
  untitled: string,
  defaultAuthor: string
): Article {
  const page = buildPreviewPage(draft, html, untitled, defaultAuthor);
  return {
    ...page,
    featuredImage: '',
    tags: draft.tags ?? [],
    excerpt: draft.seoDescription || '',
    readingTime: 1,
  };
}

const ArticlePreviewBody: React.FC<{ article: Article; defaultAuthor: string }> = ({
  article,
  defaultAuthor,
}) => {
  const { locale } = useI18n();
  const image = resolveContentPreviewImage(article);
  const author = article.author || defaultAuthor;
  const dates = formatContentDateLabels(
    {
      createdAt: article.createdAt,
      updatedAt: article.updatedAt,
      frontMatterDate: article.frontMatter?.date as string | number | undefined,
    },
    locale
  );

  return (
    <div className="min-h-[50vh] bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 pb-16">
      <header className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div className="flex flex-wrap items-center gap-2 mb-4">
          {article.tags?.map((tag) => (
            <span
              key={tag}
              className="text-xs bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-extrabold px-3 py-1 rounded-lg inline-flex items-center gap-1"
            >
              <Tag className="w-3 h-3" /> {tag}
            </span>
          ))}
        </div>
        <h1 className="text-3xl sm:text-4xl font-black tracking-tight">{article.title}</h1>
        <div className="mt-5 flex flex-wrap items-center gap-4 text-xs text-slate-500">
          <span className="inline-flex items-center gap-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3 py-1.5 font-semibold">
            <Calendar className="w-3.5 h-3.5 text-indigo-500" />
            <span title={dates.primaryTitle}>{dates.primary}</span>
            {dates.secondary && (
              <>
                <span className="text-slate-300">•</span>
                <span title={dates.secondaryTitle}>{dates.secondary}</span>
              </>
            )}
          </span>
          <span className="inline-flex items-center gap-1.5 font-semibold">
            <User className="w-3.5 h-3.5" />
            {author}
          </span>
        </div>
        {image && (
          <div className="mt-8 rounded-3xl overflow-hidden shadow-xl max-h-[420px]">
            <img src={image} alt={article.title} className="w-full h-full object-cover" />
          </div>
        )}
      </header>
      <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="bg-white dark:bg-slate-900 rounded-3xl p-8 sm:p-10 border border-slate-200/80 dark:border-slate-800 shadow-sm">
          <MarkdownRenderer content={article.content} html={article.html} />
        </div>
      </main>
    </div>
  );
};

export const SitePreviewModal: React.FC<SitePreviewModalProps> = ({
  open,
  onClose,
  draft,
  previewLocale,
  previewLocaleStatus,
}) => {
  const { t } = useI18n();
  const [scale, setScale] = useState<SitePreviewScale>('100');
  const [previewHtml, setPreviewHtml] = useState('');
  const [previewLoading, setPreviewLoading] = useState(false);
  const untitled = t('editor.sitePreview.untitled');
  const defaultAuthor = t('editor.sitePreview.defaultAuthor');

  const draftKey = useMemo(() => {
    if (!draft) {
      return '';
    }
    return `${draft.type}:${draft.slug}:${draft.contentFormat ?? 'markdown'}:${draft.content}:${draft.html ?? ''}`;
  }, [draft]);

  useEffect(() => {
    if (!open || !draft) {
      setPreviewHtml('');
      setPreviewLoading(false);
      return;
    }

    const bodyFormat = draft.contentFormat ?? (draft.html?.trim() && !draft.content.trim() ? 'html' : 'markdown');
    const body =
      bodyFormat === 'html'
        ? draft.html ?? ''
        : bodyFormat === 'tiptap_json'
          ? draft.content
          : draft.content;
    const fallback = draft.html?.trim() || markdownToHtml(draft.content);

    if (!body.trim() && !fallback.trim()) {
      setPreviewHtml('');
      return;
    }

    let cancelled = false;
    setPreviewLoading(true);

    void contentApi
      .renderPreview({
        body,
        bodyFormat,
        cachedHtml: bodyFormat === 'tiptap_json' ? draft.html : undefined,
      })
      .then((html) => {
        if (!cancelled) {
          setPreviewHtml(html || fallback);
        }
      })
      .catch(() => {
        if (!cancelled) {
          setPreviewHtml(fallback);
        }
      })
      .finally(() => {
        if (!cancelled) {
          setPreviewLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [open, draft, draftKey]);

  if (!open || !draft) {
    return null;
  }

  const isFullscreen = scale === 'fullscreen';
  const frameMaxWidth = previewFrameMaxWidth(scale);
  const displayTitle = draft.title || untitled;
  const contentTypeLabel =
    draft.type === 'article' ? t('editor.sitePreview.typeArticle') : t('editor.sitePreview.typePage');

  return (
    <div className="fixed inset-0 z-[100] flex flex-col bg-slate-950/80 backdrop-blur-sm">
      <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 bg-slate-900 px-4 py-3 text-white">
        <div>
          <p className="text-xs font-bold uppercase tracking-wider text-indigo-300">
            {t('editor.sitePreview.title')}
          </p>
          <p className="text-sm font-semibold truncate max-w-[60vw]">
            {displayTitle} · {contentTypeLabel}
          </p>
          {previewLocale && (
            <p className="text-xs text-slate-400">
              {t('editor.sitePreview.localeHint', {
                locale: previewLocale.toUpperCase(),
                status: previewLocaleStatus ?? 'draft',
              })}
            </p>
          )}
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {SCALE_VALUES.map((value) => (
            <button
              key={value}
              type="button"
              onClick={() => setScale(value)}
              className={`rounded-lg px-3 py-1.5 text-xs font-bold transition ${
                scale === value
                  ? 'bg-indigo-600 text-white'
                  : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
              }`}
            >
              {t(`editor.sitePreview.scale.${value}`)}
            </button>
          ))}
          <button
            type="button"
            onClick={() => setScale(isFullscreen ? '100' : 'fullscreen')}
            className="rounded-lg bg-slate-800 p-2 text-slate-200 hover:bg-slate-700"
            title={
              isFullscreen ? t('editor.sitePreview.constrainWidth') : t('editor.sitePreview.fullscreen')
            }
          >
            {isFullscreen ? <Minimize2 size={16} /> : <Maximize2 size={16} />}
          </button>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg bg-slate-800 p-2 text-slate-200 hover:bg-red-600"
            title={t('editor.sitePreview.close')}
          >
            <X size={16} />
          </button>
        </div>
      </div>

      <div className={`flex-1 overflow-auto ${isFullscreen ? '' : 'p-4 sm:p-8'}`}>
        <div
          className={`mx-auto bg-slate-50 dark:bg-slate-950 shadow-2xl transition-all ${
            isFullscreen ? 'min-h-full w-full' : 'rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800'
          } ${previewLoading ? 'opacity-70' : ''}`}
          aria-busy={previewLoading}
          style={{
            width: '100%',
            maxWidth: frameMaxWidth ?? undefined,
            minHeight: isFullscreen ? '100%' : undefined,
          }}
        >
          <div className="pointer-events-none select-none">
            <Navbar onOpenSearch={() => undefined} previewMode />
          </div>
          <div className="pointer-events-none select-none">
            {draft.type === 'page' ? (
              <PageRenderer page={buildPreviewPage(draft, previewHtml, untitled, defaultAuthor)} />
            ) : (
              <ArticlePreviewBody
                article={buildPreviewArticle(draft, previewHtml, untitled, defaultAuthor)}
                defaultAuthor={defaultAuthor}
              />
            )}
          </div>
          <div className="pointer-events-none select-none">
            <Footer />
          </div>
        </div>
      </div>
    </div>
  );
};

export default SitePreviewModal;
