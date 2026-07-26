// frontend/src/components/backend/MarkdownEditor.tsx
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';
import { useAutoSave } from '../../hooks/useAutoSave';
import { LockIndicator } from '../locking/LockIndicator';
import { ConflictResolver } from '../versioning/ConflictResolver';
import { merge3, assembleMerged } from '../../utils/merge3';
import { loadDraft, discardDraft, type ContentType } from '../../api/drafts';
import { getNavigation } from '../../api/navigation';
import { uploadMedia, resolvePublicMediaUrl } from '../../api/media';
import { WysiwygEditor, WysiwygEditorHandle } from './WysiwygEditor';
import { MarkdownContentEditor } from './MarkdownContentEditor';
import { MediaPickerModal } from './MediaPickerModal';
import { VersionHistory } from '../CodeEditor/VersionHistory';
import { useSettingsContext } from '../../context/SettingsContext';
import { useAuth } from '../../hooks/useAuth';
import { ContentEditorShell } from './ContentEditorShell';
import { SitePreviewModal } from './SitePreviewModal';
import { OtpConfirmModal } from './OtpConfirmModal';
import { extractOtpPending } from '../../api/workflows';
import { type SeoFormValues } from './SeoMetadataPanel';
import {
  DEFAULT_ARTICLE_COMMENTS_SETTINGS,
  triStateFromApi,
  triStateToApi,
  type ArticleCommentsSettings,
} from '../../utils/articleCommentsSettings';
import {
  type ContentFormat,
  type EditorMode,
  convertForModeSwitch,
  inferContentFormat,
  storagePayloadFromEditor,
  markdownToHtml,
  valueForEditorMode,
} from '../../utils/contentEditor';
import {
  getEditorProfile,
  resolveDefaultProfileId,
  type EditorProfileId,
} from '../../utils/editorProfiles';
import {
  findNavigationMatches,
  resolvePublicPath,
  resolveStoragePath,
  slugifyTitle,
} from '../../utils/contentEditorMeta';
import {
  type ContentEditorLoadData,
  type ContentSaveResponse,
} from '../../utils/contentEditorApi';
import {
  resolveScheduledAtForSave,
  isoToDatetimeLocalValue,
  type ContentEditorStatus,
} from '../../utils/contentScheduling';
import { useI18n } from '../../context/I18nContext';

interface MarkdownEditorProps {
  type?: ContentType;
}

interface ConflictState {
  base: string;
  mine: string;
  theirs: string;
  serverRevision: string;
}

export const MarkdownEditor: React.FC<MarkdownEditorProps> = ({ type = 'page' }) => {
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();

  const [title, setTitle] = useState('');
  const [editSlug, setEditSlug] = useState('');
  const [slugTouched, setSlugTouched] = useState(false);
  const [template, setTemplate] = useState('');
  const [storagePath, setStoragePath] = useState('');
  const [content, setContent] = useState('');
  const [baseContent, setBaseContent] = useState('');
  const [status, setStatus] = useState<ContentEditorStatus>('draft');
  const [scheduledAt, setScheduledAt] = useState('');
  const [commitMessage, setCommitMessage] = useState('');
  const [baseRevision, setBaseRevision] = useState('');
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [canEdit, setCanEdit] = useState(true);
  const [conflict, setConflict] = useState<ConflictState | null>(null);
  const [pendingDraftAt, setPendingDraftAt] = useState<number | null>(null);
  const [editorMode, setEditorMode] = useState<EditorMode>('markdown');
  const [editorProfile, setEditorProfile] = useState<EditorProfileId>('company');
  const [contentFormat, setContentFormat] = useState<ContentFormat>('markdown');
  const [mediaPickerOpen, setMediaPickerOpen] = useState(false);
  const [seoOpen, setSeoOpen] = useState(false);
  const [publishOtp, setPublishOtp] = useState<{ challengeId: string; debugCode?: string } | null>(null);
  const [navigationItems, setNavigationItems] = useState<Awaited<ReturnType<typeof getNavigation>>>([]);
  const wysiwygRef = useRef<WysiwygEditorHandle>(null);
  const [seo, setSeo] = useState<SeoFormValues>({
    seoTitle: '',
    seoDescription: '',
    canonical: '',
    ogImage: '',
    noIndex: false,
    tags: '',
  });
  const [articleComments, setArticleComments] = useState<ArticleCommentsSettings>(
    DEFAULT_ARTICLE_COMMENTS_SETTINGS
  );
  const [previewOpen, setPreviewOpen] = useState(false);
  const [previewHtml, setPreviewHtml] = useState<string | undefined>();
  const [loadedCreatedAt, setLoadedCreatedAt] = useState<string | undefined>();
  const [loadedUpdatedAt, setLoadedUpdatedAt] = useState<string | undefined>();

  const { get, post, put } = useApi();
  const toast = useToast();
  const { t } = useI18n();
  const { settings } = useSettingsContext();
  const { user } = useAuth();
  const isNew = slug === 'new' || !slug;
  const endpoint = type === 'article' ? '/api/articles' : '/api/pages';
  const resourceId = useMemo(() => `${type}:${slug ?? ''}`, [type, slug]);
  const storageFormat = settings.content?.storageFormat === 'json' ? 'json' : 'md';

  const autoSave = useAutoSave({
    type,
    slug: slug ?? '',
    data: { title, content, status, baseRevision },
    enabled: !isNew && canEdit,
  });

  const editorProfileDefinition = useMemo(
    () => getEditorProfile(editorProfile),
    [editorProfile]
  );

  useEffect(() => {
    if (isNew) {
      const preferred: EditorMode =
        settings.editor?.defaultEditor === 'wysiwyg' ? 'wysiwyg' : 'markdown';
      setEditorMode(preferred);
      setContentFormat(preferred === 'wysiwyg' ? 'tiptap_json' : 'markdown');
    }
  }, [isNew, settings.editor?.defaultEditor]);

  useEffect(() => {
    if (isNew) {
      setEditorProfile(resolveDefaultProfileId(type, settings.editor as Record<string, unknown>));
    }
  }, [isNew, type, settings.editor]);

  useEffect(() => {
    void getNavigation().then(setNavigationItems).catch(() => setNavigationItems([]));
  }, []);

  useEffect(() => {
    if (isNew && !slugTouched) {
      setEditSlug(slugifyTitle(title));
    }
  }, [title, isNew, slugTouched]);

  const switchEditorMode = (mode: EditorMode) => {
    if (mode === editorMode) return;
    const converted = convertForModeSwitch(content, editorMode, mode);
    setContent(converted);
    setContentFormat(mode === 'wysiwyg' ? 'tiptap_json' : 'markdown');
    setEditorMode(mode);
  };

  const handleEditorImageUpload = useCallback(
    async (file: File): Promise<{ url: string; alt?: string } | null> => {
      const result = await uploadMedia(file, file.name, 'editor');
      if (!result.ok) {
        toast.error(result.error);
        return null;
      }

      return {
        url: resolvePublicMediaUrl(result.media.url),
        alt: result.media.altText || file.name,
      };
    },
    [toast]
  );

  useEffect(() => {
    if (!isNew && slug) {
      void loadContent();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [slug]);

  const loadContent = async () => {
    setLoading(true);
    try {
      const response = await get<ContentEditorLoadData>(`${endpoint}/${slug}`);
      if (response.success && response.data) {
        const raw = response.data.content || '';
        const fm = response.data.frontMatter ?? {};
        const format = inferContentFormat(raw, response.data.contentFormat ?? fm.contentFormat);
        const loadedModeRaw = String(response.data.editorMode ?? fm.editorMode ?? '');
        const preferred: EditorMode =
          settings.editor?.defaultEditor === 'wysiwyg' ? 'wysiwyg' : 'markdown';
        const loadedMode: EditorMode =
          loadedModeRaw === 'wysiwyg' || loadedModeRaw === 'markdown' ? loadedModeRaw : preferred;
        const loadedSlug = String(response.data.slug ?? slug ?? '');
        const loadedProfileRaw = String(response.data.editorProfile ?? fm.editorProfile ?? '');

        setContentFormat(format);
        setEditorMode(loadedMode);
        setEditorProfile(
          loadedProfileRaw !== ''
            ? getEditorProfile(loadedProfileRaw).id
            : resolveDefaultProfileId(type, settings.editor as Record<string, unknown>)
        );
        setContent(valueForEditorMode(raw, format, loadedMode));
        setBaseContent(raw);
        setTitle(response.data.title || '');
        setEditSlug(loadedSlug);
        setTemplate(String(response.data.template ?? fm.template ?? 'default'));
        setStoragePath(
          resolveStoragePath(type, loadedSlug, String(response.data.path ?? ''), storageFormat)
        );
        setStatus((response.data.status as ContentEditorStatus) || 'draft');
        setScheduledAt(
          isoToDatetimeLocalValue(
            String(response.data.scheduledAt ?? fm.scheduledAt ?? '')
          )
        );
        setLoadedCreatedAt(String(response.data.createdAt ?? ''));
        setLoadedUpdatedAt(String(response.data.updatedAt ?? ''));
        setBaseRevision(response.data.revision || '');
        setSeo({
          seoTitle: String(response.data.seoTitle ?? fm.seoTitle ?? fm.metaTitle ?? ''),
          seoDescription: String(
            response.data.seoDescription ?? fm.seoDescription ?? fm.description ?? ''
          ),
          canonical: String(response.data.canonical ?? fm.canonical ?? ''),
          ogImage: String(
            response.data.ogImage ?? response.data.featuredImage ?? fm.seoImage ?? ''
          ),
          noIndex: Boolean(response.data.noIndex ?? fm.noIndex ?? fm.noindex ?? false),
          tags: Array.isArray(response.data.tags)
            ? response.data.tags.join(', ')
            : Array.isArray(fm.tags)
              ? fm.tags.map(String).join(', ')
              : '',
        });
        if (type === 'article') {
          setArticleComments({
            commentsEnabled: response.data.commentsEnabled !== false,
            commentsRequireApproval: triStateFromApi(response.data.commentsRequireApproval),
            commentsAllowGuests: triStateFromApi(response.data.commentsAllowGuests),
          });
        }
      }

      if (slug) {
        const draft = await loadDraft(type, slug);
        if (draft && draft.savedAt > 0) {
          setPendingDraftAt(draft.savedAt);
        }
      }
    } catch (error) {
      toast.error(t('editor.markdown.toast.loadFailed'));
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const restoreDraft = async () => {
    if (!slug) return;
    const draft = await loadDraft(type, slug);
    if (draft) {
      const format = inferContentFormat(draft.content);
      setTitle(draft.title);
      setContentFormat(format);
      setContent(valueForEditorMode(draft.content, format, editorMode));
      setStatus((draft.status as typeof status) || 'draft');
      toast.info(t('editor.markdown.toast.draftRestored'));
    }
    setPendingDraftAt(null);
  };

  const dismissDraft = async () => {
    if (slug) {
      await discardDraft(type, slug);
    }
    setPendingDraftAt(null);
  };

  const handleStatusChange = (value: ContentEditorStatus) => {
    setStatus(value);
    if (value !== 'scheduled') {
      setScheduledAt('');
    }
  };

  const handleScheduledAtChange = (value: string) => {
    setScheduledAt(value);
    if (value) {
      setStatus('scheduled');
      return;
    }

    if (status === 'scheduled') {
      setStatus('draft');
    }
  };

  const handleSave = useCallback(
    async (forceRevision?: string, contentOverride?: string) => {
      if (!title.trim()) {
        toast.warning(t('editor.markdown.toast.titleRequired'));
        return;
      }

      const effectiveContent = contentOverride ?? content;
      const stored = storagePayloadFromEditor(effectiveContent, editorMode);
      const nextSlug = isNew ? slugifyTitle(editSlug || title) : slug;

      if (isNew && !nextSlug) {
        toast.warning(t('editor.markdown.toast.slugRequired'));
        return;
      }

      setSaving(true);
      try {
        const data: Record<string, unknown> = {
          title: title.trim(),
          content: stored.content,
          contentFormat: stored.contentFormat,
          status,
          slug: nextSlug,
          message: commitMessage.trim(),
          baseRevision: forceRevision ?? baseRevision,
          seoTitle: seo.seoTitle.trim(),
          seoDescription: seo.seoDescription.trim(),
          canonical: seo.canonical.trim(),
          ogImage: seo.ogImage.trim(),
          noIndex: seo.noIndex,
          tags: seo.tags
            .split(',')
            .map((tag) => tag.trim())
            .filter(Boolean),
          editorProfile,
          editorMode,
        };

        data.scheduledAt = resolveScheduledAtForSave(status, scheduledAt);

        if (type === 'page' && template.trim()) {
          data.template = template.trim();
        }

        if (type === 'article') {
          data.commentsEnabled = articleComments.commentsEnabled;
          data.commentsRequireApproval = triStateToApi(articleComments.commentsRequireApproval);
          data.commentsAllowGuests = triStateToApi(articleComments.commentsAllowGuests);
        }

        const response = isNew
          ? await post<ContentSaveResponse>(endpoint, data)
          : await put<ContentSaveResponse>(`${endpoint}/${slug}`, data);

        const responseObj = response as unknown as Record<string, unknown>;
        const otpPending = extractOtpPending(responseObj);
        if (otpPending) {
          setPublishOtp({ challengeId: otpPending.challengeId, debugCode: otpPending.debugCode });
          toast.info(t('editor.markdown.toast.otpSent'));
          if (otpPending.debugCode) {
            toast.warning(t('editor.markdown.toast.devOtp', { code: otpPending.debugCode }));
          }
          const responseSlug = responseObj.slug;
          if (isNew && typeof responseSlug === 'string' && responseSlug !== '') {
            navigate(`/${type === 'article' ? 'articles' : 'pages'}/${responseSlug}`);
          }
          return;
        }

        if (response.success) {
          setConflict(null);
          setCommitMessage('');
          setContentFormat(stored.contentFormat);
          setBaseContent(stored.content);
          if (contentOverride !== undefined) {
            setContent(effectiveContent);
          }
          if (response.data?.revision) {
            setBaseRevision(response.data.revision);
          }
          if (response.data?.path) {
            setStoragePath(
              resolveStoragePath(
                type,
                String(response.data.slug ?? nextSlug),
                String(response.data.path),
                storageFormat
              )
            );
          }
          if (!isNew && slug) {
            await discardDraft(type, slug);
          }
          toast.success(t('editor.markdown.toast.saved'));
          if (isNew && response.data?.slug) {
            navigate(`/${type === 'article' ? 'articles' : 'pages'}/${response.data.slug}`);
          }
        } else if (response.status === 409 && response.conflict) {
          const c = response.conflict as { serverContent: string; serverRevision: string };
          const merge = merge3(effectiveContent, baseContent, c.serverContent);

          if (merge.clean) {
            const merged = assembleMerged(merge, {});
            toast.info(t('editor.markdown.toast.autoMerged'));
            await handleSave(c.serverRevision, merged);
          } else {
            setConflict({
              base: baseContent,
              mine: effectiveContent,
              theirs: c.serverContent,
              serverRevision: c.serverRevision,
            });
            toast.error(t('editor.markdown.toast.conflict', { count: merge.conflictCount }));
          }
        } else {
          toast.error(response.error || t('editor.markdown.toast.saveFailed'));
        }
      } catch (error) {
        toast.error(t('editor.markdown.toast.saveFailed'));
        console.error(error);
      } finally {
        setSaving(false);
      }
    },
    [
      title,
      content,
      status,
      scheduledAt,
      commitMessage,
      baseRevision,
      baseContent,
      isNew,
      slug,
      editSlug,
      template,
      endpoint,
      type,
      seo,
      editorMode,
      editorProfile,
      storageFormat,
      articleComments,
      post,
      put,
      navigate,
      toast,
      t,
    ]
  );

  const resolveConflict = (merged: string): void => {
    if (!conflict) return;
    const rev = conflict.serverRevision;
    setConflict(null);
    void handleSave(rev, merged);
  };

  const cancelConflict = (): void => {
    setConflict(null);
    toast.info(t('editor.markdown.toast.conflictCancelled'));
  };

  const publicPath = resolvePublicPath(type, editSlug || slug || '');
  const resolvedStoragePath =
    storagePath || resolveStoragePath(type, editSlug || slug || 'new', undefined, storageFormat);
  const navigationMatches = useMemo(
    () => findNavigationMatches(navigationItems, type, editSlug || slug || ''),
    [navigationItems, type, editSlug, slug]
  );

  const previewDraft = useMemo(() => {
    const stored = storagePayloadFromEditor(content, editorMode);
    const html =
      previewHtml ??
      (stored.contentFormat === 'html'
        ? stored.content
        : editorMode === 'wysiwyg'
          ? undefined
          : markdownToHtml(content));

    return {
      type,
      title,
      slug: editSlug || slugifyTitle(title) || 'preview',
      template,
      content: stored.contentFormat === 'html' || stored.contentFormat === 'tiptap_json' ? '' : stored.content,
      html,
      author: user?.name || t('editor.markdown.defaultAuthor'),
      tags: seo.tags
        .split(',')
        .map((tag) => tag.trim())
        .filter(Boolean),
      seoDescription: seo.seoDescription,
      createdAt: loadedCreatedAt,
      updatedAt: loadedUpdatedAt,
    };
  }, [
    content,
    editSlug,
    editorMode,
    loadedCreatedAt,
    loadedUpdatedAt,
    previewHtml,
    seo.seoDescription,
    seo.tags,
    template,
    title,
    type,
    user?.name,
    t,
  ]);

  const autoSaveLabel =
    autoSave.status === 'saving'
      ? t('editor.markdown.autoSave.saving')
      : autoSave.status === 'saved'
        ? t('editor.markdown.autoSave.saved')
        : autoSave.status === 'error'
          ? t('editor.markdown.autoSave.error')
          : '';

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {pendingDraftAt && (
        <div className="mx-auto max-w-7xl flex items-center justify-between rounded-xl bg-blue-50 dark:bg-blue-900/30 px-4 py-3 text-sm text-blue-800 dark:text-blue-200">
          <span>{t('editor.markdown.draftBanner.message')}</span>
          <span className="flex gap-2">
            <button
              onClick={() => void restoreDraft()}
              className="rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700"
            >
              {t('editor.markdown.draftBanner.restore')}
            </button>
            <button
              onClick={() => void dismissDraft()}
              className="rounded px-3 py-1 hover:bg-blue-100 dark:hover:bg-blue-800"
            >
              {t('editor.markdown.draftBanner.dismiss')}
            </button>
          </span>
        </div>
      )}

      {conflict && (
        <ConflictResolver
          base={conflict.base}
          mine={conflict.mine}
          theirs={conflict.theirs}
          onResolve={resolveConflict}
          onCancel={cancelConflict}
        />
      )}

      <ContentEditorShell
        type={type}
        isNew={isNew}
        title={title}
        editSlug={editSlug}
        status={status}
        scheduledAt={scheduledAt}
        template={template}
        content={content}
        contentFormat={contentFormat}
        editorMode={editorMode}
        editorProfile={editorProfile}
        seo={seo}
        storagePath={resolvedStoragePath}
        publicPath={publicPath}
        navigationMatches={navigationMatches}
        canEdit={canEdit}
        saving={saving}
        seoOpen={seoOpen}
        autoSaveLabel={autoSaveLabel}
        lockIndicator={!isNew ? <LockIndicator resourceId={resourceId} onLockChange={setCanEdit} /> : null}
        onTitleChange={setTitle}
        onSlugChange={(value) => {
          setSlugTouched(true);
          setEditSlug(value);
        }}
        onStatusChange={handleStatusChange}
        onScheduledAtChange={handleScheduledAtChange}
        onTemplateChange={setTemplate}
        onDescriptionChange={(value) => setSeo((prev) => ({ ...prev, seoDescription: value }))}
        onSeoChange={setSeo}
        onSeoOpenChange={setSeoOpen}
        onEditorModeChange={switchEditorMode}
        onEditorProfileChange={setEditorProfile}
        onCancel={() => navigate(type === 'article' ? '/articles' : '/pages')}
        onSave={() => void handleSave()}
        onOpenPreview={() => {
          setPreviewHtml(
            editorMode === 'wysiwyg'
              ? wysiwygRef.current?.getHtml()
              : markdownToHtml(content)
          );
          setPreviewOpen(true);
        }}
        articleComments={type === 'article' ? articleComments : undefined}
        onArticleCommentsChange={type === 'article' ? setArticleComments : undefined}
        globalCommentsRequireApproval={settings.comments?.requireApproval !== false}
        globalCommentsAllowGuests={settings.comments?.allowGuestComments !== false}
        footerExtra={
          <div className="form-group">
            <label className="form-label">{t('editor.markdown.commitMessage.label')}</label>
            <input
              type="text"
              value={commitMessage}
              onChange={(e) => setCommitMessage(e.target.value)}
              disabled={!canEdit}
              className="form-input"
              placeholder={t('editor.markdown.commitMessage.placeholder')}
            />
          </div>
        }
      >
        {editorMode === 'wysiwyg' ? (
          <WysiwygEditor
            ref={wysiwygRef}
            value={content}
            storedFormat={contentFormat}
            onChange={setContent}
            readOnly={!canEdit}
            onPickMedia={() => setMediaPickerOpen(true)}
            onUploadImage={handleEditorImageUpload}
            profile={editorProfileDefinition}
            onBlockedAction={(message) => toast.warning(message)}
          />
        ) : (
          <MarkdownContentEditor
            value={content}
            onChange={setContent}
            readOnly={!canEdit}
            spellCheck={Boolean(settings.editor?.spellcheck ?? true)}
            tabSize={Number(settings.editor?.tabSize ?? 2)}
            onPickMedia={() => setMediaPickerOpen(true)}
            profile={editorProfileDefinition}
            onBlockedAction={(message) => toast.warning(message)}
          />
        )}

        <MediaPickerModal
          open={mediaPickerOpen}
          onClose={() => setMediaPickerOpen(false)}
          onSelect={(url, alt) => {
            if (editorMode === 'wysiwyg') {
              wysiwygRef.current?.insertImage(url, alt);
            } else {
              setContent((prev) => `${prev}\n\n![${alt}](${url})\n`);
            }
          }}
        />
      </ContentEditorShell>

      {!isNew && slug && (
        <div className="mx-auto max-w-7xl card">
          <div className="card-header">
            <h2 className="text-lg font-bold">{t('editor.markdown.versionHistory')}</h2>
          </div>
          <div className="card-body">
            <VersionHistory contentId={slug} onRestore={() => void loadContent()} />
          </div>
        </div>
      )}

      <SitePreviewModal
        open={previewOpen}
        onClose={() => setPreviewOpen(false)}
        draft={previewDraft}
      />

      <OtpConfirmModal
        open={publishOtp !== null}
        title={t('editor.markdown.otpPublish.title')}
        description={t('editor.markdown.otpPublish.description')}
        challengeId={publishOtp?.challengeId ?? ''}
        debugCode={publishOtp?.debugCode}
        onClose={() => setPublishOtp(null)}
        onVerified={() => {
          setStatus('published');
          toast.success(t('editor.markdown.toast.published'));
        }}
      />
    </div>
  );
};

export default MarkdownEditor;
