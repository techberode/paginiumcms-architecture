// frontend/src/components/backend/MarkdownEditor.tsx
// === Editor obsahu (Iterácia 2 + 3 – integrácia) ===
// Zapája:
//  - LockIndicator + zamykanie (Iterácia 1): kým súbor upravuje niekto iný, editor je uzamknutý,
//  - useAutoSave: každých 60 s ukladá koncept (draft flat-file),
//  - optimistické zamykanie: pri uložení posiela baseRevision; pri 409 konflikte,
//  - 3-way merge (Iterácia 3): pri 409 sa pokúsi o automatické zlúčenie; ak nastane
//    konflikt riadkov, otvorí ConflictResolver na manuálne rozhodnutie,
//  - commit správu: voliteľný popis zmeny ukladaný k verzii.
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';
import { useAutoSave } from '../../hooks/useAutoSave';
import { LockIndicator } from '../locking/LockIndicator';
import { ConflictResolver } from '../versioning/ConflictResolver';
import { merge3, assembleMerged } from '../../utils/merge3';
import { loadDraft, discardDraft, type ContentType } from '../../api/drafts';
import { WysiwygEditor } from './WysiwygEditor';
import { MediaPickerModal } from './MediaPickerModal';
import { VersionHistory } from '../CodeEditor/VersionHistory';
import { useSettingsContext } from '../../context/SettingsContext';

interface MarkdownEditorProps {
  type?: ContentType;
}

// Stav konfliktu pre 3-way merge (base = pôvodne načítané, mine = moje, theirs = server).
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
  const [content, setContent] = useState('');
  // Pôvodne načítaný obsah (spoločný predok pre 3-way merge).
  const [baseContent, setBaseContent] = useState('');
  const [status, setStatus] = useState<'draft' | 'published' | 'archived'>('draft');
  const [commitMessage, setCommitMessage] = useState('');
  const [baseRevision, setBaseRevision] = useState('');
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);

  // Zámok: kým súbor drží niekto iný, canEdit = false.
  const [canEdit, setCanEdit] = useState(true);
  // Konflikt (409) vyžadujúci manuálne riešenie cez ConflictResolver.
  const [conflict, setConflict] = useState<ConflictState | null>(null);
  // Neuložený koncept nájdený pri načítaní.
  const [pendingDraftAt, setPendingDraftAt] = useState<number | null>(null);
  const [editorMode, setEditorMode] = useState<'markdown' | 'wysiwyg'>('markdown');
  const [mediaPickerOpen, setMediaPickerOpen] = useState(false);

  const { get, post, put } = useApi();
  const toast = useToast();
  const { settings } = useSettingsContext();
  const isNew = slug === 'new' || !slug;
  const endpoint = type === 'article' ? '/api/articles' : '/api/pages';
  const resourceId = useMemo(() => `${type}:${slug ?? ''}`, [type, slug]);

  // === Blok: Auto-save konceptu (60 s) ===
  const autoSave = useAutoSave({
    type,
    slug: slug ?? '',
    data: { title, content, status, baseRevision },
    enabled: !isNew && canEdit,
  });

  useEffect(() => {
    const preferred = settings.editor?.defaultEditor === 'wysiwyg' ? 'wysiwyg' : 'markdown';
    setEditorMode(preferred);
  }, [settings.editor?.defaultEditor]);

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
        setTitle(response.data.title || '');
        setContent(response.data.content || '');
        setBaseContent(response.data.content || '');
        setStatus(response.data.status || 'draft');
        setBaseRevision(response.data.revision || '');
      }

      // Ak existuje neuložený koncept, ponúkneme jeho obnovenie.
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

  // === Blok: Obnova / zahodenie konceptu ===
  const restoreDraft = async () => {
    if (!slug) return;
    const draft = await loadDraft(type, slug);
    if (draft) {
      setTitle(draft.title);
      setContent(draft.content);
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

  // === Blok: Uloženie (s optimistickým zamykaním a 3-way merge) ===
  // contentOverride = použije sa namiesto stavu `content` (napr. výsledok auto-merge/resolvera),
  // aby sme nepracovali so zastaraným stavom hneď po setContent.
  const handleSave = useCallback(
    async (forceRevision?: string, contentOverride?: string) => {
      if (!title.trim()) {
        toast.warning('Zadajte prosím titulok');
        return;
      }

      const effectiveContent = contentOverride ?? content;

      setSaving(true);
      try {
        const data = {
          title: title.trim(),
          content: effectiveContent,
          status,
          slug: isNew ? title.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-') : slug,
          message: commitMessage.trim(),
          baseRevision: forceRevision ?? baseRevision,
        };

        const response = isNew ? await post<any>(endpoint, data) : await put<any>(`${endpoint}/${slug}`, data);

        if (response.success) {
          setConflict(null);
          setCommitMessage('');
          setBaseContent(effectiveContent);
          if (contentOverride !== undefined) {
            setContent(effectiveContent);
          }
          if (response.data?.revision) {
            setBaseRevision(response.data.revision);
          }
          // Po úspešnom uložení zahodíme koncept – už je súčasťou publikovaného obsahu.
          if (!isNew && slug) {
            await discardDraft(type, slug);
          }
          toast.success('Obsah bol uložený');
          if (isNew && response.data?.slug) {
            navigate(`/${type}s/${response.data.slug}`);
          }
        } else if (response.status === 409 && response.conflict) {
          // Konflikt: obsah na disku sa medzičasom zmenil → pokus o 3-way merge.
          const c = response.conflict as { serverContent: string; serverRevision: string };
          const merge = merge3(effectiveContent, baseContent, c.serverContent);

          if (merge.clean) {
            // Bez konfliktných riadkov → automatické zlúčenie a okamžité douloženie.
            const merged = assembleMerged(merge, {});
            toast.info('Zmeny boli automaticky zlúčené so serverovou verziou.');
            await handleSave(c.serverRevision, merged);
          } else {
            // Konfliktné riadky → manuálne riešenie cez ConflictResolver.
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
    [title, content, status, commitMessage, baseRevision, baseContent, isNew, slug, endpoint, type, get, post, put] // eslint-disable-line react-hooks/exhaustive-deps
  );

  // === Blok: Riešenie konfliktu (ConflictResolver) ===
  const resolveConflict = (merged: string): void => {
    if (!conflict) return;
    const rev = conflict.serverRevision;
    setConflict(null);
    // Uložíme zlúčený obsah proti aktuálnej serverovej revízii.
    void handleSave(rev, merged);
  };

  const cancelConflict = (): void => {
    setConflict(null);
    toast.info('Riešenie konfliktu zrušené. Vaše zmeny ostali neuložené.');
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  const autoSaveLabel =
    autoSave.status === 'saving'
      ? 'Ukladám koncept…'
      : autoSave.status === 'saved'
        ? 'Koncept uložený'
        : autoSave.status === 'error'
          ? 'Koncept sa nepodarilo uložiť'
          : '';

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
          {isNew ? `Vytvoriť ${type}` : `Upraviť ${type}`}
        </h1>
        <div className="flex items-center gap-3">
          {!isNew && <LockIndicator resourceId={resourceId} onLockChange={setCanEdit} />}
          {autoSaveLabel && <span className="text-xs text-gray-500 dark:text-gray-400">{autoSaveLabel}</span>}
          <button onClick={() => void handleSave()} disabled={saving || !canEdit} className="btn btn-primary">
            {saving ? 'Ukladám…' : 'Uložiť'}
          </button>
        </div>
      </div>

      {/* Banner: neuložený koncept */}
      {pendingDraftAt && (
        <div className="flex items-center justify-between rounded-md bg-blue-50 dark:bg-blue-900/30 px-4 py-3 text-sm text-blue-800 dark:text-blue-200">
          <span>Našiel sa neuložený koncept. Chcete ho obnoviť?</span>
          <span className="flex gap-2">
            <button onClick={() => void restoreDraft()} className="rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700">
              Obnoviť
            </button>
            <button onClick={() => void dismissDraft()} className="rounded px-3 py-1 hover:bg-blue-100 dark:hover:bg-blue-800">
              Zahodiť
            </button>
          </span>
        </div>
      )}

      {/* Modal: 3-way merge riešenie konfliktu */}
      {conflict && (
        <ConflictResolver
          base={conflict.base}
          mine={conflict.mine}
          theirs={conflict.theirs}
          onResolve={resolveConflict}
          onCancel={cancelConflict}
        />
      )}

      <div className="card">
        <div className="card-body space-y-4">
          <div className="form-group">
            <label className="form-label">Titulok</label>
            <input
              type="text"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              disabled={!canEdit}
              className="form-input"
              placeholder="Zadajte titulok…"
            />
          </div>

          <div className="form-group">
            <label className="form-label">Stav</label>
            <select
              value={status}
              onChange={(e) => setStatus(e.target.value as any)}
              disabled={!canEdit}
              className="form-input"
            >
              <option value="draft">Koncept</option>
              <option value="published">Publikované</option>
              <option value="archived">Archivované</option>
            </select>
          </div>

          <div className="form-group">
            <div className="flex items-center justify-between mb-2">
              <label className="form-label mb-0">Obsah</label>
              <div className="flex gap-2">
                <button
                  type="button"
                  className={`btn text-xs px-3 py-1 ${editorMode === 'markdown' ? 'btn-primary' : 'btn-secondary'}`}
                  disabled={!canEdit}
                  onClick={() => setEditorMode('markdown')}
                >
                  Markdown
                </button>
                <button
                  type="button"
                  className={`btn text-xs px-3 py-1 ${editorMode === 'wysiwyg' ? 'btn-primary' : 'btn-secondary'}`}
                  disabled={!canEdit}
                  onClick={() => setEditorMode('wysiwyg')}
                >
                  WYSIWYG
                </button>
              </div>
            </div>
            {editorMode === 'wysiwyg' ? (
              <WysiwygEditor
                value={content}
                onChange={setContent}
                readOnly={!canEdit}
                onPickMedia={() => setMediaPickerOpen(true)}
              />
            ) : (
              <textarea
                value={content}
                onChange={(e) => setContent(e.target.value)}
                disabled={!canEdit}
                className="form-input min-h-[400px] font-mono text-sm"
                placeholder="Write content in Markdown…"
              />
            )}
          </div>

          <MediaPickerModal
            open={mediaPickerOpen}
            onClose={() => setMediaPickerOpen(false)}
            onSelect={(url, alt) => {
              if (editorMode === 'wysiwyg') {
                setContent((prev) => `${prev}<p><img src="${url}" alt="${alt.replace(/"/g, '&quot;')}" /></p>`);
              } else {
                setContent((prev) => `${prev}\n\n![${alt}](${url})\n`);
              }
            }}
          />

          <div className="form-group">
            <label className="form-label">Popis zmeny (commit správa)</label>
            <input
              type="text"
              value={commitMessage}
              onChange={(e) => setCommitMessage(e.target.value)}
              disabled={!canEdit}
              className="form-input"
              placeholder="Napr. Aktualizácia úvodného odseku…"
            />
          </div>

          {!isNew && <div className="text-sm text-gray-500 dark:text-gray-400">Slug: /{type}s/{slug}</div>}
        </div>
      </div>

      {!isNew && slug && (
        <div className="card mt-6">
          <div className="card-header">
            <h2 className="text-lg font-bold">História verzií</h2>
          </div>
          <div className="card-body">
            <VersionHistory contentId={slug} onRestore={() => window.location.reload()} />
          </div>
        </div>
      )}
    </div>
  );
};

export default MarkdownEditor;
