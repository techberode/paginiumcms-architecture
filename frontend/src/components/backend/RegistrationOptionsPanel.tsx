import React, { useCallback, useEffect, useState } from 'react';
import { Plus, Trash2 } from 'lucide-react';
import {
  registrationOptionsApi,
  type RegistrationOption,
  type RegistrationRoleChoice,
} from '../../api/registrationOptions';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { AdminFormActions } from './AdminFormActions';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { ADMIN_INPUT } from '../../theme/adminUiClasses';

function emptyOption(roleId: string): RegistrationOption {
  return {
    id: '',
    label: '',
    roleId,
    enabled: true,
    requireAdminApproval: true,
    assignTeamId: '',
    welcomeMailEnabled: true,
    welcomeMailSubject: '',
    welcomeMailBody: '',
  };
}

export const RegistrationOptionsPanel: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [options, setOptions] = useState<RegistrationOption[]>([]);
  const [roles, setRoles] = useState<RegistrationRoleChoice[]>([]);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const index = await registrationOptionsApi.list();
      setOptions(index.options);
      setRoles(index.roles);
    } catch {
      toast.error(t('platform.registrationOptions.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [t, toast]);

  useEffect(() => {
    void load();
  }, [load]);

  const update = (index: number, patch: Partial<RegistrationOption>) => {
    setOptions((current) => current.map((option, i) => (i === index ? { ...option, ...patch } : option)));
  };

  const handleSave = async () => {
    if (options.some((option) => option.label.trim() === '')) {
      toast.error(t('platform.registrationOptions.toast.labelRequired'));
      return;
    }
    setSaving(true);
    try {
      const response = await registrationOptionsApi.save(options);
      if (!successOptions(response)) {
        toast.error(response.error || t('platform.registrationOptions.toast.saveFailed'));
        return;
      }
      setOptions(response.data.options);
      toast.success(t('platform.registrationOptions.toast.saved'));
    } finally {
      setSaving(false);
    }
  };

  const defaultRole = roles[0]?.id ?? 'USER';

  return (
    <AdminWidgetCard title={t('platform.registrationOptions.title')}>
      <div className="space-y-4" data-testid="registration-options">
        <p className="text-sm text-admin-muted">{t('platform.registrationOptions.hint')}</p>
        {loading ? (
          <p className="text-sm text-admin-muted">{t('platform.registrationOptions.loading')}</p>
        ) : (
          options.map((option, index) => (
            <fieldset
              key={option.id || `new-${index}`}
              className="rounded-xl border border-admin-border p-3 space-y-3"
              data-testid={`registration-option-${index}`}
            >
              <div className="flex justify-between gap-2">
                <label className="flex-1 text-sm">
                  <span className="text-admin-muted">{t('platform.registrationOptions.label')}</span>
                  <input
                    data-testid={`registration-option-label-${index}`}
                    value={option.label}
                    onChange={(event) => update(index, { label: event.target.value })}
                    className={`mt-1 ${ADMIN_INPUT}`}
                  />
                </label>
                <button
                  type="button"
                  className="self-end p-2 text-admin-muted hover:text-rose-600"
                  onClick={() => setOptions((current) => current.filter((_, i) => i !== index))}
                  aria-label={t('platform.registrationOptions.remove')}
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
              <label className="block text-sm">
                <span className="text-admin-muted">{t('platform.registrationOptions.role')}</span>
                <select
                  data-testid={`registration-option-role-${index}`}
                  value={option.roleId}
                  onChange={(event) => update(index, { roleId: event.target.value })}
                  className={`mt-1 ${ADMIN_INPUT}`}
                >
                  {roles.map((role) => (
                    <option key={role.id} value={role.id}>
                      {role.name}
                    </option>
                  ))}
                </select>
              </label>
              <label className="flex items-center gap-2 text-sm text-admin-text">
                <input
                  type="checkbox"
                  checked={option.enabled}
                  onChange={(event) => update(index, { enabled: event.target.checked })}
                />
                {t('platform.registrationOptions.enabled')}
              </label>
              <label className="flex items-center gap-2 text-sm text-admin-text">
                <input
                  type="checkbox"
                  checked={option.requireAdminApproval}
                  onChange={(event) => update(index, { requireAdminApproval: event.target.checked })}
                />
                {t('platform.registrationOptions.requireApproval')}
              </label>
              <label className="flex items-center gap-2 text-sm text-admin-text">
                <input
                  type="checkbox"
                  checked={option.welcomeMailEnabled}
                  onChange={(event) => update(index, { welcomeMailEnabled: event.target.checked })}
                />
                {t('platform.registrationOptions.welcomeEnabled')}
              </label>
              <label className="block text-sm">
                <span className="text-admin-muted">{t('platform.registrationOptions.welcomeSubject')}</span>
                <input
                  value={option.welcomeMailSubject}
                  onChange={(event) => update(index, { welcomeMailSubject: event.target.value })}
                  className={`mt-1 ${ADMIN_INPUT}`}
                />
              </label>
              <label className="block text-sm">
                <span className="text-admin-muted">{t('platform.registrationOptions.welcomeBody')}</span>
                <textarea
                  rows={4}
                  value={option.welcomeMailBody}
                  onChange={(event) => update(index, { welcomeMailBody: event.target.value })}
                  className={`mt-1 ${ADMIN_INPUT}`}
                />
              </label>
            </fieldset>
          ))
        )}
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            data-testid="registration-option-add"
            onClick={() => setOptions((current) => [...current, emptyOption(defaultRole)])}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-admin-border text-sm text-admin-text"
          >
            <Plus className="w-4 h-4" />
            {t('platform.registrationOptions.add')}
          </button>
          <AdminFormActions
            onSave={() => void handleSave()}
            saveLabel={saving ? t('platform.registrationOptions.saving') : t('platform.registrationOptions.save')}
            saveDisabled={saving || loading}
            saveBusy={saving}
            saveTestId="registration-options-save"
          />
        </div>
      </div>
    </AdminWidgetCard>
  );
};

function successOptions(
  response: { success: boolean; data?: { options?: RegistrationOption[] } }
): response is { success: true; data: { options: RegistrationOption[] } } {
  return Boolean(response.success && response.data?.options);
}

export default RegistrationOptionsPanel;
