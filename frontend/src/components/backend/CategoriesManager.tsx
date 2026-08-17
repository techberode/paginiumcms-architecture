import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { Hash, Plus, RefreshCw, Trash2 } from 'lucide-react';
import { categoriesApi, type ContentCategory } from '../../api/categories';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { slugifyTitle } from '../../utils/contentEditorMeta';

function isValidCategorySlug(slug: string): boolean {
  return /^[a-z0-9]+(?:-[a-z0-9]+){0,19}$/.test(slug);
}

export const CategoriesManager: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [loading, setLoading] = useState(true);
  const [categories, setCategories] = useState<ContentCategory[]>([]);
  const [showCreate, setShowCreate] = useState(false);
  const [slug, setSlug] = useState('');
  const [label, setLabel] = useState('');
  const [slugTouched, setSlugTouched] = useState(false);
  const [creating, setCreating] = useState(false);
  const [busySlug, setBusySlug] = useState<string | null>(null);
  const [editingLabels, setEditingLabels] = useState<Record<string, string>>({});

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const items = await categoriesApi.listAdmin();
      setCategories(items);
      setEditingLabels(Object.fromEntries(items.map((item) => [item.slug, item.label])));
    } catch {
      toast.error(t('platform.categories.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  useEffect(() => {
    void load();
  }, [load]);

  const suggestedSlug = useMemo(() => slugifyTitle(label), [label]);

  const handleCreate = async () => {
    const normalizedSlug = (slugTouched ? slug : suggestedSlug).trim().toLowerCase();
    const normalizedLabel = label.trim();

    if (normalizedLabel === '') {
      toast.error(t('platform.categories.toast.labelRequired'));
      return;
    }

    if (!isValidCategorySlug(normalizedSlug)) {
      toast.error(t('platform.categories.toast.slugInvalid'));
      return;
    }

    setCreating(true);
    try {
      const created = await categoriesApi.save(normalizedSlug, normalizedLabel);
      if (!created) {
        toast.error(t('platform.categories.toast.createFailed'));
        return;
      }

      toast.success(t('platform.categories.toast.created'));
      setShowCreate(false);
      setSlug('');
      setLabel('');
      setSlugTouched(false);
      await load();
    } finally {
      setCreating(false);
    }
  };

  const handleSaveLabel = async (category: ContentCategory) => {
    const nextLabel = (editingLabels[category.slug] ?? category.label).trim();
    if (nextLabel === '') {
      toast.error(t('platform.categories.toast.labelRequired'));
      return;
    }

    if (nextLabel === category.label) {
      return;
    }

    setBusySlug(category.slug);
    try {
      const updated = await categoriesApi.update(category.slug, nextLabel);
      if (!updated) {
        toast.error(t('platform.categories.toast.updateFailed'));
        return;
      }
      toast.success(t('platform.categories.toast.updated'));
      await load();
    } finally {
      setBusySlug(null);
    }
  };

  const handleDelete = async (category: ContentCategory) => {
    if (!window.confirm(t('platform.categories.confirmDelete', { label: category.label }))) {
      return;
    }

    setBusySlug(category.slug);
    try {
      const ok = await categoriesApi.remove(category.slug);
      if (!ok) {
        toast.error(t('platform.categories.toast.deleteFailed'));
        return;
      }
      toast.success(t('platform.categories.toast.deleted'));
      await load();
    } finally {
      setBusySlug(null);
    }
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <Hash className="w-7 h-7 text-indigo-600" />
            {t('platform.categories.title')}
          </h1>
          <p className="text-sm text-slate-600 dark:text-slate-300 mt-1">{t('platform.categories.subtitle')}</p>
        </div>
        <div className="flex gap-2">
          <button type="button" className="btn btn-secondary" onClick={() => void load()} disabled={loading}>
            <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
            {t('platform.categories.refresh')}
          </button>
          <button type="button" className="btn btn-primary" onClick={() => setShowCreate((open) => !open)}>
            <Plus className="w-4 h-4" />
            {t('platform.categories.create')}
          </button>
        </div>
      </div>

      <p className="text-sm text-slate-600 dark:text-slate-300">
        {t('platform.categories.hint')}{' '}
        <Link to="/articles" className="font-semibold text-indigo-600 hover:underline">
          {t('platform.categories.articlesLink')}
        </Link>
      </p>

      {showCreate ? (
        <div className="rounded-xl border border-slate-200 bg-white p-4 space-y-4 dark:border-slate-800 dark:bg-slate-950">
          <h2 className="font-bold text-slate-900 dark:text-white">{t('platform.categories.createTitle')}</h2>
          <div className="grid gap-4 md:grid-cols-2">
            <label className="space-y-1 text-sm">
              <span className="font-semibold text-slate-700 dark:text-slate-200">{t('platform.categories.label')}</span>
              <input
                type="text"
                className="form-input w-full"
                value={label}
                onChange={(event) => setLabel(event.target.value)}
                placeholder={t('platform.categories.labelPlaceholder')}
              />
            </label>
            <label className="space-y-1 text-sm">
              <span className="font-semibold text-slate-700 dark:text-slate-200">{t('platform.categories.slug')}</span>
              <input
                type="text"
                className="form-input w-full font-mono text-sm"
                value={slugTouched ? slug : suggestedSlug}
                onChange={(event) => {
                  setSlugTouched(true);
                  setSlug(event.target.value);
                }}
                placeholder={t('platform.categories.slugPlaceholder')}
              />
            </label>
          </div>
          <div className="flex flex-wrap gap-2">
            <button type="button" className="btn btn-primary" disabled={creating} onClick={() => void handleCreate()}>
              {creating ? t('platform.categories.saving') : t('platform.categories.save')}
            </button>
            <button
              type="button"
              className="btn btn-secondary"
              onClick={() => {
                setShowCreate(false);
                setSlug('');
                setLabel('');
                setSlugTouched(false);
              }}
            >
              {t('platform.categories.cancel')}
            </button>
          </div>
        </div>
      ) : null}

      <div className="rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950 overflow-hidden">
        {loading ? (
          <p className="px-4 py-6 text-sm text-slate-500">{t('platform.categories.loading')}</p>
        ) : categories.length === 0 ? (
          <p className="px-4 py-6 text-sm text-slate-500">{t('platform.categories.empty')}</p>
        ) : (
          <table className="min-w-full text-sm">
            <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900">
              <tr>
                <th className="px-4 py-3">{t('platform.categories.table.label')}</th>
                <th className="px-4 py-3">{t('platform.categories.table.slug')}</th>
                <th className="px-4 py-3 text-right">{t('platform.categories.table.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {categories.map((category) => (
                <tr key={category.slug} className="border-t border-slate-100 dark:border-slate-800">
                  <td className="px-4 py-3">
                    <input
                      type="text"
                      className="form-input w-full max-w-md"
                      value={editingLabels[category.slug] ?? category.label}
                      disabled={busySlug === category.slug}
                      onChange={(event) =>
                        setEditingLabels((prev) => ({ ...prev, [category.slug]: event.target.value }))
                      }
                      onBlur={() => void handleSaveLabel(category)}
                    />
                  </td>
                  <td className="px-4 py-3 font-mono text-xs text-slate-500">{category.slug}</td>
                  <td className="px-4 py-3">
                    <div className="flex justify-end gap-2">
                      <button
                        type="button"
                        className="btn btn-secondary text-xs"
                        disabled={busySlug === category.slug}
                        onClick={() => void handleSaveLabel(category)}
                      >
                        {t('platform.categories.save')}
                      </button>
                      <button
                        type="button"
                        className="btn btn-danger text-xs"
                        disabled={busySlug === category.slug}
                        onClick={() => void handleDelete(category)}
                        aria-label={t('platform.categories.delete')}
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
};

export default CategoriesManager;
