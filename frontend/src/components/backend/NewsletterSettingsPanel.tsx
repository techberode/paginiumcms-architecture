import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { ExternalLink, Settings2 } from 'lucide-react';
import {
  getSettings,
  updateSettingsGroup,
  type SettingField,
  type SettingsValues,
} from '../../api/settings';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { useSettings } from '../../hooks/useSettings';
import { AdminFormActions } from './AdminFormActions';
import { previewPatchFromGroup } from '../../utils/settingsPreview';
import {
  translateSettingFieldHelp,
  translateSettingFieldLabel,
} from '../../i18n/modules/settings/helpers';

const BOOL_KEYS = [
  'footerEnabled',
  'sendEnabled',
  'weeklyDigestEnabled',
  'newArticleEnabled',
  'cmsReleaseEnabled',
  'requireDoubleOptIn',
  'requireConsentCheckbox',
] as const;

export const NewsletterSettingsPanel: React.FC<{
  onSaved?: () => void;
}> = ({ onSaved }) => {
  const { t } = useI18n();
  const { success, error: showError } = useToast();
  const { applyPreview, clearPreviewGroup, reload: reloadPublicSettings } = useSettings();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [fields, setFields] = useState<SettingField[]>([]);
  const [values, setValues] = useState<Record<string, unknown>>({});

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const payload = await getSettings();
      const group = payload?.schema?.newsletter;
      const groupValues = payload?.values?.newsletter ?? {};
      if (!group) {
        setFields([]);
        setValues({});
        return;
      }

      setFields(group.fields);
      setValues({ ...groupValues });
    } catch {
      showError(t('newsletter.settings.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [showError, t]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => () => {
    clearPreviewGroup('newsletter');
  }, [clearPreviewGroup]);

  const boolFields = useMemo(
    () => fields.filter((field) => field.type === 'bool' && BOOL_KEYS.includes(field.key as (typeof BOOL_KEYS)[number])),
    [fields]
  );

  const textFields = useMemo(
    () => fields.filter((field) => field.key === 'enabledPreferences' || field.key === 'footerHint'),
    [fields]
  );

  const setBool = (key: string, checked: boolean) => {
    setValues((current) => ({ ...current, [key]: checked }));
  };

  const setText = (key: string, value: string) => {
    setValues((current) => ({ ...current, [key]: value }));
  };

  const handleApply = () => {
    const patch = previewPatchFromGroup('newsletter', values);
    if (!patch) {
      showError(t('newsletter.settings.applyUnavailable'));
      return;
    }
    applyPreview(patch);
    success(t('newsletter.settings.applied'));
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      const payload = await getSettings();
      const merged: SettingsValues['newsletter'] = {
        ...(payload?.values?.newsletter ?? {}),
        ...values,
      };

      const res = await updateSettingsGroup('newsletter', merged);
      if (!res.success) {
        showError(res.message ?? t('newsletter.settings.saveFailed'));
        return;
      }

      clearPreviewGroup('newsletter');
      await reloadPublicSettings();
      success(t('newsletter.settings.saved'));
      await load();
      onSaved?.();
    } catch {
      showError(t('newsletter.settings.saveFailed'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h2 className="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
            <Settings2 className="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
            {t('newsletter.settings.title')}
          </h2>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">{t('newsletter.settings.subtitle')}</p>
        </div>
        <Link
          to="/settings?category=system&group=newsletter"
          className="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
        >
          {t('newsletter.settings.openFull')}
          <ExternalLink className="h-4 w-4" />
        </Link>
      </div>

      {loading ? (
        <p className="text-sm text-slate-500 dark:text-slate-400">{t('list.loading')}</p>
      ) : (
        <>
          <div className="grid gap-3 sm:grid-cols-2">
            {boolFields.map((field) => (
              <label
                key={field.key}
                className="flex items-start gap-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 px-3 py-2 cursor-pointer"
              >
                <input
                  type="checkbox"
                  checked={values[field.key] === true}
                  onChange={(event) => setBool(field.key, event.target.checked)}
                  className="mt-0.5 h-4 w-4 rounded border-slate-200 dark:border-slate-800 text-indigo-600 dark:text-indigo-400"
                />
                <span className="min-w-0">
                  <span className="block text-sm font-medium text-slate-900 dark:text-white">
                    {translateSettingFieldLabel(t, 'newsletter', field.key, field.label)}
                  </span>
                  {field.help ? (
                    <span className="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                      {translateSettingFieldHelp(t, 'newsletter', field.key, field.help)}
                    </span>
                  ) : null}
                </span>
              </label>
            ))}
          </div>

          {textFields.map((field) => (
            <label key={field.key} className="block space-y-1">
              <span className="text-sm font-medium text-slate-900 dark:text-white">
                {translateSettingFieldLabel(t, 'newsletter', field.key, field.label)}
              </span>
              {field.type === 'text' ? (
                <textarea
                  rows={field.key === 'enabledPreferences' ? 4 : 2}
                  value={String(values[field.key] ?? '')}
                  onChange={(event) => setText(field.key, event.target.value)}
                  className="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white"
                />
              ) : (
                <input
                  type="text"
                  value={String(values[field.key] ?? '')}
                  onChange={(event) => setText(field.key, event.target.value)}
                  className="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-white"
                />
              )}
              {field.help ? (
                <span className="block text-xs text-slate-500 dark:text-slate-400">
                  {translateSettingFieldHelp(t, 'newsletter', field.key, field.help)}
                </span>
              ) : null}
            </label>
          ))}

          <AdminFormActions
            showApply
            onApply={handleApply}
            onSave={() => void handleSave()}
            applyLabel={t('newsletter.settings.apply')}
            saveLabel={saving ? t('newsletter.settings.saving') : t('newsletter.settings.save')}
            applyDisabled={saving}
            saveDisabled={saving}
            saveBusy={saving}
          />
        </>
      )}
    </div>
  );
};

export default NewsletterSettingsPanel;
