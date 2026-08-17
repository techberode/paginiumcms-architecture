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
import { SeoHealthBadge } from './SeoHealthBadge';
import { getContentSeoHealthFromFields } from '../../utils/seoHealth';
import { ArticleCommentsPanel } from './ArticleCommentsPanel';
import type { ArticleCommentsSettings } from '../../utils/articleCommentsSettings';
import { useOpenLinksInNewTab } from '../../hooks/useOpenLinksInNewTab';
import { ArticleTagsEditor } from './ArticleTagsEditor';
import { ContentMetaSuggestPanel } from './ContentMetaSuggestPanel';
import { linkTargetProps } from '../../utils/linkTarget';
import { useI18n } from '../../context/I18nContext';
import { useSettingsContext } from '../../context/SettingsContext';
import { useAuth } from '../../hooks/useAuth';
import {
  PAGE_LAYOUT_TEMPLATES,
  normalizeLayoutBuilderMode,
  type LayoutBuilderMode,
} from '../../layout/pageLayoutTemplates';
import { LayoutPreviewFrame } from '../admin/LayoutPreviewFrame';
import { ShortcodeInsertPanel } from './ShortcodeInsertPanel';
import { SnippetInsertPanel } from './SnippetInsertPanel';
import {
  DEFAULT_COLOR_SCHEME_ID,
  isColorSchemeId,
  type AppearanceMode,
} from '../../theme/colorSchemes';

const PAGE_TEMPLATE_VALUES = ['default', 'home', 'about', 'contact', 'landing', 'services', 'blog'] as const;

interface ContentEditorShellProps {
  type: ContentType;
  isNew: boolean;
  title: string;
  editSlug: string;
  status: ContentEditorStatus;
  scheduledAt: string;
  template: string;
  layoutTemplate: string;
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
  onLayoutTemplateChange: (value: string) => void;
  onInsertShortcode?: (snippet: string) => void;
  onDescriptionChange: (value: string) => void;
  onSeoChange: (values: SeoFormValues) => void;
  onSeoOpenChange: (open: boolean) => void;
  onEditorModeChange: (mode: EditorMode) => void;
  onEditorProfileChange: (profileId: EditorProfileId) => void;
  onCancel: () => void;
  onSave: () => void;
  onMarkReviewed?: () => void;
  onOpenPreview?: () => void;
  children: React.ReactNode;
  footerExtra?: React.ReactNode;
  articleComments?: ArticleCommentsSettings;
  onArticleCommentsChange?: (value: ArticleCommentsSettings) => void;
  articleAuthor?: string;
  onArticleAuthorChange?: (value: string) => void;
  defaultBlogAuthor?: string;
  globalCommentsRequireApproval?: boolean;
  globalCommentsAllowGuests?: boolean;
  activeLocale?: string;
  localeOptions?: string[];
  localeStatusMap?: Record<string, ContentEditorStatus>;
  onLocaleChange?: (locale: string) => void;
}

export const ContentEditorShell: React.FC<ContentEditorShellProps> = ({
  type,
  isNew,
  title,
  editSlug,
  status,
  scheduledAt,
  template,
  layoutTemplate,
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
  onLayoutTemplateChange,
  onInsertShortcode,
  onDescriptionChange,
  onSeoChange,
  onSeoOpenChange,
  onEditorModeChange,
  onEditorProfileChange,
  onCancel,
  onSave,
  onMarkReviewed,
  onOpenPreview,
  children,
  footerExtra,
  articleComments,
  onArticleCommentsChange,
  articleAuthor,
  onArticleAuthorChange,
  defaultBlogAuthor = '',
  globalCommentsRequireApproval = true,
  globalCommentsAllowGuests = true,
  activeLocale,
  localeOptions = [],
  localeStatusMap = {},
  onLocaleChange,
}) => {
  const { t } = useI18n();
  const { settings } = useSettingsContext();
  const seoHealth = useMemo(
    () =>
      getContentSeoHealthFromFields({
        status,
        checkAsPublished: true,
        contentType: type,
        seoTitle: seo.seoTitle,
        seoDescription: seo.seoDescription,
        ogImage: seo.ogImage,
        tags: seo.tags,
      }),
    [status, type, seo.seoTitle, seo.seoDescription, seo.ogImage, seo.tags]
  );
  const { user } = useAuth();
  const stats = countContentStats(content);
  const openInNewTab = useOpenLinksInNewTab();
  const listPath = type === 'article' ? '/articles' : '/pages';
  const previewPath =
    type === 'page' && !isNew && editSlug
      ? `/preview/${editSlug}${activeLocale ? `?locale=${encodeURIComponent(activeLocale)}` : ''}`
      : null;
  const contextLabel =
    navigationMatches[0]?.label || title.trim() || editSlug || t('editor.shell.newItem');

  const builderMode: LayoutBuilderMode = normalizeLayoutBuilderMode(settings.layout?.builderMode);
  const developerRequiresAdmin = settings.layout?.developerRequiresAdmin !== false;
  const isAdmin = user?.roles?.some((role) => role === 'ADMIN' || role === 'SUPER_ADMIN') ?? false;
  const showLayoutTemplatePicker = type === 'page' && builderMode === 'templates';
  const showShortcodePicker = type === 'page' && builderMode === 'shortcodes' && Boolean(onInsertShortcode);
  const showDeveloperHint =
    type === 'page' && builderMode === 'developer' && developerRequiresAdmin && !isAdmin;

  const rawScheme = settings.appearance?.colorScheme ?? '';
  const schemeId = isColorSchemeId(rawScheme) ? rawScheme : DEFAULT_COLOR_SCHEME_ID;
  const appearanceMode = (settings.appearance?.mode as AppearanceMode | undefined) ?? 'system';

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
            {localeOptions.length > 1 && onLocaleChange && activeLocale && (
              <div className="flex flex-wrap items-center gap-2 pt-1">
                <span className="text-xs font-medium text-slate-500 dark:text-slate-400">
                  {t('editor.shell.contentLocale')}
                </span>
                <div className="flex flex-wrap gap-1.5">
                  {localeOptions.map((code) => {
                    const localeStatus = localeStatusMap[code] ?? 'draft';
                    const isActive = code === activeLocale;
                    return (
                      <button
                        key={code}
                        type="button"
                        disabled={!canEdit}
                        onClick={() => onLocaleChange(code)}
                        className={`inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-semibold transition ${
                          isActive
                            ? 'border-indigo-600 bg-indigo-600 text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300'
                        }`}
                      >
                        <span>{code.toUpperCase()}</span>
                        <span
                          className={`rounded px-1 py-0.5 text-[10px] font-medium ${
                            isActive
                              ? 'bg-indigo-500/30 text-white'
                              : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
                          }`}
                        >
                          {statusLabels[localeStatus] ?? localeStatus}
                        </span>
                      </button>
                    );
                  })}
                </div>
              </div>
            )}
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
            {!isNew && status === 'published' && onMarkReviewed && (
              <button
                type="button"
                onClick={onMarkReviewed}
                disabled={!canEdit || saving}
                className="inline-flex items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 disabled:opacity-60 dark:border-amber-900 dark:bg-amber-950/60 dark:text-amber-200"
              >
                {t('content.stale.markReviewed')}
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

            {showLayoutTemplatePicker && (
              <div className="form-group md:col-span-2 space-y-3">
                <label className="form-label">{t('editor.shell.layoutTemplate')}</label>
                <select
                  value={layoutTemplate || 'hero-content'}
                  onChange={(e) => onLayoutTemplateChange(e.target.value)}
                  disabled={!canEdit}
                  className="form-input text-sm"
                  data-testid="editor-layout-template"
                >
                  {PAGE_LAYOUT_TEMPLATES.map((entry) => (
                    <option key={entry.id} value={entry.id}>
                      {t(entry.nameKey)}
                    </option>
                  ))}
                </select>
                <LayoutPreviewFrame
                  templateId={layoutTemplate}
                  schemeId={schemeId}
                  mode={appearanceMode}
                  className="max-w-sm"
                />
              </div>
            )}

            {showShortcodePicker && onInsertShortcode && (
              <div className="form-group md:col-span-2">
                <ShortcodeInsertPanel
                  disabled={!canEdit}
                  onInsert={(snippet) => onInsertShortcode(snippet)}
                />
              </div>
            )}

            {onInsertShortcode && (
              <div className="form-group md:col-span-2">
                <SnippetInsertPanel
                  disabled={!canEdit}
                  onInsert={(snippet) => onInsertShortcode(snippet)}
                />
              </div>
            )}

            {showDeveloperHint && (
              <div className="form-group md:col-span-2">
                <p className="text-xs text-amber-700 dark:text-amber-400">
                  {t('editor.shell.developerLockedHint')}
                </p>
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

          {type === 'article' && onArticleAuthorChange && (
            <div className="rounded-xl border border-slate-200 dark:border-slate-800 p-4 space-y-2">
              <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                {t('editor.shell.articleAuthor')}
              </label>
              <input
                type="text"
                className="form-input w-full"
                value={articleAuthor ?? ''}
                disabled={!canEdit}
                placeholder={defaultBlogAuthor || t('editor.shell.articleAuthorPlaceholder')}
                onChange={(e) => onArticleAuthorChange(e.target.value)}
              />
              <p className="text-xs text-slate-500">{t('editor.shell.articleAuthorHint')}</p>
            </div>
          )}

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
              className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-sm font-semibold text-slate-800 dark:text-slate-100"
              onClick={() => onSeoOpenChange(!seoOpen)}
            >
              <span className="inline-flex items-center gap-2">
                {t('editor.shell.seoSettings')}
                {!seoOpen ? <SeoHealthBadge level={seoHealth.level} issues={seoHealth.issues} /> : null}
              </span>
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
                  contentStatus={status}
                  contentType={type}
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
