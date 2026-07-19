// frontend/src/components/backend/AclManager.tsx
import React, { useEffect, useState } from 'react';
import { Lock, Plus, Save, Trash2 } from 'lucide-react';
import { securityApi, type AclRule } from '../../api/security';
import { useToast } from '../../hooks/useToast';

const emptyRule = (): AclRule => ({
  id: `acl_${Date.now()}`,
  path: 'content/pages/*',
  roles: ['EDITOR'],
  permissions: [],
  enabled: true,
});

export const AclManager: React.FC = () => {
  const [enabled, setEnabled] = useState(false);
  const [rules, setRules] = useState<AclRule[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const toast = useToast();

  useEffect(() => {
    void (async () => {
      setLoading(true);
      try {
        const data = await securityApi.getAcl();
        setEnabled(data.enabled);
        setRules(data.rules.length > 0 ? data.rules : [emptyRule()]);
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const handleSave = async () => {
    setSaving(true);
    try {
      const saved = await securityApi.saveAcl({ enabled, rules });
      if (saved) {
        setRules(saved.rules);
        toast.success('ACL pravidlá uložené');
      } else {
        toast.error('Uloženie zlyhalo');
      }
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <div className="p-6 text-slate-500">Načítavam ACL…</div>;
  }

  return (
    <div className="p-6 space-y-6">
      <div>
        <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
          <Lock className="w-7 h-7 text-indigo-500" />
          Jemnozrnné ACL
        </h1>
        <p className="text-sm text-slate-500 mt-1">Pravidlá podľa cesty k obsahu (glob s *).</p>
      </div>

      <label className="inline-flex items-center gap-2 text-sm font-semibold">
        <input type="checkbox" checked={enabled} onChange={(e) => setEnabled(e.target.checked)} />
        Povoliť path ACL
      </label>

      <div className="space-y-4">
        {rules.map((rule, index) => (
          <div key={rule.id} className="rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
              <label className="block text-xs font-bold uppercase text-slate-500">
                Cesta
                <input
                  className="mt-1 w-full rounded-xl border px-3 py-2 text-sm dark:bg-slate-900"
                  value={rule.path}
                  onChange={(e) => {
                    const next = [...rules];
                    next[index] = { ...rule, path: e.target.value };
                    setRules(next);
                  }}
                />
              </label>
              <label className="block text-xs font-bold uppercase text-slate-500">
                Role (čiarkou)
                <input
                  className="mt-1 w-full rounded-xl border px-3 py-2 text-sm dark:bg-slate-900"
                  value={rule.roles.join(', ')}
                  onChange={(e) => {
                    const next = [...rules];
                    next[index] = {
                      ...rule,
                      roles: e.target.value.split(',').map((r) => r.trim()).filter(Boolean),
                    };
                    setRules(next);
                  }}
                />
              </label>
            </div>
            <div className="flex justify-between items-center">
              <label className="inline-flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={rule.enabled}
                  onChange={(e) => {
                    const next = [...rules];
                    next[index] = { ...rule, enabled: e.target.checked };
                    setRules(next);
                  }}
                />
                Aktívne
              </label>
              <button
                type="button"
                onClick={() => setRules(rules.filter((_, i) => i !== index))}
                className="text-rose-500 text-sm inline-flex items-center gap-1"
              >
                <Trash2 className="w-4 h-4" />
                Odstrániť
              </button>
            </div>
          </div>
        ))}
      </div>

      <div className="flex gap-3">
        <button
          type="button"
          onClick={() => setRules([...rules, emptyRule()])}
          className="inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-bold"
        >
          <Plus className="w-4 h-4" />
          Pridať pravidlo
        </button>
        <button
          type="button"
          disabled={saving}
          onClick={() => void handleSave()}
          className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold disabled:opacity-60"
        >
          <Save className="w-4 h-4" />
          Uložiť
        </button>
      </div>
    </div>
  );
};

export default AclManager;
