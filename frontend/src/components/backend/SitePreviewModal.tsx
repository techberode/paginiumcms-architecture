import React, { useMemo, useState } from 'react';
import { Calendar, Maximize2, Minimize2, Tag, User, X } from 'lucide-react';
import type { Article, Page } from '../../api/types';
import { Navbar } from '../frontend/Navbar';
import { Footer } from '../frontend/Footer';
import { PageRenderer } from '../frontend/PageRenderer';
import { MarkdownRenderer } from '../common/MarkdownRenderer';
import { resolveContentPreviewImage } from '../../utils/contentPreviewImage';
import { formatContentDateLabels } from '../../utils/contentDates';
import { markdownToHtml } from '../../utils/contentEditor';
import { previewFrameMaxWidth } from '../../utils/sitePreview';

export type SitePreviewScale = 'fullscreen' | '100' | '75' | '50';

export interface SitePreviewDraft {
  type: 'page' | 'article';
  title: string;
  slug: string;
  template?: string;
  content: string;
  html?: string;
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
}

const SCALE_OPTIONS: { value: SitePreviewScale; label: string }[] = [
  { value: 'fullscreen', label: 'Celá obrazovka' },
  { value: '100', label: '100 %' },
  { value: '75', label: '75 %' },
  { value: '50', label: '50 %' },
];

function buildPreviewPage(draft: SitePreviewDraft, html: string): Page {
  const now = new Date().toISOString();
  return {
    id: `preview-${draft.slug || 'draft'}`,
    title: draft.title || 'Bez názvu',
    slug: draft.slug || 'preview',
    content: draft.content,
    html,
    status: 'published',
    author: draft.author || 'Redakcia',
    createdAt: draft.createdAt || now,
    updatedAt: draft.updatedAt || now,
    template: draft.template || 'default',
    frontMatter: {
      template: draft.template || 'default',
      description: draft.seoDescription || '',
    },
  };
}

function buildPreviewArticle(draft: SitePreviewDraft, html: string): Article {
  const page = buildPreviewPage(draft, html);
  return {
    ...page,
    featuredImage: '',
    tags: draft.tags ?? [],
    excerpt: draft.seoDescription || '',
    readingTime: 1,
  };
}

const ArticlePreviewBody: React.FC<{ article: Article }> = ({ article }) => {
  const image = resolveContentPreviewImage(article);
  const author = article.author || 'Redakcia';
  const dates = formatContentDateLabels({
    createdAt: article.createdAt,
    updatedAt: article.updatedAt,
    frontMatterDate: article.frontMatter?.date,
  });

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

export const SitePreviewModal: React.FC<SitePreviewModalProps> = ({ open, onClose, draft }) => {
  const [scale, setScale] = useState<SitePreviewScale>('100');

  const previewHtml = useMemo(() => {
    if (!draft) {
      return '';
    }
    if (draft.html?.trim()) {
      return draft.html;
    }
    return markdownToHtml(draft.content);
  }, [draft]);

  if (!open || !draft) {
    return null;
  }

  const isFullscreen = scale === 'fullscreen';
  const frameMaxWidth = previewFrameMaxWidth(scale);

  return (
    <div className="fixed inset-0 z-[100] flex flex-col bg-slate-950/80 backdrop-blur-sm">
      <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 bg-slate-900 px-4 py-3 text-white">
        <div>
          <p className="text-xs font-bold uppercase tracking-wider text-indigo-300">Náhľad stránky</p>
          <p className="text-sm font-semibold truncate max-w-[60vw]">
            {draft.title || 'Bez názvu'} · {draft.type === 'article' ? 'článok' : 'stránka'}
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {SCALE_OPTIONS.map((option) => (
            <button
              key={option.value}
              type="button"
              onClick={() => setScale(option.value)}
              className={`rounded-lg px-3 py-1.5 text-xs font-bold transition ${
                scale === option.value
                  ? 'bg-indigo-600 text-white'
                  : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
              }`}
            >
              {option.label}
            </button>
          ))}
          <button
            type="button"
            onClick={() => setScale(isFullscreen ? '100' : 'fullscreen')}
            className="rounded-lg bg-slate-800 p-2 text-slate-200 hover:bg-slate-700"
            title={isFullscreen ? 'Obmedziť šírku' : 'Celá obrazovka'}
          >
            {isFullscreen ? <Minimize2 size={16} /> : <Maximize2 size={16} />}
          </button>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg bg-slate-800 p-2 text-slate-200 hover:bg-red-600"
            title="Zavrieť náhľad"
          >
            <X size={16} />
          </button>
        </div>
      </div>

      <div className={`flex-1 overflow-auto ${isFullscreen ? '' : 'p-4 sm:p-8'}`}>
        <div
          className={`mx-auto bg-slate-50 dark:bg-slate-950 shadow-2xl transition-all ${
            isFullscreen ? 'min-h-full w-full' : 'rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800'
          }`}
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
              <PageRenderer page={buildPreviewPage(draft, previewHtml)} />
            ) : (
              <ArticlePreviewBody article={buildPreviewArticle(draft, previewHtml)} />
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
