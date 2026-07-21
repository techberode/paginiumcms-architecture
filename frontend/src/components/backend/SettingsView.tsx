// frontend/src/components/backend/SettingsView.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useLocation, useSearchParams } from 'react-router-dom';
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
import { useI18n } from '../../context/I18nContext';
import {
  translateSettingEnumOption,
  translateSettingFieldHelp,
  translateSettingFieldLabel,
  translateSettingGroup,
} from '../../i18n/modules/settings/helpers';
import { zodFromRules } from '../../validation/zodFromRules';
import { applyApiValidationErrors } from '../../validation/mapApiErrors';
import {
  SETTINGS_CATEGORIES,
  groupsForCategory,
  resolveSettingsCategory,
  type SettingsCategoryId,
} from '../../i18n/modules/settings/categories';
import { CacheManagerPanel } from './CacheManagerPanel';
import { AdminHintCard } from './AdminHintCard';
import { LoginBackgroundImagePicker } from './LoginBackgroundImagePicker';

function resolveRequestedSettingsGroup(
  searchParams: URLSearchParams,
  locationState: unknown
): string {
  const fromQuery = searchParams.get('group')?.trim() ?? '';
  if (fromQuery) {
    return fromQuery;
  }

  const fromState = (locationState as { group?: string } | null)?.group?.trim() ?? '';
  return fromState;
}

function resolveRequestedCategory(
  searchParams: URLSearchParams,
  groupKey: string
): SettingsCategoryId {
  const fromQuery = searchParams.get('category')?.trim() ?? '';
  if (fromQuery === 'system' || fromQuery === 'site' || fromQuery === 'media' || fromQuery === 'security') {
    return fromQuery;
  }
  return resolveSettingsCategory(groupKey);
}

export const SettingsView: React.FC = () => {
  const { t } = useI18n();
  const [searchParams, setSearchParams] = useSearchParams();
  const location = useLocation();
  const [schema, setSchema] = useState<SettingsSchema>({});
  const [values, setValues] = useState<SettingsValues>({});
  const [activeGroup, setActiveGroup] = useState<string>('');
  const [activeCategory, setActiveCategory] = useState<SettingsCategoryId>('system');
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
    watch,
    setValue,
    formState: { errors, isSubmitting },
  } = useForm<Record<string, unknown>>({
    resolver: zodResolver(zodSchema),
    defaultValues: {},
  });

  useEffect(() => {
    if (activeGroup && values[activeGroup]) {
      reset(values[activeGroup]);
    }
  }, [activeGroup, values, reset]);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const payload = await getSettings();
      if (payload) {
        setSchema(payload.schema);
        setValues(payload.values);
      } else {
        toastError(t('settings.page.loadFailed'));
      }
    } finally {
      setLoading(false);
    }
  }, [t, toastError]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    const groupKeys = Object.keys(schema);
    if (!groupKeys.length) {
      return;
    }

    const requested = resolveRequestedSettingsGroup(searchParams, location.state);
    const nextGroup = requested && schema[requested] ? requested : groupKeys[0];
    const nextCategory = resolveRequestedCategory(searchParams, nextGroup);

    setActiveGroup((prev) => (prev === nextGroup ? prev : nextGroup));
    setActiveCategory((prev) => (prev === nextCategory ? prev : nextCategory));

    const params: Record<string, string> = { category: nextCategory, group: nextGroup };
    if (searchParams.get('category') !== nextCategory || searchParams.get('group') !== nextGroup) {
      setSearchParams(params, { replace: true });
    }
  }, [location.state, schema, searchParams, setSearchParams]);

  const selectCategory = (categoryId: SettingsCategoryId) => {
    const available = Object.keys(schema);
    const groups = groupsForCategory(categoryId, available);
    const nextGroup = groups.includes(activeGroup) ? activeGroup : groups[0] ?? available[0] ?? '';
    setActiveCategory(categoryId);
    setActiveGroup(nextGroup);
    setSearchParams({ category: categoryId, group: nextGroup }, { replace: true });
  };

  const selectGroup = (key: string) => {
    const categoryId = resolveSettingsCategory(key);
    setActiveCategory(categoryId);
    setActiveGroup(key);
    setSearchParams({ category: categoryId, group: key }, { replace: true });
  };

  const onSubmit = async (formValues: Record<string, unknown>) => {
    if (!activeGroup) return;

    const res = await updateSettingsGroup(activeGroup, formValues);
    if (res.success && res.data) {
      setValues((prev) => ({ ...prev, [activeGroup]: res.data!.values }));
      await reloadGlobalSettings();
      success(t('settings.page.saved'));
      return;
    }

    if (applyApiValidationErrors(res, setError)) {
      toastError(res.error || t('settings.page.validationFailed'));
      return;
    }

    toastError(res.error || t('settings.page.saveFailed'));
  };

  const groupKeys = Object.keys(schema);
  const visibleGroups = groupsForCategory(activeCategory, groupKeys);

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('settings.page.title')}</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {t(`settings.categories.${activeCategory}.description`)}
          </p>
        </div>
        <button
          type="button"
          onClick={() => void handleSubmit(onSubmit)()}
          disabled={isSubmitting}
          className="btn btn-primary shrink-0"
        >
          {isSubmitting ? t('settings.page.saving') : t('settings.page.save')}
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-[220px_minmax(0,1fr)] gap-6">
        <aside className="space-y-1">
          {SETTINGS_CATEGORIES.map((category) => {
            const groups = groupsForCategory(category.id, groupKeys);
            if (groups.length === 0) {
              return null;
            }
            const active = activeCategory === category.id;
            return (
              <button
                key={category.id}
                type="button"
                onClick={() => selectCategory(category.id)}
                className={`w-full text-left px-4 py-3 rounded-xl border transition-colors ${
                  active
                    ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300'
                    : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/60 text-gray-700 dark:text-gray-200'
                }`}
              >
                <div className="text-sm font-bold">{t(category.labelKey)}</div>
                <div className="text-xs opacity-70 mt-1">{groups.length} skupín</div>
              </button>
            );
          })}
        </aside>

        <div className="space-y-4 min-w-0">
          <div className="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-1">
            {visibleGroups.map((key) => (
              <button
                key={key}
                type="button"
                onClick={() => selectGroup(key)}
                className={`px-3 py-2 text-sm font-medium border-b-2 transition-colors ${
                  activeGroup === key
                    ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                }`}
              >
                {translateSettingGroup(t, key, schema[key].label)}
              </button>
            ))}
          </div>

          {activeCategory === 'security' && (
            <AdminHintCard tone="warning" title={t('settings.hints.security.title')}>
              {t('settings.hints.security.body')}
            </AdminHintCard>
          )}

          {group && (
            <form onSubmit={handleSubmit(onSubmit)} className="card">
              <div className="card-body space-y-5">
                {group.fields.map((field) => (
                  <SettingFieldRow
                    key={field.key}
                    groupKey={activeGroup}
                    field={field}
                    register={register}
                    watch={watch}
                    setValue={setValue}
                    error={errors[field.key]?.message as string | undefined}
                  />
                ))}
              </div>
            </form>
          )}

          {activeCategory === 'system' && <CacheManagerPanel />}
        </div>
      </div>

      <div className="card">
        <div className="card-body flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
              {t('settings.twoFactor.title')}
            </h3>
            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
              {t('settings.twoFactor.description')}
            </p>
          </div>
          <Link to="/account/security" className="btn btn-secondary shrink-0">
            {t('settings.twoFactor.link')}
          </Link>
        </div>
      </div>
    </div>
  );
};

interface RowProps {
  groupKey: string;
  field: SettingField;
  register: ReturnType<typeof useForm>['register'];
  watch: ReturnType<typeof useForm>['watch'];
  setValue: ReturnType<typeof useForm>['setValue'];
  error?: string;
}

const SettingFieldRow: React.FC<RowProps> = ({ groupKey, field, register, watch, setValue, error }) => {
  const { t } = useI18n();
  const inputId = `setting-${field.key}`;
  const errorClass = error ? 'border-red-500 focus:ring-red-500' : '';
  const label = translateSettingFieldLabel(t, groupKey, field.key, field.label);
  const help = translateSettingFieldHelp(t, groupKey, field.key, field.help);

  if (groupKey === 'login' && field.key === 'backgroundImageUrl') {
    const currentValue = String(watch(field.key) ?? '');

    return (
      <LoginBackgroundImagePicker
        value={currentValue}
        onChange={(url) =>
          setValue(field.key, url, { shouldDirty: true, shouldValidate: true })
        }
        label={label}
        help={help}
        error={error}
      />
    );
  }

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
          <span className="text-sm font-medium text-gray-700 dark:text-gray-200">{label}</span>
        </label>
      ) : (
        <>
          <label htmlFor={inputId} className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
            {label}
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
                  {field.key === 'language'
                    ? translateSettingEnumOption(t, field.key, opt, opt)
                    : opt}
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

      {help && !error && (
        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{help}</p>
      )}
      {error && <p className="mt-1 text-xs text-red-600 dark:text-red-400">{error}</p>}
    </div>
  );
};

export default SettingsView;
