// frontend/src/components/backend/NavigationManager.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { Navigation, Plus, Trash2, ArrowUp, ArrowDown, Save } from 'lucide-react';
import { getNavigation, NavigationItem, updateNavigation } from '../../api/navigation';
import { useToast } from '../../hooks/useToast';

export const NavigationManager: React.FC = () => {
  const { error: showError, success: showSuccess } = useToast();
  const [items, setItems] = useState<NavigationItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [newLabel, setNewLabel] = useState('');
  const [newPath, setNewPath] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const nav = await getNavigation();
      setItems([...nav].sort((a, b) => a.order - b.order));
    } catch {
      showError('Failed to load navigation.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const handleSave = async () => {
    setSaving(true);
    try {
      const saved = await updateNavigation(items.map((item, index) => ({ ...item, order: index })));
      setItems(saved);
      showSuccess('Navigation saved.');
    } catch {
      showError('Failed to save navigation.');
    } finally {
      setSaving(false);
    }
  };

  const addItem = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newLabel.trim() || !newPath.trim()) return;
    const path = newPath.startsWith('/') || newPath.startsWith('http') ? newPath : `/${newPath}`;
    setItems((prev) => [
      ...prev,
      { id: `nav_${Date.now()}`, label: newLabel.trim(), path, order: prev.length, target: '_self' },
    ]);
    setNewLabel('');
    setNewPath('');
  };

  const move = (index: number, direction: 'up' | 'down') => {
    const target = direction === 'up' ? index - 1 : index + 1;
    if (target < 0 || target >= items.length) return;
    const copy = [...items];
    [copy[index], copy[target]] = [copy[target], copy[index]];
    setItems(copy);
  };

  if (loading) {
    return (
      <div className="flex justify-center py-16">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center flex-wrap gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Navigation className="w-6 h-6 text-indigo-500" />
            Navigation
          </h1>
          <p className="text-sm text-gray-500 mt-1">Stored in <code>data/navigation.json</code></p>
        </div>
        <button type="button" className="btn btn-primary" disabled={saving} onClick={() => void handleSave()}>
          <Save className="w-4 h-4 inline mr-2" />
          {saving ? 'Saving…' : 'Save menu'}
        </button>
      </div>

      <div className="card">
        <div className="card-body space-y-3">
          {items.map((item, index) => (
            <div key={item.id} className="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50">
              <span className="text-xs font-bold w-6 text-center">{index + 1}</span>
              <div className="flex-1 min-w-0">
                <p className="font-medium truncate">{item.label}</p>
                <p className="text-xs text-gray-500 truncate">{item.path}</p>
              </div>
              <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={() => move(index, 'up')}>
                <ArrowUp className="w-3 h-3" />
              </button>
              <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={() => move(index, 'down')}>
                <ArrowDown className="w-3 h-3" />
              </button>
              <button
                type="button"
                className="btn btn-danger text-xs px-2 py-1"
                onClick={() => setItems((prev) => prev.filter((i) => i.id !== item.id))}
              >
                <Trash2 className="w-3 h-3" />
              </button>
            </div>
          ))}
        </div>
      </div>

      <form onSubmit={addItem} className="card">
        <div className="card-body flex flex-wrap gap-3 items-end">
          <div className="flex-1 min-w-[140px]">
            <label className="form-label">Label</label>
            <input className="form-input" value={newLabel} onChange={(e) => setNewLabel(e.target.value)} />
          </div>
          <div className="flex-1 min-w-[140px]">
            <label className="form-label">Path</label>
            <input className="form-input" value={newPath} onChange={(e) => setNewPath(e.target.value)} placeholder="/about" />
          </div>
          <button type="submit" className="btn btn-secondary">
            <Plus className="w-4 h-4 inline mr-1" />
            Add
          </button>
        </div>
      </form>
    </div>
  );
};

export default NavigationManager;
