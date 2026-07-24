// frontend/src/components/backend/NavigationManager.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Navigation, Plus, Trash2, ArrowUp, ArrowDown, Save, CornerDownRight } from 'lucide-react';
import { getNavigation, NavigationItem, updateNavigation } from '../../api/navigation';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { MediaPickerModal } from './MediaPickerModal';
import { NavigationItemRichFields } from './NavigationItemRichFields';
import {
  NAVIGATION_MAX_DEPTH,
  buildNavigationTree,
  collectDescendantIds,
  flattenNavigationTree,
  getNavigationDepth,
  normalizeNavigationOrders,
  reorderSibling,
} from '../../utils/navigationTree';

const createItem = (label: string, path: string, parentId: string | null, order: number): NavigationItem => ({
  id: `nav_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`,
  label,
  path,
  order,
  target: '_self',
  parentId,
  description: '',
  iconType: 'none',
  iconValue: null,
  previewOnHover: false,
  previewScale: 1.5,
  thumbnailSize: 'sm',
});

export const NavigationManager: React.FC = () => {
  const { error: showError, success: showSuccess } = useToast();
  const { t } = useI18n();
  const [items, setItems] = useState<NavigationItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [newLabel, setNewLabel] = useState('');
  const [newPath, setNewPath] = useState('');
  const [mediaPickerItemId, setMediaPickerItemId] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const nav = await getNavigation();
      setItems(normalizeNavigationOrders(nav));
    } catch {
      showError(t('navigation.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [showError, t]);

  useEffect(() => {
    void load();
  }, [load]);

  const tree = useMemo(() => buildNavigationTree(items), [items]);
  const flatTree = useMemo(() => flattenNavigationTree(tree), [tree]);

  const updateItem = (id: string, patch: Partial<NavigationItem>) => {
    setItems((prev) => prev.map((item) => (item.id === id ? { ...item, ...patch } : item)));
  };

  const removeItem = (id: string) => {
    const descendants = collectDescendantIds(items, id);
    const removeIds = new Set([id, ...descendants]);
    setItems((prev) => normalizeNavigationOrders(prev.filter((item) => !removeIds.has(item.id))));
  };

  const addRootItem = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newLabel.trim() || !newPath.trim()) {
      return;
    }
    const path = newPath.startsWith('/') || newPath.startsWith('http') ? newPath : `/${newPath}`;
    setItems((prev) =>
      normalizeNavigationOrders([
        ...prev,
        createItem(newLabel.trim(), path, null, prev.length),
      ])
    );
    setNewLabel('');
    setNewPath('');
  };

  const addChildItem = (parentId: string) => {
    const depth = getNavigationDepth(items, parentId);
    if (depth >= NAVIGATION_MAX_DEPTH) {
      showError(t('navigation.toast.maxDepth', { depth: String(NAVIGATION_MAX_DEPTH) }));
      return;
    }

    setItems((prev) =>
      normalizeNavigationOrders([
        ...prev,
        createItem(t('navigation.defaults.newItemLabel'), '/', parentId, prev.length),
      ])
    );
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      const payload = normalizeNavigationOrders(items);
      const saved = await updateNavigation(payload);
      setItems(normalizeNavigationOrders(saved));
      showSuccess(t('navigation.toast.saved'));
    } catch {
      showError(t('navigation.toast.saveFailed'));
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center py-16">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
      </div>
    );
  }

  return (
    <div className="space-y-6 w-full max-w-none">
      <div className="flex justify-between items-center flex-wrap gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Navigation className="w-6 h-6 text-indigo-500" />
            {t('navigation.page.title')}
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            {t('navigation.page.subtitle', { depth: String(NAVIGATION_MAX_DEPTH) })}
          </p>
        </div>
        <button type="button" className="btn btn-primary" disabled={saving} onClick={() => void handleSave()}>
          <Save className="w-4 h-4 inline mr-2" />
          {saving ? t('navigation.actions.saving') : t('navigation.actions.save')}
        </button>
      </div>

      <div className="card">
        <div className="card-body space-y-3">
          {flatTree.length === 0 ? (
            <p className="text-sm text-gray-500 text-center py-8">{t('navigation.empty')}</p>
          ) : (
            flatTree.map((node) => (
              <div
                key={node.id}
                className="flex flex-col gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700"
                style={{ marginLeft: `${(node.depth - 1) * 1.25}rem` }}
              >
                <div className="flex flex-col lg:flex-row lg:items-center gap-3">
                <div className="flex items-center gap-2 shrink-0 text-xs text-gray-400 min-w-[72px]">
                  <CornerDownRight className="w-3 h-3" />
                  {t('navigation.level', { depth: String(node.depth) })}
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 flex-1 min-w-0">
                  <input
                    className="form-input"
                    value={node.label}
                    onChange={(e) => updateItem(node.id, { label: e.target.value })}
                    placeholder={t('navigation.fields.labelPlaceholder')}
                    aria-label={t('navigation.fields.label')}
                  />
                  <input
                    className="form-input font-mono text-sm"
                    value={node.path}
                    onChange={(e) => updateItem(node.id, { path: e.target.value })}
                    placeholder={t('navigation.fields.pathPlaceholder')}
                    aria-label={t('navigation.fields.path')}
                  />
                </div>

                <div className="flex flex-wrap gap-2 shrink-0">
                  {node.depth < NAVIGATION_MAX_DEPTH ? (
                    <button
                      type="button"
                      className="btn btn-secondary text-xs px-2 py-1"
                      onClick={() => addChildItem(node.id)}
                    >
                      <Plus className="w-3 h-3 inline mr-1" />
                      {t('navigation.actions.submenu')}
                    </button>
                  ) : null}
                  <button
                    type="button"
                    className="btn btn-secondary text-xs px-2 py-1"
                    onClick={() => setItems((prev) => reorderSibling(prev, node.id, 'up'))}
                  >
                    <ArrowUp className="w-3 h-3" />
                  </button>
                  <button
                    type="button"
                    className="btn btn-secondary text-xs px-2 py-1"
                    onClick={() => setItems((prev) => reorderSibling(prev, node.id, 'down'))}
                  >
                    <ArrowDown className="w-3 h-3" />
                  </button>
                  <button
                    type="button"
                    className="btn btn-danger text-xs px-2 py-1"
                    onClick={() => removeItem(node.id)}
                  >
                    <Trash2 className="w-3 h-3" />
                  </button>
                </div>
                </div>

                <NavigationItemRichFields
                  item={node}
                  onChange={(patch) => updateItem(node.id, patch)}
                  onPickMedia={() => setMediaPickerItemId(node.id)}
                />
              </div>
            ))
          )}
        </div>
      </div>

      <form onSubmit={addRootItem} className="card">
        <div className="card-body flex flex-wrap gap-3 items-end">
          <div className="flex-1 min-w-[140px]">
            <label className="form-label">{t('navigation.actions.newRootLabel')}</label>
            <input className="form-input" value={newLabel} onChange={(e) => setNewLabel(e.target.value)} />
          </div>
          <div className="flex-1 min-w-[140px]">
            <label className="form-label">{t('navigation.fields.path')}</label>
            <input
              className="form-input"
              value={newPath}
              onChange={(e) => setNewPath(e.target.value)}
              placeholder={t('navigation.fields.pathPlaceholderNew')}
            />
          </div>
          <button type="submit" className="btn btn-secondary">
            <Plus className="w-4 h-4 inline mr-1" />
            {t('navigation.actions.add')}
          </button>
        </div>
      </form>

      <MediaPickerModal
        open={mediaPickerItemId !== null}
        onClose={() => setMediaPickerItemId(null)}
        urlFormat="storage"
        title={t('navigation.mediaPickerTitle')}
        onSelect={(url) => {
          if (mediaPickerItemId) {
            updateItem(mediaPickerItemId, { iconType: 'media', iconValue: url });
          }
          setMediaPickerItemId(null);
        }}
      />
    </div>
  );
};

export default NavigationManager;
