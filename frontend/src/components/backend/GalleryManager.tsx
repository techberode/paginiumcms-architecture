import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ArrowDown,
  ArrowUp,
  Download,
  ExternalLink,
  Eye,
  EyeOff,
  LayoutGrid,
  Plus,
  Settings,
  Trash2,
  Upload,
} from 'lucide-react';
import { Link } from 'react-router-dom';
import {
  bulkDeleteGalleryItems,
  createGalleryItem,
  deleteGalleryItem,
  exportGalleryJson,
  GalleryItem,
  GalleryItemStatus,
  importGalleryJson,
  listAdminGalleryItems,
  reorderGalleryItems,
  updateGalleryItem,
} from '../../api/gallery';
import { resolvePublicMediaUrl } from '../../api/media';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useI18n } from '../../context/I18nContext';
import { BulkActionBar } from './BulkActionBar';
import { summarizeBulkResult } from '../../types/bulk';
import { useSettingsContext } from '../../context/SettingsContext';
import { FeatureGallerySection } from '../frontend/FeatureGallerySection';
import { normalizeGalleryPublicPath } from '../../utils/galleryPublicRoute';
import { MediaPickerModal } from './MediaPickerModal';

type FormState = {
  title: string;
  description: string;
  mediaPath: string;
  featureTag: string;
  linkUrl: string;
  status: GalleryItemStatus;
};

const emptyForm = (): FormState => ({
  title: '',
  description: '',
  mediaPath: '',
  featureTag: '',
  linkUrl: '',
  status: 'draft',
});

export const GalleryManager: React.FC = () => {
  const { t } = useI18n();
  const { settings } = useSettingsContext();
  const { error: showError, success: showSuccess } = useToast();
  const [items, setItems] = useState<GalleryItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [mediaPickerOpen, setMediaPickerOpen] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [form, setForm] = useState<FormState>(emptyForm());
  const [formOpen, setFormOpen] = useState(false);
  const [previewOpen, setPreviewOpen] = useState(true);
  const [importing, setImporting] = useState(false);
  const importInputRef = useRef<HTMLInputElement>(null);

  const bulkSelection = useBulkSelection(
    items.map((item) => item.id),
    String(items.length)
  );

  const publishedItems = useMemo(
    () => items.filter((item) => item.status === 'published'),
    [items]
  );
  const publicRoute = normalizeGalleryPublicPath(settings.gallery?.publicRoute);
  const layoutLabel = settings.gallery?.layout ?? 'grid';
  const effectLabel = settings.gallery?.effectPreset ?? 'subtle';

  const load = useCallback(async () => {
    setLoading(true);
    try {
      setItems(await listAdminGalleryItems());
    } catch {
      showError(t('gallery.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [showError, t]);

  useEffect(() => {
    void load();
  }, [load]);

  const handleExport = async () => {
    const result = await exportGalleryJson();
    if (!result.ok) {
      showError(result.error);
      return;
    }
    const url = URL.createObjectURL(result.blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = `gallery-export-${new Date().toISOString().slice(0, 10)}.json`;
    anchor.click();
    URL.revokeObjectURL(url);
    showSuccess(t('gallery.toast.exported'));
  };

  const handleImportFile = async (file: File | null) => {
    if (!file) {
      return;
    }
    if (!confirm(t('gallery.confirm.importReplace'))) {
      return;
    }
    setImporting(true);
    try {
      const text = await file.text();
      const parsed = JSON.parse(text) as { items?: unknown };
      if (!Array.isArray(parsed.items)) {
        showError(t('gallery.toast.importFailed'));
        return;
      }
      const result = await importGalleryJson({
        items: parsed.items as Array<GalleryItem>,
        replace: true,
      });
      if (!result.ok) {
        showError(result.error);
        return;
      }
      showSuccess(t('gallery.toast.imported', { count: String(result.result.imported) }));
      void load();
    } catch {
      showError(t('gallery.toast.importFailed'));
    } finally {
      setImporting(false);
      if (importInputRef.current) {
        importInputRef.current.value = '';
      }
    }
  };
  const openCreate = () => {
    setEditingId(null);
    setForm(emptyForm());
    setFormOpen(true);
  };

  const openEdit = (item: GalleryItem) => {
    setEditingId(item.id);
    setForm({
      title: item.title,
      description: item.description,
      mediaPath: item.mediaPath,
      featureTag: item.featureTag ?? '',
      linkUrl: item.linkUrl ?? '',
      status: item.status,
    });
    setFormOpen(true);
  };

  const closeForm = () => {
    setFormOpen(false);
    setEditingId(null);
    setForm(emptyForm());
  };

  const handleSave = async () => {
    if (!form.title.trim() || !form.mediaPath.trim()) {
      showError(t('gallery.toast.saveFailed'));
      return;
    }

    setSaving(true);
    const payload = {
      title: form.title.trim(),
      description: form.description.trim(),
      mediaPath: form.mediaPath.trim(),
      featureTag: form.featureTag.trim(),
      linkUrl: form.linkUrl.trim(),
      status: form.status,
    };

    const result = editingId
      ? await updateGalleryItem(editingId, payload)
      : await createGalleryItem(payload);

    setSaving(false);

    if (!result.ok) {
      showError(result.error);
      return;
    }

    showSuccess(editingId ? t('gallery.toast.updated') : t('gallery.toast.created'));
    closeForm();
    void load();
  };

  const handleBulkDelete = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(t('gallery.confirm.bulkDelete', { count: String(bulkSelection.count) }))) {
      return;
    }
    const result = await bulkDeleteGalleryItems(bulkSelection.selectedIds);
    if (!result) {
      showError(t('gallery.toast.bulkFailed'));
      return;
    }
    showSuccess(summarizeBulkResult(result, t));
    bulkSelection.clear();
    void load();
  };

  const handleDelete = async (id: string) => {
    if (!confirm(t('gallery.confirm.delete'))) {
      return;
    }
    const result = await deleteGalleryItem(id);
    if (!result.ok) {
      showError(result.error);
      return;
    }
    showSuccess(t('gallery.toast.deleted'));
    void load();
  };

  const moveItem = async (index: number, direction: -1 | 1) => {
    const target = index + direction;
    if (target < 0 || target >= items.length) {
      return;
    }
    const next = [...items];
    const [moved] = next.splice(index, 1);
    next.splice(target, 0, moved);
    setItems(next);
    const result = await reorderGalleryItems(next.map((item) => item.id));
    if (!result.ok) {
      showError(result.error);
      void load();
      return;
    }
    showSuccess(t('gallery.toast.reordered'));
  };

  const togglePublish = async (item: GalleryItem) => {
    const nextStatus: GalleryItemStatus = item.status === 'published' ? 'draft' : 'published';
    const result = await updateGalleryItem(item.id, { status: nextStatus });
    if (!result.ok) {
      showError(result.error);
      return;
    }
    showSuccess(t('gallery.toast.updated'));
    void load();
  };

  return (
    <div className="max-w-6xl mx-auto p-4 sm:p-6 space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <LayoutGrid className="w-7 h-7 text-indigo-500" />
            {t('gallery.title')}
          </h1>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{t('gallery.subtitle')}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <input
            ref={importInputRef}
            type="file"
            accept="application/json,.json"
            className="hidden"
            onChange={(e) => void handleImportFile(e.target.files?.[0] ?? null)}
          />
          <button
            type="button"
            className="btn btn-secondary inline-flex items-center gap-2"
            onClick={() => void handleExport()}
          >
            <Download className="w-4 h-4" />
            {t('gallery.actions.export')}
          </button>
          <button
            type="button"
            className="btn btn-secondary inline-flex items-center gap-2"
            disabled={importing}
            onClick={() => importInputRef.current?.click()}
          >
            <Upload className="w-4 h-4" />
            {t('gallery.actions.import')}
          </button>
          <button
            type="button"
            className="btn btn-secondary inline-flex items-center gap-2"
            onClick={() => setPreviewOpen((open) => !open)}
          >
            {previewOpen ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
            {previewOpen ? t('gallery.preview.hide') : t('gallery.preview.show')}
          </button>
          <Link
            to="/settings?category=site&group=gallery"
            className="btn btn-secondary inline-flex items-center gap-2"
          >
            <Settings className="w-4 h-4" />
            {t('gallery.settingsLink')}
          </Link>
          <button type="button" className="btn btn-primary inline-flex items-center gap-2" onClick={openCreate}>
            <Plus className="w-4 h-4" />
            {t('gallery.addItem')}
          </button>
        </div>
      </div>

      {previewOpen ? (
        <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 overflow-hidden">
          <div className="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <span>
              {t('gallery.preview.meta', {
                layout: layoutLabel,
                effect: effectLabel,
                route: publicRoute,
              })}
            </span>
            <Link to={publicRoute} target="_blank" rel="noopener noreferrer" className="underline underline-offset-2">
              {t('gallery.preview.openPublic')}
            </Link>
          </div>
          {publishedItems.length === 0 ? (
            <p className="p-6 text-sm text-slate-500 dark:text-slate-400">{t('gallery.preview.empty')}</p>
          ) : (
            <div className="bg-theme-surface text-theme-text">
              <FeatureGallerySection variant="preview" previewItems={publishedItems} />
            </div>
          )}
        </div>
      ) : null}

      {loading ? (
        <div className="flex justify-center py-16">
          <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600" />
        </div>
      ) : items.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 p-10 text-center text-slate-500">
          {t('gallery.empty')}
        </div>
      ) : (
        <>
          <BulkActionBar
            count={bulkSelection.count}
            onClear={bulkSelection.clear}
            actions={[
              {
                id: 'delete',
                label: t('gallery.actions.delete'),
                variant: 'danger',
                onClick: () => void handleBulkDelete(),
              },
            ]}
          />
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {items.map((item, index) => (
            <article
              key={item.id}
              className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm"
            >
              <div className="relative">
                <label className="absolute top-3 left-3 z-10">
                  <input
                    type="checkbox"
                    className="rounded border-slate-300 dark:border-slate-600"
                    checked={bulkSelection.isSelected(item.id)}
                    onChange={() => bulkSelection.toggle(item.id)}
                    aria-label={item.title}
                  />
                </label>
                <button
                  type="button"
                  className="block w-full aspect-video bg-slate-100 dark:bg-slate-800 overflow-hidden"
                  onClick={() => openEdit(item)}
                >
                  <img
                    src={resolvePublicMediaUrl(item.mediaPath)}
                    alt={item.title}
                    className="h-full w-full object-cover object-top"
                  />
                </button>
              </div>
              <div className="p-4 space-y-3">
                <div className="flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <h2 className="font-bold text-slate-900 dark:text-white truncate">{item.title}</h2>
                    {item.featureTag ? (
                      <span className="inline-flex mt-1 text-[10px] uppercase tracking-wide font-bold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                        {item.featureTag}
                      </span>
                    ) : null}
                  </div>
                  <span
                    className={`shrink-0 text-[10px] font-bold uppercase px-2 py-1 rounded-full ${
                      item.status === 'published'
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                        : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
                    }`}
                  >
                    {t(`gallery.status.${item.status}`)}
                  </span>
                </div>
                {item.description ? (
                  <p className="text-sm text-slate-500 dark:text-slate-400 line-clamp-2">{item.description}</p>
                ) : null}
                <div className="flex flex-wrap items-center gap-2">
                  <button type="button" className="btn btn-secondary text-xs px-3 py-1.5" onClick={() => openEdit(item)}>
                    {t('gallery.editItem')}
                  </button>
                  <button
                    type="button"
                    className="btn btn-secondary text-xs px-3 py-1.5"
                    onClick={() => void togglePublish(item)}
                  >
                    {item.status === 'published' ? t('gallery.actions.unpublish') : t('gallery.actions.publish')}
                  </button>
                  {item.linkUrl ? (
                    <a
                      href={item.linkUrl}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="btn btn-secondary text-xs px-3 py-1.5 inline-flex items-center gap-1"
                    >
                      <ExternalLink className="w-3 h-3" />
                      URL
                    </a>
                  ) : null}
                  <button
                    type="button"
                    className="btn btn-secondary text-xs px-2 py-1.5"
                    onClick={() => void moveItem(index, -1)}
                    disabled={index === 0}
                    aria-label={t('gallery.actions.moveUp')}
                  >
                    <ArrowUp className="w-4 h-4" />
                  </button>
                  <button
                    type="button"
                    className="btn btn-secondary text-xs px-2 py-1.5"
                    onClick={() => void moveItem(index, 1)}
                    disabled={index === items.length - 1}
                    aria-label={t('gallery.actions.moveDown')}
                  >
                    <ArrowDown className="w-4 h-4" />
                  </button>
                  <button
                    type="button"
                    className="btn btn-danger text-xs px-2 py-1.5 ml-auto"
                    onClick={() => void handleDelete(item.id)}
                    aria-label={t('gallery.actions.delete')}
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              </div>
            </article>
          ))}
        </div>
        </>
      )}

      {formOpen ? (
        <div className="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4">
          <div
            className="w-full max-w-lg rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-2xl p-5 space-y-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="gallery-form-title"
          >
            <h2 id="gallery-form-title" className="text-lg font-bold text-slate-900 dark:text-white">
              {editingId ? t('gallery.editItem') : t('gallery.addItem')}
            </h2>

            <label className="block space-y-1">
              <span className="text-xs font-semibold text-slate-600 dark:text-slate-300">{t('gallery.form.title')}</span>
              <input
                className="input w-full"
                value={form.title}
                onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))}
              />
            </label>

            <label className="block space-y-1">
              <span className="text-xs font-semibold text-slate-600 dark:text-slate-300">
                {t('gallery.form.description')}
              </span>
              <textarea
                className="input w-full min-h-[88px]"
                value={form.description}
                onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))}
              />
            </label>

            <div className="space-y-2">
              <span className="text-xs font-semibold text-slate-600 dark:text-slate-300">{t('gallery.form.mediaPath')}</span>
              {form.mediaPath ? (
                <img
                  src={resolvePublicMediaUrl(form.mediaPath)}
                  alt=""
                  className="w-full max-h-40 object-cover rounded-xl border border-slate-200 dark:border-slate-700"
                />
              ) : null}
              <button
                type="button"
                className="btn btn-secondary text-sm"
                onClick={() => setMediaPickerOpen(true)}
              >
                {form.mediaPath ? t('gallery.form.changeMedia') : t('gallery.form.pickMedia')}
              </button>
            </div>

            <label className="block space-y-1">
              <span className="text-xs font-semibold text-slate-600 dark:text-slate-300">{t('gallery.form.featureTag')}</span>
              <input
                className="input w-full"
                value={form.featureTag}
                placeholder="analytics"
                onChange={(e) => setForm((prev) => ({ ...prev, featureTag: e.target.value }))}
              />
            </label>

            <label className="block space-y-1">
              <span className="text-xs font-semibold text-slate-600 dark:text-slate-300">{t('gallery.form.linkUrl')}</span>
              <input
                className="input w-full"
                value={form.linkUrl}
                onChange={(e) => setForm((prev) => ({ ...prev, linkUrl: e.target.value }))}
              />
            </label>

            <label className="block space-y-1">
              <span className="text-xs font-semibold text-slate-600 dark:text-slate-300">{t('gallery.form.status')}</span>
              <select
                className="input w-full"
                value={form.status}
                onChange={(e) => setForm((prev) => ({ ...prev, status: e.target.value as GalleryItemStatus }))}
              >
                <option value="draft">{t('gallery.status.draft')}</option>
                <option value="published">{t('gallery.status.published')}</option>
              </select>
            </label>

            <div className="flex justify-end gap-2 pt-2">
              <button type="button" className="btn btn-secondary" onClick={closeForm} disabled={saving}>
                {t('gallery.actions.cancel')}
              </button>
              <button type="button" className="btn btn-primary" onClick={() => void handleSave()} disabled={saving}>
                {t('gallery.actions.save')}
              </button>
            </div>
          </div>
        </div>
      ) : null}

      <MediaPickerModal
        open={mediaPickerOpen}
        onClose={() => setMediaPickerOpen(false)}
        urlFormat="storage"
        title={t('gallery.form.pickMedia')}
        onSelect={(url) => {
          setForm((prev) => ({ ...prev, mediaPath: url }));
          setMediaPickerOpen(false);
        }}
      />
    </div>
  );
};

export default GalleryManager;
