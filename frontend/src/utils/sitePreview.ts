import type { ContentType } from '../api/drafts';
import type { SitePreviewDraft } from '../components/backend/SitePreviewModal';

interface ApiContentRecord {
  title?: string;
  slug?: string;
  content?: string;
  html?: string;
  contentFormat?: string;
  template?: string;
  author?: string;
  tags?: string[];
  createdAt?: string;
  updatedAt?: string;
  seoDescription?: string;
  frontMatter?: Record<string, unknown>;
}

/** Builds modal preview payload from a single content API record. */
export function buildSitePreviewDraft(
  type: ContentType,
  data: ApiContentRecord
): SitePreviewDraft {
  const fm = data.frontMatter ?? {};
  const rawContent = data.content || '';
  const format = String(data.contentFormat ?? fm.contentFormat ?? 'markdown');
  const isHtml = format === 'html';
  const isTiptap = format === 'tiptap_json';

  return {
    type: type === 'article' ? 'article' : 'page',
    title: String(data.title ?? ''),
    slug: String(data.slug ?? ''),
    template: String(data.template ?? fm.template ?? 'default'),
    content: isHtml || isTiptap ? '' : rawContent,
    html: isHtml ? rawContent : data.html,
    contentFormat: (isHtml ? 'html' : isTiptap ? 'tiptap_json' : 'markdown') as SitePreviewDraft['contentFormat'],
    author: String(data.author ?? fm.author ?? 'Redakcia'),
    tags: Array.isArray(data.tags)
      ? data.tags.map(String)
      : Array.isArray(fm.tags)
        ? fm.tags.map(String)
        : [],
    seoDescription: String(
      data.seoDescription ?? fm.seoDescription ?? fm.description ?? ''
    ),
    createdAt: data.createdAt ? String(data.createdAt) : undefined,
    updatedAt: data.updatedAt ? String(data.updatedAt) : undefined,
  };
}

export function previewFrameMaxWidth(scale: 'fullscreen' | '100' | '75' | '50'): number | null {
  if (scale === 'fullscreen') {
    return null;
  }
  const numeric = Number.parseInt(scale, 10);
  return Math.round(1200 * (numeric / 100));
}
