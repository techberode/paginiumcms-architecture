// frontend/src/components/backend/SettingsView.tsx
import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { ShieldCheck } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import {
  getSettings,
  updateSettingsGroup,
  rulesFromSchema,
  SettingsSchema,
  SettingsValues,
  SettingField,
} from '../../api/settings';
import { useToast } from '../../hooks/useToast';
import { useSettings } from '../../hooks/useSettings';
import { zodFromRules } from '../../validation/zodFromRules';
import { applyApiValidationErrors } from '../../validation/mapApiErrors';

export const SettingsView: React.FC = () => {
  const [schema, setSchema] = useState<SettingsSchema>({});
  const [values, setValues] = useState<SettingsValues>({});
  const [activeGroup, setActiveGroup] = useState<string>('');
  const [loading, setLoading] = useState(true);
  const { success, error: toastError } = useToast();
  const { reload: reloadGlobalSettings } = useSettings();

  const group = activeGroup ? schema[activeGroup] : undefined;
  const groupRules = useMemo(
    () => (group ? rulesFromSchema(group) : {}),
    [group]
  );
  const zodSchema = useMemo(() => zodFromRules(groupRules), [groupRules]);

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<Record<string, unknown>>({
    resolver: zodResolver(zodSchema),
    defaultValues: {},
  });

  useEffect(() => {
    void load();
  }, []);

  useEffect(() => {
    if (activeGroup && values[activeGroup]) {
      reset(values[activeGroup]);
    }
  }, [activeGroup, values, reset]);

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

  const onSubmit = async (formValues: Record<string, unknown>) => {
    if (!activeGroup) return;

    const res = await updateSettingsGroup(activeGroup, formValues);
    if (res.success && res.data) {
      setValues((prev) => ({ ...prev, [activeGroup]: res.data!.values }));
      await reloadGlobalSettings();
      success('Nastavenia uložené');
      return;
    }

    if (applyApiValidationErrors(res, setError)) {
      toastError(res.error || 'Validácia zlyhala');
      return;
    }

    toastError(res.error || 'Uloženie zlyhalo');
  };

  const groupKeys = Object.keys(schema);

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
        <button
          type="button"
          onClick={() => void handleSubmit(onSubmit)()}
          disabled={isSubmitting}
          className="btn btn-primary"
        >
          {isSubmitting ? 'Ukladám...' : 'Uložiť zmeny'}
        </button>
      </div>

      <div className="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700">
        {groupKeys.map((key) => (
          <button
            key={key}
            type="button"
            onClick={() => setActiveGroup(key)}
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

      {group && (
        <form onSubmit={handleSubmit(onSubmit)} className="card">
          <div className="card-body space-y-5">
            {group.fields.map((field) => (
              <SettingFieldRow
                key={field.key}
                field={field}
                register={register}
                error={errors[field.key]?.message as string | undefined}
              />
            ))}
          </div>
        </form>
      )}

      <div className="card">
        <div className="card-body flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div className="flex items-start gap-3">
            <ShieldCheck className="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" />
            <div>
              <h3 className="text-sm font-semibold text-gray-900 dark:text-white">Dvojfaktorové overenie (2FA)</h3>
              <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Nastavenie QR kódu a TOTP autentifikátora je v samostatnej sekcii bezpečnosti účtu.
              </p>
            </div>
          </div>
          <Link to="/account/security" className="btn btn-secondary shrink-0">
            Prejsť na bezpečnosť účtu
          </Link>
        </div>
      </div>
    </div>
  );
};

interface RowProps {
  field: SettingField;
  register: ReturnType<typeof useForm>['register'];
  error?: string;
}

const SettingFieldRow: React.FC<RowProps> = ({ field, register, error }) => {
  const inputId = `setting-${field.key}`;
  const errorClass = error ? 'border-red-500 focus:ring-red-500' : '';

  return (
    <div>
      {field.type === 'bool' ? (
        <label htmlFor={inputId} className="flex items-center gap-3 cursor-pointer">
          <input
            id={inputId}
            type="checkbox"
            {...register(field.key, {
              setValueAs: (v) => v === true || v === 'on' || v === 'true' || v === 1,
            })}
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
              rows={3}
              {...register(field.key)}
              className={`form-input w-full ${errorClass}`}
            />
          )}

          {field.type === 'enum' && (
            <select id={inputId} {...register(field.key)} className={`form-input w-full ${errorClass}`}>
              {(field.options ?? []).map((opt) => (
                <option key={opt} value={opt}>
                  {opt}
                </option>
              ))}
            </select>
          )}

          {field.type === 'int' && (
            <input
              id={inputId}
              type="number"
              {...register(field.key, { valueAsNumber: true })}
              className={`form-input w-full ${errorClass}`}
            />
          )}

          {(field.type === 'string' ||
            field.type === 'email' ||
            field.type === 'url' ||
            field.type === 'password') && (
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
              {...register(field.key)}
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
