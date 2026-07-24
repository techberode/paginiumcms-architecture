import React, { useMemo } from 'react';
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
import type { ContentEditorStatus } from '../../utils/contentScheduling';
import type { NavigationItem } from '../../api/navigation';
import type { EditorMode, ContentFormat } from '../../utils/contentEditor';
import type { EditorProfileId } from '../../utils/editorProfiles';
import { EditorProfilePicker } from './EditorProfilePicker';
import { countContentStats } from '../../utils/contentEditorMeta';
import { SeoMetadataPanel, type SeoFormValues } from './SeoMetadataPanel';
import { ArticleCommentsPanel } from './ArticleCommentsPanel';
import type { ArticleCommentsSettings } from '../../utils/articleCommentsSettings';
import { useOpenLinksInNewTab } from '../../hooks/useOpenLinksInNewTab';
import { ArticleTagsEditor } from './ArticleTagsEditor';
import { ContentMetaSuggestPanel } from './ContentMetaSuggestPanel';
import { linkTargetProps } from '../../utils/linkTarget';
import { useI18n } from '../../context/I18nContext';

const PAGE_TEMPLATE_VALUES = ['default', 'home', 'about', 'contact', 'landing', 'services', 'blog'] as const;

interface ContentEditorShellProps {
  type: ContentType;
  isNew: boolean;
  title: string;
  editSlug: string;
  status: ContentEditorStatus;
  scheduledAt: string;
  template: string;
  content: string;
  contentFormat: ContentFormat;
  editorMode: EditorMode;
  editorProfile: EditorProfileId;
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
  onStatusChange: (value: ContentEditorStatus) => void;
  onScheduledAtChange: (value: string) => void;
  onTemplateChange: (value: string) => void;
  onDescriptionChange: (value: string) => void;
  onSeoChange: (values: SeoFormValues) => void;
  onSeoOpenChange: (open: boolean) => void;
  onEditorModeChange: (mode: EditorMode) => void;
  onEditorProfileChange: (profileId: EditorProfileId) => void;
  onCancel: () => void;
  onSave: () => void;
  onOpenPreview?: () => void;
  children: React.ReactNode;
  footerExtra?: React.ReactNode;
  articleComments?: ArticleCommentsSettings;
  onArticleCommentsChange?: (value: ArticleCommentsSettings) => void;
  globalCommentsRequireApproval?: boolean;
  globalCommentsAllowGuests?: boolean;
}

export const ContentEditorShell: React.FC<ContentEditorShellProps> = ({
  type,
  isNew,
  title,
  editSlug,
  status,
  scheduledAt,
  template,
  content,
  contentFormat,
  editorMode,
  editorProfile,
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
  onScheduledAtChange,
  onTemplateChange,
  onDescriptionChange,
  onSeoChange,
  onSeoOpenChange,
  onEditorModeChange,
  onEditorProfileChange,
  onCancel,
  onSave,
  onOpenPreview,
  children,
  footerExtra,
  articleComments,
  onArticleCommentsChange,
  globalCommentsRequireApproval = true,
  globalCommentsAllowGuests = true,
}) => {
  const { t } = useI18n();
  const stats = countContentStats(content);
  const openInNewTab = useOpenLinksInNewTab();
  const listPath = type === 'article' ? '/articles' : '/pages';
  const previewPath = type === 'page' && !isNew && editSlug ? `/preview/${editSlug}` : null;
  const contextLabel =
    navigationMatches[0]?.label || title.trim() || editSlug || t('editor.shell.newItem');

  const heading = isNew
    ? type === 'article'
      ? t('editor.shell.createArticle')
      : t('editor.shell.createPage')
    : type === 'article'
      ? t('editor.shell.editArticle')
      : t('editor.shell.editPage');

  const statusLabels = useMemo(
    () => ({
      draft: t('editor.shell.statusLabels.draft'),
      published: t('editor.shell.statusLabels.published'),
      archived: t('editor.shell.statusLabels.archived'),
      scheduled: t('editor.shell.statusLabels.scheduled'),
    }),
    [t]
  );

  return (
    <div className="mx-auto w-full max-w-7xl">
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-950">
        <div className="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
          <div className="min-w-0 space-y-2">
            <div className="flex flex-wrap items-center gap-2">
              <h1 className="text-xl font-bold text-slate-900 dark:text-white">{heading}</h1>
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
              {t('editor.shell.wysiwyg')}
            </button>
            {onOpenPreview && (
              <button
                type="button"
                onClick={onOpenPreview}
                className="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-900 dark:bg-indigo-950/60 dark:text-indigo-200"
                title={t('editor.shell.previewTitle')}
              >
                <Eye size={14} />
                {t('editor.shell.preview')}
              </button>
            )}
            {previewPath && (
              <Link
                to={previewPath}
                {...linkTargetProps(openInNewTab)}
                className="inline-flex items-center justify-center rounded-xl border border-slate-200 p-2 text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-900"
                title={t('editor.shell.previewWebTitle')}
              >
                <Eye size={16} />
              </Link>
            )}
            <Link
              to={listPath}
              className="inline-flex items-center justify-center rounded-xl border border-slate-200 p-2 text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-900"
              title={t('editor.shell.close')}
            >
              <X size={16} />
            </Link>
          </div>
        </div>

        <div className="space-y-5 px-5 py-5">
          <div className="grid gap-4 md:grid-cols-2">
            <div className="form-group md:col-span-2">
              <label className="form-label">{t('editor.shell.title')}</label>
              <input
                type="text"
                value={title}
                onChange={(e) => onTitleChange(e.target.value)}
                disabled={!canEdit}
                className="form-input"
                placeholder={t('editor.shell.titlePlaceholder')}
              />
            </div>

            <div className="form-group">
              <label className="form-label">{t('editor.shell.slug')}</label>
              <input
                type="text"
                value={editSlug}
                onChange={(e) => onSlugChange(e.target.value)}
                disabled={!canEdit || !isNew}
                className="form-input font-mono text-sm"
                placeholder="home"
              />
              {!isNew && <p className="mt-1 text-xs text-slate-400">{t('editor.shell.slugHint')}</p>}
            </div>

            <div className="form-group">
              <label className="form-label">{t('editor.shell.status')}</label>
              <select
                value={status}
                onChange={(e) => onStatusChange(e.target.value as ContentEditorStatus)}
                disabled={!canEdit}
                className="form-input"
              >
                {Object.entries(statusLabels).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </div>

            <div className="form-group">
              <label className="form-label">{t('editor.shell.scheduledAt')}</label>
              <input
                type="datetime-local"
                value={scheduledAt}
                onChange={(e) => onScheduledAtChange(e.target.value)}
                disabled={!canEdit}
                className="form-input"
              />
              <p className="mt-1 text-xs text-slate-400">{t('editor.shell.scheduledAtHint')}</p>
            </div>

            {type === 'page' && (
              <div className="form-group">
                <label className="form-label">{t('editor.shell.template')}</label>
                <select
                  value={template || 'default'}
                  onChange={(e) => onTemplateChange(e.target.value)}
                  disabled={!canEdit}
                  className="form-input font-mono text-sm"
                >
                  {PAGE_TEMPLATE_VALUES.map((value) => (
                    <option key={value} value={value}>
                      {t(`editor.shell.templates.${value}`)}
                    </option>
                  ))}
                </select>
              </div>
            )}

            <div className={`form-group ${type === 'page' ? '' : 'md:col-span-2'}`}>
              <label className="form-label flex justify-between">
                <span>{t('editor.shell.description')}</span>
                <span className="text-xs font-normal text-slate-400">{seo.seoDescription.length}/160</span>
              </label>
              <textarea
                value={seo.seoDescription}
                onChange={(e) => onDescriptionChange(e.target.value)}
                disabled={!canEdit}
                className="form-input min-h-[72px]"
                placeholder={t('editor.shell.descriptionPlaceholder')}
                maxLength={300}
              />
            </div>
          </div>

          <ContentMetaSuggestPanel
            type={type}
            title={title}
            body={content}
            bodyFormat={contentFormat}
            tagsValue={seo.tags}
            descriptionValue={seo.seoDescription}
            disabled={!canEdit}
            onApplyTags={(value) => onSeoChange({ ...seo, tags: value })}
            onApplyDescription={onDescriptionChange}
          />

          <div className="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/40">
            <div className="flex flex-wrap items-start justify-between gap-3">
              <div className="space-y-2 text-sm">
                <p className="font-semibold text-slate-800 dark:text-slate-100">{t('editor.shell.routingTitle')}</p>
                <p className="flex flex-wrap items-center gap-2 text-slate-600 dark:text-slate-300">
                  <ExternalLink size={14} />
                  <span>{t('editor.shell.publicUrl')}</span>
                  <code className="rounded bg-white px-2 py-0.5 text-xs dark:bg-slate-950">{publicPath}</code>
                </p>
                <p className="flex flex-wrap items-center gap-2 text-slate-600 dark:text-slate-300">
                  <Menu size={14} />
                  <span>{t('editor.shell.menuItem')}</span>
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
                      {t('editor.shell.notInMenu', { path: publicPath })}
                    </span>
                  )}
                </p>
              </div>
              <Link to="/navigation" className="btn btn-secondary text-xs whitespace-nowrap">
                {t('editor.shell.editMenu')}
              </Link>
            </div>
          </div>

          {type === 'article' && (
            <ArticleTagsEditor
              value={seo.tags}
              onChange={(tags) => onSeoChange({ ...seo, tags })}
              disabled={!canEdit}
            />
          )}

          <div className="rounded-xl border border-slate-200 dark:border-slate-800">
            <button
              type="button"
              className="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-semibold text-slate-800 dark:text-slate-100"
              onClick={() => onSeoOpenChange(!seoOpen)}
            >
              <span>{t('editor.shell.seoSettings')}</span>
              <span className="text-xs font-normal text-slate-400">
                {seoOpen ? t('editor.shell.hide') : t('editor.shell.show')}
              </span>
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

          <div className="space-y-3">
            <EditorProfilePicker
              value={editorProfile}
              onChange={onEditorProfileChange}
              disabled={!canEdit}
            />
            <div>{children}</div>
          </div>

          {footerExtra}
        </div>

        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
          <p className="text-xs text-slate-400">
            {t('editor.shell.stats', { characters: stats.characters, lines: stats.lines })}
          </p>
          <div className="flex gap-2">
            <button type="button" className="btn btn-secondary" onClick={onCancel}>
              {t('editor.shell.cancel')}
            </button>
            <button
              type="button"
              className="btn btn-primary inline-flex items-center gap-2"
              disabled={saving || !canEdit}
              onClick={onSave}
            >
              <Save size={16} />
              {saving ? t('editor.shell.saving') : t('editor.shell.save')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
