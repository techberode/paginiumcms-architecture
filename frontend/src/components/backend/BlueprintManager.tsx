// frontend/src/components/backend/BlueprintManager.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Layers, Plus, Save, Trash2 } from 'lucide-react';
import {
  blueprintApi,
  type BlueprintDefinition,
  type BlueprintField,
  type BlueprintSummary,
} from '../../api/blueprint';
import { DynamicForm } from '../blueprint/DynamicForm';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';

export const BlueprintManager: React.FC = () => {
  const { t } = useI18n();
  const [summaries, setSummaries] = useState<BlueprintSummary[]>([]);
  const [activeType, setActiveType] = useState('page');
  const [blueprint, setBlueprint] = useState<BlueprintDefinition | null>(null);
  const [previewValues, setPreviewValues] = useState<Record<string, unknown>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const toast = useToast();

  const emptyField = (): BlueprintField => ({
    key: 'field_key',
    type: 'text',
    label: t('platform.blueprint.newFieldLabel'),
    rules: ['string'],
    options: [],
    help: '',
  });

  const loadList = useCallback(async () => {
    const list = await blueprintApi.list();
    setSummaries(list);
    if (list.length > 0 && !list.some((item) => item.type === activeType)) {
      setActiveType(list[0].type);
    }
  }, [activeType]);

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
  }, [loadList]);

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
        toast.success(t('platform.blueprint.toast.saved'));
        await loadList();
      } else {
        toast.error(t('platform.blueprint.toast.saveFailed'));
      }
    } finally {
      setSaving(false);
    }
  };

  const handleValidatePreview = async () => {
    if (!blueprint) return;
    const result = await blueprintApi.validate(blueprint.type, previewValues);
    if (result?.valid) {
      toast.success(t('platform.blueprint.toast.validationOk'));
    } else {
      toast.error(t('platform.blueprint.toast.validationFailed'));
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
    return <div className="p-8 text-slate-500">{t('platform.blueprint.loading')}</div>;
  }

  return (
    <div className="p-6 space-y-6">
      <div className="flex items-center gap-3">
        <Layers className="text-indigo-500" />
        <div>
          <h1 className="text-2xl font-black">{t('platform.blueprint.title')}</h1>
          <p className="text-sm text-slate-500">{t('platform.blueprint.subtitle')}</p>
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
              <h2 className="font-bold">{t('platform.blueprint.fieldSchema')}</h2>
              <button
                type="button"
                onClick={addField}
                disabled={blueprint.system}
                className="inline-flex items-center gap-1 text-sm font-bold text-indigo-600 disabled:opacity-40"
              >
                <Plus size={16} /> {t('platform.blueprint.addField')}
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
                  placeholder={t('platform.blueprint.rulesPlaceholder')}
                />
                {!blueprint.system && (
                  <button
                    type="button"
                    onClick={() => removeField(index)}
                    className="md:col-span-2 inline-flex items-center gap-1 text-xs text-red-500"
                  >
                    <Trash2 size={14} /> {t('platform.blueprint.removeField')}
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
              <Save size={16} /> {t('platform.blueprint.save')}
            </button>
          </section>

          <section className="rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-4">
            <div className="flex items-center justify-between">
              <h2 className="font-bold">{t('platform.blueprint.formPreview')}</h2>
              <button
                type="button"
                onClick={() => void handleValidatePreview()}
                className="text-sm font-bold text-indigo-600"
              >
                {t('platform.blueprint.testValidation')}
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
