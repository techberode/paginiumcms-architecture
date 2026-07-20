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
} from './ArticleCommentsPanel';
import {
  type ContentFormat,
  type EditorMode,
  convertForModeSwitch,
  inferContentFormat,
  storagePayloadFromEditor,
  valueForEditorMode,
} from '../../utils/contentEditor';
import {
  findNavigationMatches,
  resolvePublicPath,
  resolveStoragePath,
  slugifyTitle,
} from '../../utils/contentEditorMeta';

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
  const [status, setStatus] = useState<'draft' | 'published' | 'archived'>('draft');
  const [commitMessage, setCommitMessage] = useState('');
  const [baseRevision, setBaseRevision] = useState('');
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [canEdit, setCanEdit] = useState(true);
  const [conflict, setConflict] = useState<ConflictState | null>(null);
  const [pendingDraftAt, setPendingDraftAt] = useState<number | null>(null);
  const [editorMode, setEditorMode] = useState<EditorMode>('markdown');
  const [, setContentFormat] = useState<ContentFormat>('markdown');
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
  const [loadedCreatedAt, setLoadedCreatedAt] = useState<string | undefined>();
  const [loadedUpdatedAt, setLoadedUpdatedAt] = useState<string | undefined>();

  const { get, post, put } = useApi();
  const toast = useToast();
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

  useEffect(() => {
    const preferred: EditorMode = settings.editor?.defaultEditor === 'wysiwyg' ? 'wysiwyg' : 'markdown';
    setEditorMode(preferred);
  }, [settings.editor?.defaultEditor]);

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
    setContentFormat(mode === 'wysiwyg' ? 'html' : 'markdown');
    setEditorMode(mode);
  };

  useEffect(() => {
    if (!isNew && slug) {
      void loadContent();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [slug]);

  const loadContent = async () => {
    setLoading(true);
    try {
      const response = await get<any>(`${endpoint}/${slug}`);
      if (response.success && response.data) {
        const raw = response.data.content || '';
        const fm = response.data.frontMatter ?? {};
        const format = inferContentFormat(raw, response.data.contentFormat ?? fm.contentFormat);
        const preferred: EditorMode =
          settings.editor?.defaultEditor === 'wysiwyg' ? 'wysiwyg' : 'markdown';
        const loadedSlug = String(response.data.slug ?? slug ?? '');

        setContentFormat(format);
        setEditorMode(preferred);
        setContent(valueForEditorMode(raw, format, preferred));
        setBaseContent(raw);
        setTitle(response.data.title || '');
        setEditSlug(loadedSlug);
        setTemplate(String(response.data.template ?? fm.template ?? 'default'));
        setStoragePath(
          resolveStoragePath(type, loadedSlug, String(response.data.path ?? ''), storageFormat)
        );
        setStatus(response.data.status || 'draft');
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
      toast.error('Nepodarilo sa načítať obsah');
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
      toast.info('Koncept bol obnovený');
    }
    setPendingDraftAt(null);
  };

  const dismissDraft = async () => {
    if (slug) {
      await discardDraft(type, slug);
    }
    setPendingDraftAt(null);
  };

  const handleSave = useCallback(
    async (forceRevision?: string, contentOverride?: string) => {
      if (!title.trim()) {
        toast.warning('Zadajte prosím názov');
        return;
      }

      const effectiveContent = contentOverride ?? content;
      const stored = storagePayloadFromEditor(effectiveContent, editorMode);
      const nextSlug = isNew ? slugifyTitle(editSlug || title) : slug;

      if (isNew && !nextSlug) {
        toast.warning('Slug nemôže byť prázdny');
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
        };

        if (type === 'page' && template.trim()) {
          data.template = template.trim();
        }

        if (type === 'article') {
          data.commentsEnabled = articleComments.commentsEnabled;
          data.commentsRequireApproval = triStateToApi(articleComments.commentsRequireApproval);
          data.commentsAllowGuests = triStateToApi(articleComments.commentsAllowGuests);
        }

        const response = isNew
          ? await post<any>(endpoint, data)
          : await put<any>(`${endpoint}/${slug}`, data);

        const responseObj = response as unknown as Record<string, unknown>;
        const otpPending = extractOtpPending(responseObj);
        if (otpPending) {
          setPublishOtp({ challengeId: otpPending.challengeId, debugCode: otpPending.debugCode });
          toast.info('Overovací kód pre publikáciu bol odoslaný na email');
          if (otpPending.debugCode) {
            toast.warning(`Dev OTP: ${otpPending.debugCode}`);
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
          toast.success('Obsah bol uložený');
          if (isNew && response.data?.slug) {
            navigate(`/${type === 'article' ? 'articles' : 'pages'}/${response.data.slug}`);
          }
        } else if (response.status === 409 && response.conflict) {
          const c = response.conflict as { serverContent: string; serverRevision: string };
          const merge = merge3(effectiveContent, baseContent, c.serverContent);

          if (merge.clean) {
            const merged = assembleMerged(merge, {});
            toast.info('Zmeny boli automaticky zlúčené so serverovou verziou.');
            await handleSave(c.serverRevision, merged);
          } else {
            setConflict({
              base: baseContent,
              mine: effectiveContent,
              theirs: c.serverContent,
              serverRevision: c.serverRevision,
            });
            toast.error(`Konflikt (${merge.conflictCount}) – vyriešte ho prosím manuálne.`);
          }
        } else {
          toast.error(response.error || 'Uloženie zlyhalo');
        }
      } catch (error) {
        toast.error('Uloženie zlyhalo');
        console.error(error);
      } finally {
        setSaving(false);
      }
    },
    [
      title,
      content,
      status,
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
      storageFormat,
      articleComments,
      post,
      put,
      navigate,
      toast,
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
    toast.info('Riešenie konfliktu zrušené. Vaše zmeny ostali neuložené.');
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
    return {
      type,
      title,
      slug: editSlug || slugifyTitle(title) || 'preview',
      template,
      content: stored.contentFormat === 'html' ? '' : stored.content,
      html: stored.contentFormat === 'html' ? stored.content : undefined,
      author: user?.name || 'Redakcia',
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
    seo.seoDescription,
    seo.tags,
    template,
    title,
    type,
    user?.name,
  ]);

  const autoSaveLabel =
    autoSave.status === 'saving'
      ? 'Ukladám koncept…'
      : autoSave.status === 'saved'
        ? 'Koncept uložený'
        : autoSave.status === 'error'
          ? 'Koncept sa nepodarilo uložiť'
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
          <span>Našiel sa neuložený koncept. Chcete ho obnoviť?</span>
          <span className="flex gap-2">
            <button
              onClick={() => void restoreDraft()}
              className="rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700"
            >
              Obnoviť
            </button>
            <button
              onClick={() => void dismissDraft()}
              className="rounded px-3 py-1 hover:bg-blue-100 dark:hover:bg-blue-800"
            >
              Zahodiť
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
        template={template}
        content={content}
        editorMode={editorMode}
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
        onStatusChange={setStatus}
        onTemplateChange={setTemplate}
        onDescriptionChange={(value) => setSeo((prev) => ({ ...prev, seoDescription: value }))}
        onSeoChange={setSeo}
        onSeoOpenChange={setSeoOpen}
        onEditorModeChange={switchEditorMode}
        onCancel={() => navigate(type === 'article' ? '/articles' : '/pages')}
        onSave={() => void handleSave()}
        onOpenPreview={() => setPreviewOpen(true)}
        articleComments={type === 'article' ? articleComments : undefined}
        onArticleCommentsChange={type === 'article' ? setArticleComments : undefined}
        globalCommentsRequireApproval={settings.comments?.requireApproval !== false}
        globalCommentsAllowGuests={settings.comments?.allowGuestComments !== false}
        footerExtra={
          <div className="form-group">
            <label className="form-label">Popis zmeny (voliteľné)</label>
            <input
              type="text"
              value={commitMessage}
              onChange={(e) => setCommitMessage(e.target.value)}
              disabled={!canEdit}
              className="form-input"
              placeholder="Napr. Aktualizácia úvodného odseku…"
            />
          </div>
        }
      >
        {editorMode === 'wysiwyg' ? (
          <WysiwygEditor
            ref={wysiwygRef}
            value={content}
            onChange={setContent}
            readOnly={!canEdit}
            onPickMedia={() => setMediaPickerOpen(true)}
          />
        ) : (
          <MarkdownContentEditor
            value={content}
            onChange={setContent}
            readOnly={!canEdit}
            spellCheck={Boolean(settings.editor?.spellcheck ?? true)}
            tabSize={Number(settings.editor?.tabSize ?? 2)}
            onPickMedia={() => setMediaPickerOpen(true)}
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
            <h2 className="text-lg font-bold">História verzií</h2>
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
        title="Confirm publish"
        description="Enter the verification code sent to your email to publish this content."
        challengeId={publishOtp?.challengeId ?? ''}
        debugCode={publishOtp?.debugCode}
        onClose={() => setPublishOtp(null)}
        onVerified={() => {
          setStatus('published');
          toast.success('Obsah bol publikovaný');
        }}
      />
    </div>
  );
};

export default MarkdownEditor;
