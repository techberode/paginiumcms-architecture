import React from 'react';
import { Link } from 'react-router-dom';
import {
  ExternalLink,
  Eye,
  Home,
  Link2,
  Menu,
  Save,
  Sparkles,
  X,
} from 'lucide-react';
import type { ContentType } from '../../api/drafts';
import type { NavigationItem } from '../../api/navigation';
import type { EditorMode } from '../../utils/contentEditor';
import { countContentStats } from '../../utils/contentEditorMeta';
import { SeoMetadataPanel, type SeoFormValues } from './SeoMetadataPanel';
import {
  ArticleCommentsPanel,
  type ArticleCommentsSettings,
} from './ArticleCommentsPanel';

const PAGE_TEMPLATE_OPTIONS = [
  { value: 'default', label: 'Predvolená' },
  { value: 'home', label: 'Domov (hero)' },
  { value: 'about', label: 'O nás' },
  { value: 'contact', label: 'Kontakt' },
  { value: 'landing', label: 'Landing' },
  { value: 'services', label: 'Služby' },
  { value: 'blog', label: 'Blog' },
] as const;

interface ContentEditorShellProps {
  type: ContentType;
  isNew: boolean;
  title: string;
  editSlug: string;
  status: 'draft' | 'published' | 'archived';
  template: string;
  content: string;
  editorMode: EditorMode;
  seo: SeoFormValues;
  storagePath: string;
  publicPath: string;
  navigationMatches: NavigationItem[];
  canEdit: boolean;
  saving: boolean;
  seoOpen: boolean;
  autoSaveLabel?: string;
  lockIndicator?: React.ReactNode;
  onTitleChange: (value: string) => void;
  onSlugChange: (value: string) => void;
  onStatusChange: (value: 'draft' | 'published' | 'archived') => void;
  onTemplateChange: (value: string) => void;
  onDescriptionChange: (value: string) => void;
  onSeoChange: (values: SeoFormValues) => void;
  onSeoOpenChange: (open: boolean) => void;
  onEditorModeChange: (mode: EditorMode) => void;
  onCancel: () => void;
  onSave: () => void;
  children: React.ReactNode;
  footerExtra?: React.ReactNode;
  articleComments?: ArticleCommentsSettings;
  onArticleCommentsChange?: (value: ArticleCommentsSettings) => void;
  globalCommentsRequireApproval?: boolean;
  globalCommentsAllowGuests?: boolean;
}

const STATUS_LABELS: Record<ContentEditorShellProps['status'], string> = {
  draft: 'Koncept',
  published: 'Publikované',
  archived: 'Archivované',
};

export const ContentEditorShell: React.FC<ContentEditorShellProps> = ({
  type,
  isNew,
  title,
  editSlug,
  status,
  template,
  content,
  editorMode,
  seo,
  storagePath,
  publicPath,
  navigationMatches,
  canEdit,
  saving,
  seoOpen,
  autoSaveLabel,
  lockIndicator,
  onTitleChange,
  onSlugChange,
  onStatusChange,
  onTemplateChange,
  onDescriptionChange,
  onSeoChange,
  onSeoOpenChange,
  onEditorModeChange,
  onCancel,
  onSave,
  children,
  footerExtra,
  articleComments,
  onArticleCommentsChange,
  globalCommentsRequireApproval = true,
  globalCommentsAllowGuests = true,
}) => {
  const stats = countContentStats(content);
  const typeLabel = type === 'article' ? 'článok' : 'stránku';
  const listPath = type === 'article' ? '/articles' : '/pages';
  const previewPath = type === 'page' && !isNew && editSlug ? `/preview/${editSlug}` : null;
  const contextLabel =
    navigationMatches[0]?.label || title.trim() || editSlug || 'Nová položka';

  return (
    <div className="mx-auto w-full max-w-7xl">
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-950">
        <div className="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
          <div className="min-w-0 space-y-2">
            <div className="flex flex-wrap items-center gap-2">
              <h1 className="text-xl font-bold text-slate-900 dark:text-white">
                {isNew ? `Vytvoriť ${typeLabel}` : `Upraviť ${typeLabel}`}
              </h1>
              <span className="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-200">
                {type === 'page' ? <Home size={12} /> : <Link2 size={12} />}
                {contextLabel}
              </span>
            </div>
            <p className="font-mono text-xs text-slate-400 truncate">{storagePath}</p>
          </div>

          <div className="flex flex-wrap items-center gap-2">
            {lockIndicator}
            {autoSaveLabel && <span className="text-xs text-slate-400">{autoSaveLabel}</span>}
            <button
              type="button"
              className={`inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-semibold transition ${
                editorMode === 'wysiwyg'
                  ? 'border-indigo-600 bg-indigo-600 text-white'
                  : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300'
              }`}
              disabled={!canEdit}
              onClick={() => onEditorModeChange(editorMode === 'wysiwyg' ? 'markdown' : 'wysiwyg')}
            >
              <Sparkles size={14} />
              WYSIWYG
            </button>
            {previewPath && (
              <Link
                to={previewPath}
                target="_blank"
                className="inline-flex items-center justify-center rounded-xl border border-slate-200 p-2 text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-900"
                title="Náhľad na webe"
              >
                <Eye size={16} />
              </Link>
            )}
            <Link
              to={listPath}
              className="inline-flex items-center justify-center rounded-xl border border-slate-200 p-2 text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-900"
              title="Zavrieť"
            >
              <X size={16} />
            </Link>
          </div>
        </div>

        <div className="space-y-5 px-5 py-5">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="form-group md:col-span-2">
              <label className="form-label">Názov</label>
              <input
                type="text"
                value={title}
                onChange={(e) => onTitleChange(e.target.value)}
                disabled={!canEdit}
                className="form-input"
                placeholder="Titulok stránky alebo článku"
              />
            </div>

            <div className="form-group">
              <label className="form-label">Slug</label>
              <input
                type="text"
                value={editSlug}
                onChange={(e) => onSlugChange(e.target.value)}
                disabled={!canEdit || !isNew}
                className="form-input font-mono text-sm"
                placeholder="home"
              />
              {!isNew && (
                <p className="mt-1 text-xs text-slate-400">Slug sa po vytvorení nemení (URL ostáva stabilná).</p>
              )}
            </div>

            <div className="form-group">
              <label className="form-label">Stav</label>
              <select
                value={status}
                onChange={(e) => onStatusChange(e.target.value as ContentEditorShellProps['status'])}
                disabled={!canEdit}
                className="form-input"
              >
                {Object.entries(STATUS_LABELS).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </div>

            {type === 'page' && (
              <div className="form-group">
                <label className="form-label">Šablóna</label>
                <select
                  value={template || 'default'}
                  onChange={(e) => onTemplateChange(e.target.value)}
                  disabled={!canEdit}
                  className="form-input font-mono text-sm"
                >
                  {PAGE_TEMPLATE_OPTIONS.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
            )}

            <div className={`form-group ${type === 'page' ? '' : 'md:col-span-2'}`}>
              <label className="form-label flex justify-between">
                <span>Popis</span>
                <span className="text-xs font-normal text-slate-400">{seo.seoDescription.length}/160</span>
              </label>
              <textarea
                value={seo.seoDescription}
                onChange={(e) => onDescriptionChange(e.target.value)}
                disabled={!canEdit}
                className="form-input min-h-[72px]"
                placeholder="Krátky popis pre vyhľadávače a sociálne siete"
                maxLength={300}
              />
            </div>
          </div>

          <div className="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/40">
            <div className="flex flex-wrap items-start justify-between gap-3">
              <div className="space-y-2 text-sm">
                <p className="font-semibold text-slate-800 dark:text-slate-100">Kam smeruje táto položka</p>
                <p className="flex flex-wrap items-center gap-2 text-slate-600 dark:text-slate-300">
                  <ExternalLink size={14} />
                  <span>Verejná URL:</span>
                  <code className="rounded bg-white px-2 py-0.5 text-xs dark:bg-slate-950">{publicPath}</code>
                </p>
                <p className="flex flex-wrap items-center gap-2 text-slate-600 dark:text-slate-300">
                  <Menu size={14} />
                  <span>Položka menu:</span>
                  {navigationMatches.length > 0 ? (
                    navigationMatches.map((item) => (
                      <span
                        key={item.id}
                        className="inline-flex items-center rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-slate-950 dark:text-indigo-200"
                      >
                        {item.label} → {item.path}
                      </span>
                    ))
                  ) : (
                    <span className="text-slate-500">
                      Zatiaľ nie je v menu. Použite cestu <code>{publicPath}</code>.
                    </span>
                  )}
                </p>
              </div>
              <Link to="/navigation" className="btn btn-secondary text-xs whitespace-nowrap">
                Upraviť menu
              </Link>
            </div>
          </div>

          <div className="rounded-xl border border-slate-200 dark:border-slate-800">
            <button
              type="button"
              className="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-semibold text-slate-800 dark:text-slate-100"
              onClick={() => onSeoOpenChange(!seoOpen)}
            >
              <span>SEO nastavenia</span>
              <span className="text-xs font-normal text-slate-400">{seoOpen ? 'Skryť' : 'Zobraziť'}</span>
            </button>
            {seoOpen && (
              <div className="border-t border-slate-200 px-4 py-4 dark:border-slate-800">
                <SeoMetadataPanel
                  values={seo}
                  onChange={onSeoChange}
                  disabled={!canEdit}
                  showTags={type === 'article'}
                  compact
                />
              </div>
            )}
          </div>

          {type === 'article' && articleComments && onArticleCommentsChange ? (
            <ArticleCommentsPanel
              value={articleComments}
              onChange={onArticleCommentsChange}
              disabled={!canEdit}
              globalRequireApproval={globalCommentsRequireApproval}
              globalAllowGuests={globalCommentsAllowGuests}
            />
          ) : null}

          <div>{children}</div>

          {footerExtra}
        </div>

        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
          <p className="text-xs text-slate-400">
            {stats.characters} znakov • {stats.lines} riadkov
          </p>
          <div className="flex gap-2">
            <button type="button" className="btn btn-secondary" onClick={onCancel}>
              Zrušiť
            </button>
            <button
              type="button"
              className="btn btn-primary inline-flex items-center gap-2"
              disabled={saving || !canEdit}
              onClick={onSave}
            >
              <Save size={16} />
              {saving ? 'Ukladám…' : 'Uložiť'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
