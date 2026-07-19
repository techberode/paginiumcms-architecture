// frontend/src/components/backend/BlueprintManager.tsx
import React, { useEffect, useMemo, useState } from 'react';
import { Layers, Plus, Save, Trash2 } from 'lucide-react';
import {
  blueprintApi,
  type BlueprintDefinition,
  type BlueprintField,
  type BlueprintSummary,
} from '../../api/blueprint';
import { DynamicForm } from '../blueprint/DynamicForm';
import { useToast } from '../../hooks/useToast';

const emptyField = (): BlueprintField => ({
  key: 'field_key',
  type: 'text',
  label: 'Nové pole',
  rules: ['string'],
  options: [],
  help: '',
});

export const BlueprintManager: React.FC = () => {
  const [summaries, setSummaries] = useState<BlueprintSummary[]>([]);
  const [activeType, setActiveType] = useState('page');
  const [blueprint, setBlueprint] = useState<BlueprintDefinition | null>(null);
  const [previewValues, setPreviewValues] = useState<Record<string, unknown>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const toast = useToast();

  const loadList = async () => {
    const list = await blueprintApi.list();
    setSummaries(list);
    if (list.length > 0 && !list.some((item) => item.type === activeType)) {
      setActiveType(list[0].type);
    }
  };

  const loadBlueprint = async (type: string) => {
    setLoading(true);
    try {
      const data = await blueprintApi.get(type);
      if (data) {
        setBlueprint(data);
        const defaults: Record<string, unknown> = {};
        data.fields.forEach((field) => {
          defaults[field.key] = field.default ?? '';
        });
        setPreviewValues(defaults);
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void loadList();
  }, []);

  useEffect(() => {
    if (activeType) {
      void loadBlueprint(activeType);
    }
  }, [activeType]);

  const handleSave = async () => {
    if (!blueprint) return;
    setSaving(true);
    try {
      const saved = await blueprintApi.save(blueprint.type, {
        label: blueprint.label,
        description: blueprint.description,
        fields: blueprint.fields,
      });
      if (saved) {
        setBlueprint(saved);
        toast.success('Blueprint uložený');
        await loadList();
      } else {
        toast.error('Uloženie zlyhalo');
      }
    } finally {
      setSaving(false);
    }
  };

  const handleValidatePreview = async () => {
    if (!blueprint) return;
    const result = await blueprintApi.validate(blueprint.type, previewValues);
    if (result?.valid) {
      toast.success('Ukážkové dáta prešli validáciou');
    } else {
      toast.error('Validácia zlyhala');
    }
  };

  const updateField = (index: number, patch: Partial<BlueprintField>) => {
    if (!blueprint) return;
    const fields = [...blueprint.fields];
    fields[index] = { ...fields[index], ...patch };
    setBlueprint({ ...blueprint, fields });
  };

  const addField = () => {
    if (!blueprint) return;
    setBlueprint({ ...blueprint, fields: [...blueprint.fields, emptyField()] });
  };

  const removeField = (index: number) => {
    if (!blueprint || blueprint.system) return;
    setBlueprint({
      ...blueprint,
      fields: blueprint.fields.filter((_, i) => i !== index),
    });
  };

  const previewFields = useMemo(
    () => blueprint?.fields.filter((field) => field.key !== 'content') ?? [],
    [blueprint]
  );

  if (loading && !blueprint) {
    return <div className="p-8 text-slate-500">Načítavam blueprinty…</div>;
  }

  return (
    <div className="p-6 space-y-6">
      <div className="flex items-center gap-3">
        <Layers className="text-indigo-500" />
        <div>
          <h1 className="text-2xl font-black">Blueprint engine</h1>
          <p className="text-sm text-slate-500">Flat-file definície typov obsahu a polí</p>
        </div>
      </div>

      <div className="flex flex-wrap gap-2">
        {summaries.map((item) => (
          <button
            key={item.type}
            type="button"
            onClick={() => setActiveType(item.type)}
            className={`px-4 py-2 rounded-xl text-sm font-bold border ${
              activeType === item.type
                ? 'bg-indigo-600 text-white border-indigo-600'
                : 'border-slate-200 dark:border-slate-700'
            }`}
          >
            {item.label} ({item.field_count})
          </button>
        ))}
      </div>

      {blueprint && (
        <div className="grid lg:grid-cols-2 gap-6">
          <section className="rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-4">
            <div className="flex items-center justify-between">
              <h2 className="font-bold">Schéma polí</h2>
              <button
                type="button"
                onClick={addField}
                disabled={blueprint.system}
                className="inline-flex items-center gap-1 text-sm font-bold text-indigo-600 disabled:opacity-40"
              >
                <Plus size={16} /> Pole
              </button>
            </div>

            {blueprint.fields.map((field, index) => (
              <div key={`${field.key}-${index}`} className="grid md:grid-cols-2 gap-2 p-3 rounded-xl bg-slate-50 dark:bg-slate-900/40">
                <input
                  className="rounded-lg border px-2 py-1 text-sm"
                  value={field.key}
                  onChange={(e) => updateField(index, { key: e.target.value })}
                />
                <input
                  className="rounded-lg border px-2 py-1 text-sm"
                  value={field.label}
                  onChange={(e) => updateField(index, { label: e.target.value })}
                />
                <select
                  className="rounded-lg border px-2 py-1 text-sm"
                  value={field.type}
                  onChange={(e) => updateField(index, { type: e.target.value })}
                >
                  {['text', 'textarea', 'markdown', 'slug', 'select', 'bool', 'number', 'email', 'url', 'media', 'datetime'].map(
                    (type) => (
                      <option key={type} value={type}>
                        {type}
                      </option>
                    )
                  )}
                </select>
                <input
                  className="rounded-lg border px-2 py-1 text-sm"
                  value={field.rules.join(', ')}
                  onChange={(e) =>
                    updateField(index, {
                      rules: e.target.value.split(',').map((rule) => rule.trim()).filter(Boolean),
                    })
                  }
                  placeholder="required, string, max:255"
                />
                {!blueprint.system && (
                  <button
                    type="button"
                    onClick={() => removeField(index)}
                    className="md:col-span-2 inline-flex items-center gap-1 text-xs text-red-500"
                  >
                    <Trash2 size={14} /> Odstrániť pole
                  </button>
                )}
              </div>
            ))}

            <button
              type="button"
              disabled={saving}
              onClick={() => void handleSave()}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold"
            >
              <Save size={16} /> Uložiť blueprint
            </button>
          </section>

          <section className="rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-4">
            <div className="flex items-center justify-between">
              <h2 className="font-bold">Náhľad formulára</h2>
              <button
                type="button"
                onClick={() => void handleValidatePreview()}
                className="text-sm font-bold text-indigo-600"
              >
                Otestovať validáciu
              </button>
            </div>
            <DynamicForm
              fields={previewFields}
              values={previewValues}
              onChange={(key, value) => setPreviewValues((prev) => ({ ...prev, [key]: value }))}
            />
          </section>
        </div>
      )}
    </div>
  );
};

export default BlueprintManager;
