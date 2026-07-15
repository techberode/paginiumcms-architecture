// frontend/src/components/backend/SettingsView.tsx
// === Nastavenia CMS (Iterácia 4) ===
// Generický formulár riadený schémou z backendu (/api/admin/settings).
// Pridanie novej skupiny/poľa na backende sa tu prejaví automaticky – žiadna
// zmena tohto komponentu nie je potrebná. Validácia beží na dvoch úrovniach:
// okamžite na FE (zdieľané pravidlá) a autoritatívne na BE (422 → res.errors).
import React, { useEffect, useMemo, useState } from 'react';
import {
  getSettings,
  updateSettingsGroup,
  rulesFromSchema,
  SettingsSchema,
  SettingsValues,
  SettingField,
} from '../../api/settings';
import { validate, ValidationErrors } from '../../utils/validation';
import { useToast } from '../../hooks/useToast';
import { useSettings } from '../../hooks/useSettings';
import { TwoFactorSettings } from '../auth/TwoFactorSettings';

export const SettingsView: React.FC = () => {
  const [schema, setSchema] = useState<SettingsSchema>({});
  const [values, setValues] = useState<SettingsValues>({});
  const [activeGroup, setActiveGroup] = useState<string>('');
  const [errors, setErrors] = useState<ValidationErrors>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const { success, error: toastError } = useToast();
  const { reload: reloadGlobalSettings } = useSettings();

  useEffect(() => {
    void load();
  }, []);

  const load = async () => {
    setLoading(true);
    try {
      const payload = await getSettings();
      if (payload) {
        setSchema(payload.schema);
        setValues(payload.values);
        setActiveGroup((prev) => prev || Object.keys(payload.schema)[0] || '');
      } else {
        toastError('Nepodarilo sa načítať nastavenia');
      }
    } finally {
      setLoading(false);
    }
  };

  const groupKeys = useMemo(() => Object.keys(schema), [schema]);
  const group = activeGroup ? schema[activeGroup] : undefined;
  const groupValues = activeGroup ? values[activeGroup] ?? {} : {};

  const setFieldValue = (key: string, value: unknown) => {
    setValues((prev) => ({
      ...prev,
      [activeGroup]: { ...(prev[activeGroup] ?? {}), [key]: value },
    }));
    // Zmiznutie inline chyby po úprave poľa.
    setErrors((prev) => {
      if (!prev[key]) return prev;
      const next = { ...prev };
      delete next[key];
      return next;
    });
  };

  const handleSave = async () => {
    if (!group) return;

    const rules = rulesFromSchema(group);
    const result = validate(groupValues, rules);
    if (!result.valid) {
      setErrors(result.errors);
      toastError('Skontrolujte vyplnené polia');
      return;
    }

    setSaving(true);
    setErrors({});
    try {
      const res = await updateSettingsGroup(activeGroup, groupValues);
      if (res.success && res.data) {
        setValues((prev) => ({ ...prev, [activeGroup]: res.data!.values }));
        await reloadGlobalSettings();
        success('Nastavenia uložené');
      } else if (res.errors) {
        // Autoritatívne validačné chyby z backendu (422).
        setErrors(res.errors);
        toastError(res.error || 'Validácia zlyhala');
      } else {
        toastError(res.error || 'Uloženie zlyhalo');
      }
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Nastavenia</h1>
        <button onClick={handleSave} disabled={saving} className="btn btn-primary">
          {saving ? 'Ukladám...' : 'Uložiť zmeny'}
        </button>
      </div>

      {/* Záložky skupín */}
      <div className="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700">
        {groupKeys.map((key) => (
          <button
            key={key}
            onClick={() => {
              setActiveGroup(key);
              setErrors({});
            }}
            className={`px-4 py-2 -mb-px text-sm font-medium border-b-2 transition-colors ${
              activeGroup === key
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
            }`}
          >
            {schema[key].label}
          </button>
        ))}
      </div>

      {/* Formulár aktívnej skupiny */}
      {group && (
        <div className="card">
          <div className="card-body space-y-5">
            {group.fields.map((field) => (
              <SettingFieldRow
                key={field.key}
                field={field}
                value={groupValues[field.key]}
                error={errors[field.key]?.[0] ?? null}
                onChange={(v) => setFieldValue(field.key, v)}
              />
            ))}
          </div>
        </div>
      )}

      <TwoFactorSettings />
    </div>
  );
};

interface RowProps {
  field: SettingField;
  value: unknown;
  error: string | null;
  onChange: (value: unknown) => void;
}

const SettingFieldRow: React.FC<RowProps> = ({ field, value, error, onChange }) => {
  const inputId = `setting-${field.key}`;
  const errorClass = error ? 'border-red-500 focus:ring-red-500' : '';

  return (
    <div>
      {field.type === 'bool' ? (
        <label htmlFor={inputId} className="flex items-center gap-3 cursor-pointer">
          <input
            id={inputId}
            type="checkbox"
            checked={Boolean(value)}
            onChange={(e) => onChange(e.target.checked)}
            className="h-4 w-4 rounded border-gray-300 text-indigo-600"
          />
          <span className="text-sm font-medium text-gray-700 dark:text-gray-200">{field.label}</span>
        </label>
      ) : (
        <>
          <label htmlFor={inputId} className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
            {field.label}
          </label>

          {field.type === 'text' && (
            <textarea
              id={inputId}
              value={String(value ?? '')}
              onChange={(e) => onChange(e.target.value)}
              rows={3}
              className={`form-input w-full ${errorClass}`}
            />
          )}

          {field.type === 'enum' && (
            <select
              id={inputId}
              value={String(value ?? '')}
              onChange={(e) => onChange(e.target.value)}
              className={`form-input w-full ${errorClass}`}
            >
              {(field.options ?? []).map((opt) => (
                <option key={opt} value={opt}>
                  {opt}
                </option>
              ))}
            </select>
          )}

          {(field.type === 'int') && (
            <input
              id={inputId}
              type="number"
              value={value === null || value === undefined ? '' : Number(value)}
              onChange={(e) => onChange(e.target.value === '' ? '' : Number(e.target.value))}
              className={`form-input w-full ${errorClass}`}
            />
          )}

          {(field.type === 'string' || field.type === 'email' || field.type === 'url' || field.type === 'password') && (
            <input
              id={inputId}
              type={
                field.type === 'email'
                  ? 'email'
                  : field.type === 'url'
                    ? 'url'
                    : field.type === 'password'
                      ? 'password'
                      : 'text'
              }
              value={String(value ?? '')}
              onChange={(e) => onChange(e.target.value)}
              className={`form-input w-full ${errorClass}`}
              autoComplete={field.type === 'password' ? 'new-password' : undefined}
            />
          )}
        </>
      )}

      {field.help && !error && (
        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{field.help}</p>
      )}
      {error && <p className="mt-1 text-xs text-red-600 dark:text-red-400">{error}</p>}
    </div>
  );
};

export default SettingsView;
