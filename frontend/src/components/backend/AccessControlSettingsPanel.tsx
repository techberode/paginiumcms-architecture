import React from 'react';
import { Link } from 'react-router-dom';
import { Lock, Plus, Trash2 } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { AdminHintCard } from './AdminHintCard';
import { SettingFieldLabel } from './SettingHelpTooltip';
import { translateSettingFieldTooltip } from '../../i18n/modules/settings/helpers';

export interface PathAclRule {
  id: string;
  path: string;
  roles: string[];
  permissions: string[];
  enabled: boolean;
}

const ROLE_KEYS: Record<string, string> = {
  ADMIN: 'permissionsAdmin',
  EDITOR: 'permissionsEditor',
  USER: 'permissionsUser',
};

const emptyRule = (): PathAclRule => ({
  id: `acl_${Date.now()}`,
  path: 'content/pages/*',
  roles: ['EDITOR'],
  permissions: [],
  enabled: true,
});

function parseRules(raw: unknown): PathAclRule[] {
  if (typeof raw !== 'string' || raw.trim() === '') {
    return [];
  }
  try {
    const parsed = JSON.parse(raw) as unknown;
    if (!Array.isArray(parsed)) {
      return [];
    }
    return parsed
      .filter((item): item is PathAclRule => typeof item === 'object' && item !== null)
      .map((item) => ({
        id: String(item.id ?? `acl_${Math.random().toString(36).slice(2)}`),
        path: String(item.path ?? ''),
        roles: Array.isArray(item.roles) ? item.roles.map(String) : [],
        permissions: Array.isArray(item.permissions) ? item.permissions.map(String) : [],
        enabled: item.enabled !== false,
      }));
  } catch {
    return [];
  }
}

function parsePermissions(raw: unknown): string[] {
  if (typeof raw !== 'string' || raw.trim() === '') {
    return [];
  }
  return raw
    .split(',')
    .map((part) => part.trim())
    .filter(Boolean);
}

interface AccessControlSettingsPanelProps {
  permissionsCatalog: string[];
  watch: (name: string) => unknown;
  setValue: (name: string, value: unknown, options?: { shouldDirty?: boolean; shouldValidate?: boolean }) => void;
}

export const AccessControlSettingsPanel: React.FC<AccessControlSettingsPanelProps> = ({
  permissionsCatalog,
  watch,
  setValue,
}) => {
  const { t } = useI18n();
  const pathAclEnabled = Boolean(watch('pathAclEnabled'));
  const rules = parseRules(watch('pathAclRulesJson'));
  const displayRules = rules.length > 0 ? rules : [emptyRule()];

  const setRules = (next: PathAclRule[]) => {
    setValue('pathAclRulesJson', JSON.stringify(next), { shouldDirty: true, shouldValidate: true });
  };

  const togglePermission = (role: keyof typeof ROLE_KEYS, permission: string) => {
    const key = ROLE_KEYS[role];
    const current = parsePermissions(watch(key));
    const next = current.includes(permission)
      ? current.filter((entry) => entry !== permission)
      : [...current, permission];
    setValue(key, next.join(','), { shouldDirty: true, shouldValidate: true });
  };

  const rolePermissions = (role: keyof typeof ROLE_KEYS): string[] =>
    parsePermissions(watch(ROLE_KEYS[role]));

  return (
    <div className="space-y-6">
      <AdminHintCard tone="info" title={t('settings.accessControl.superAdminTitle')}>
        {t('settings.accessControl.superAdminHint')}{' '}
        <Link to="/security/roles" className="text-indigo-600 font-semibold underline">
          {t('settings.accessControl.rolesManagerLink')}
        </Link>
        .
      </AdminHintCard>

      <section className="space-y-4">
        <h3 className="text-sm font-bold uppercase tracking-wide text-slate-500">
          {t('settings.accessControl.rolesTitle')}
        </h3>

        {(['ADMIN', 'EDITOR', 'USER'] as const).map((role) => (
          <div key={role} className="rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-3">
            <div className="flex items-center gap-2">
              <Lock className="w-4 h-4 text-indigo-500" />
              <h4 className="text-sm font-black text-slate-900 dark:text-white">
                {t(`users.roles.${role}`)}
              </h4>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
              {permissionsCatalog.map((permission) => (
                <label key={`${role}-${permission}`} className="inline-flex items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={rolePermissions(role).includes(permission)}
                    onChange={() => togglePermission(role, permission)}
                    className="h-4 w-4 rounded border-gray-300 text-indigo-600"
                  />
                  <span>{t(`settings.accessControl.permissions.${permission}`)}</span>
                </label>
              ))}
            </div>
          </div>
        ))}
      </section>

      <section className="space-y-4">
        <div className="flex items-center justify-between gap-3">
          <h3 className="text-sm font-bold uppercase tracking-wide text-slate-500">
            {t('settings.accessControl.pathAclTitle')}
          </h3>
          <div className="inline-flex items-center gap-2 text-sm font-semibold">
            <input
              type="checkbox"
              id="setting-pathAclEnabled"
              checked={pathAclEnabled}
              onChange={(event) =>
                setValue('pathAclEnabled', event.target.checked, { shouldDirty: true, shouldValidate: true })
              }
              className="h-4 w-4 rounded border-gray-300 text-indigo-600 shrink-0"
            />
            <SettingFieldLabel
              htmlFor="setting-pathAclEnabled"
              label={t('settings.accessControl.pathAclEnabled')}
              tooltip={translateSettingFieldTooltip(t, 'accessControl', 'pathAclEnabled')}
            />
          </div>
        </div>

        <p className="text-xs text-slate-500">{t('settings.accessControl.pathAclHint')}</p>

        <div className="space-y-4">
          {displayRules.map((rule, index) => (
            <div key={rule.id} className="rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label className="block text-xs font-bold uppercase text-slate-500">
                  {t('platform.acl.path')}
                  <input
                    className="mt-1 w-full rounded-xl border px-3 py-2 text-sm dark:bg-slate-900"
                    value={rule.path}
                    onChange={(event) => {
                      const next = [...displayRules];
                      next[index] = { ...rule, path: event.target.value };
                      setRules(next);
                    }}
                  />
                </label>
                <label className="block text-xs font-bold uppercase text-slate-500">
                  {t('platform.acl.roles')}
                  <input
                    className="mt-1 w-full rounded-xl border px-3 py-2 text-sm dark:bg-slate-900"
                    value={rule.roles.join(', ')}
                    onChange={(event) => {
                      const next = [...displayRules];
                      next[index] = {
                        ...rule,
                        roles: event.target.value.split(',').map((entry) => entry.trim()).filter(Boolean),
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
                    onChange={(event) => {
                      const next = [...displayRules];
                      next[index] = { ...rule, enabled: event.target.checked };
                      setRules(next);
                    }}
                  />
                  {t('platform.acl.active')}
                </label>
                <button
                  type="button"
                  onClick={() => setRules(displayRules.filter((_, i) => i !== index))}
                  className="text-rose-500 text-sm inline-flex items-center gap-1"
                >
                  <Trash2 className="w-4 h-4" />
                  {t('platform.acl.remove')}
                </button>
              </div>
            </div>
          ))}
        </div>

        <button
          type="button"
          onClick={() => setRules([...displayRules, emptyRule()])}
          className="inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-bold"
        >
          <Plus className="w-4 h-4" />
          {t('platform.acl.addRule')}
        </button>
      </section>
    </div>
  );
};

export default AccessControlSettingsPanel;
