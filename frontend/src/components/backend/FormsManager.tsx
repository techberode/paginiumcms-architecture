import React, { useCallback, useEffect, useState } from 'react';
import { Clipboard, Plus, RefreshCw, Trash2 } from 'lucide-react';
import { formsApi, type FormDefinition, type FormListItem } from '../../api/forms';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { useAdminConfirm } from '../../hooks/useAdminConfirm';
import { AdminHintCard } from './AdminHintCard';
import { ADMIN_PAGE_TITLE, ADMIN_PAGE_SUBTITLE } from '../../theme/adminUiClasses';

export const FormsManager: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const confirmDestructive = useAdminConfirm();
  const [loading, setLoading] = useState(true);
  const [forms, setForms] = useState<FormListItem[]>([]);
  const [presets, setPresets] = useState<string[]>([]);
  const [selectedId, setSelectedId] = useState('');
  const [draft, setDraft] = useState<FormDefinition | null>(null);
  const [jsonText, setJsonText] = useState('');
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const response = await formsApi.list();
      if (!response.success || !response.data) {
        toast.error(t('platform.forms.toast.loadFailed'));
        return;
      }
      setForms(response.data.forms);
      setPresets(response.data.presets);
    } finally {
      setLoading(false);
    }
  }, [t, toast]);

  useEffect(() => {
    void load();
  }, [load]);

  const openForm = async (id: string) => {
    const response = await formsApi.get(id);
    if (!response.success || !response.data) {
      toast.error(t('platform.forms.toast.loadFailed'));
      return;
    }
    setSelectedId(id);
    setDraft(response.data.form);
    setJsonText(JSON.stringify(response.data.form, null, 2));
  };

  const createPreset = async (preset: string) => {
    const response = await formsApi.createFromPreset(preset);
    if (!response.success || !response.data) {
      toast.error(response.error || t('platform.forms.toast.createFailed'));
      return;
    }
    toast.success(t('platform.forms.toast.created'));
    await load();
    void openForm(response.data.form.id);
  };

  const saveDraft = async () => {
    let parsed: FormDefinition;
    try {
      parsed = JSON.parse(jsonText) as FormDefinition;
    } catch {
      toast.error(t('platform.forms.toast.invalidJson'));
      return;
    }
    setSaving(true);
    try {
      const response = await formsApi.save(parsed);
      if (!response.success) {
        toast.error(response.error || t('platform.forms.toast.saveFailed'));
        return;
      }
      toast.success(t('platform.forms.toast.saved'));
      await load();
      const saved = response.data?.form ?? parsed;
      setDraft(saved);
      setJsonText(JSON.stringify(saved, null, 2));
    } finally {
      setSaving(false);
    }
  };

  const removeForm = async (id: string) => {
    if (!(await confirmDestructive(t('platform.forms.confirmDelete', { id })))) {
      return;
    }
    const response = await formsApi.remove(id);
    if (!response.success) {
      toast.error(response.error || t('platform.forms.toast.deleteFailed'));
      return;
    }
    toast.success(t('platform.forms.toast.deleted'));
    if (selectedId === id) {
      setSelectedId('');
      setDraft(null);
    }
    await load();
  };

  const copyMarkup = async (markup: string) => {
    try {
      await navigator.clipboard.writeText(markup);
      toast.success(t('platform.forms.toast.copied'));
    } catch {
      toast.error(t('platform.forms.toast.copyFailed'));
    }
  };

  return (
    <div className="space-y-6">
      <p className={ADMIN_PAGE_TITLE}>{t('platform.forms.title')}</p>
      <p className={ADMIN_PAGE_SUBTITLE}>{t('platform.forms.subtitle')}</p>

      <AdminHintCard title={t('platform.forms.hintTitle')}>{t('platform.forms.hint')}</AdminHintCard>

      <div className="flex flex-wrap gap-2">
        <button type="button" className="btn btn-secondary" onClick={() => void load()} disabled={loading}>
          <RefreshCw className="mr-1 inline h-4 w-4" />
          {t('platform.forms.refresh')}
        </button>
        {presets.map((preset) => (
          <button key={preset} type="button" className="btn btn-primary" onClick={() => void createPreset(preset)}>
            <Plus className="mr-1 inline h-4 w-4" />
            {t('platform.forms.addPreset', { preset })}
          </button>
        ))}
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="rounded-xl border border-admin-border bg-admin-card p-4">
          <h2 className="mb-3 text-sm font-semibold text-admin-text">{t('platform.forms.listTitle')}</h2>
          {loading ? (
            <p className="text-sm text-admin-muted">{t('platform.forms.loading')}</p>
          ) : forms.length === 0 ? (
            <p className="text-sm text-admin-muted">{t('platform.forms.empty')}</p>
          ) : (
            <ul className="space-y-2">
              {forms.map((row) => (
                <li key={row.id} className="rounded-lg border border-admin-border p-3">
                  <div className="flex items-start justify-between gap-2">
                    <button type="button" className="text-left" onClick={() => void openForm(row.id)}>
                      <p className="font-semibold text-admin-text">{row.title}</p>
                      <p className="text-xs text-admin-muted">
                        {row.id} · {row.enabled ? t('platform.forms.enabled') : t('platform.forms.disabled')}
                      </p>
                    </button>
                    <button
                      type="button"
                      className="text-admin-muted hover:text-red-600"
                      aria-label={t('platform.forms.delete')}
                      onClick={() => void removeForm(row.id)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                  <div className="mt-2 flex flex-wrap gap-2">
                    <button type="button" className="text-xs text-admin-primary" onClick={() => void copyMarkup(row.markup)}>
                      <Clipboard className="mr-1 inline h-3 w-3" />
                      {t('platform.forms.copyShortcode')}
                    </button>
                    <button
                      type="button"
                      className="text-xs text-admin-primary"
                      onClick={() => void copyMarkup(row.widgetMarkup)}
                    >
                      <Clipboard className="mr-1 inline h-3 w-3" />
                      {t('platform.forms.copyWidget')}
                    </button>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>

        <div className="rounded-xl border border-admin-border bg-admin-card p-4">
          <h2 className="mb-3 text-sm font-semibold text-admin-text">{t('platform.forms.editorTitle')}</h2>
          {draft ? (
            <div className="space-y-3">
              <label className="block text-sm text-admin-muted">
                {t('platform.forms.jsonLabel')}
                <textarea
                  className="mt-1 min-h-[360px] w-full rounded-lg border border-admin-border bg-admin-canvas px-3 py-2 font-mono text-xs text-admin-text"
                  value={jsonText}
                  onChange={(event) => setJsonText(event.target.value)}
                />
              </label>
              <button type="button" className="btn btn-primary" disabled={saving} onClick={() => void saveDraft()}>
                {saving ? t('platform.forms.saving') : t('platform.forms.save')}
              </button>
            </div>
          ) : (
            <p className="text-sm text-admin-muted">{t('platform.forms.selectForm')}</p>
          )}
        </div>
      </div>
    </div>
  );
};
