// frontend/src/components/blueprint/DynamicForm.tsx
import React from 'react';
import type { BlueprintField } from '../../api/blueprint';

interface DynamicFormProps {
  fields: BlueprintField[];
  values: Record<string, unknown>;
  onChange: (key: string, value: unknown) => void;
  errors?: Record<string, string>;
  disabled?: boolean;
}

export const DynamicForm: React.FC<DynamicFormProps> = ({
  fields,
  values,
  onChange,
  errors = {},
  disabled = false,
}) => {
  return (
    <div className="space-y-4">
      {fields.map((field) => {
        const value = values[field.key] ?? field.default ?? '';
        const error = errors[field.key];
        const inputClass =
          'w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm';

        return (
          <div key={field.key}>
            <label className="block text-sm font-semibold mb-1">
              {field.label}
              {field.rules.includes('required') && <span className="text-red-500"> *</span>}
            </label>
            {field.help && <p className="text-xs text-slate-500 mb-1">{field.help}</p>}

            {field.type === 'select' ? (
              <select
                className={inputClass}
                disabled={disabled}
                value={String(value)}
                onChange={(e) => onChange(field.key, e.target.value)}
              >
                {(field.options ?? []).map((option) => (
                  <option key={option} value={option}>
                    {option}
                  </option>
                ))}
              </select>
            ) : field.type === 'markdown' || field.type === 'textarea' ? (
              <textarea
                className={`${inputClass} min-h-[120px]`}
                disabled={disabled}
                value={String(value)}
                onChange={(e) => onChange(field.key, e.target.value)}
              />
            ) : field.type === 'bool' ? (
              <input
                type="checkbox"
                disabled={disabled}
                checked={Boolean(value)}
                onChange={(e) => onChange(field.key, e.target.checked)}
              />
            ) : (
              <input
                type={field.type === 'number' ? 'number' : 'text'}
                className={inputClass}
                disabled={disabled}
                value={String(value)}
                onChange={(e) =>
                  onChange(field.key, field.type === 'number' ? Number(e.target.value) : e.target.value)
                }
              />
            )}

            {error && <p className="text-xs text-red-500 mt-1">{error}</p>}
          </div>
        );
      })}
    </div>
  );
};

export default DynamicForm;
