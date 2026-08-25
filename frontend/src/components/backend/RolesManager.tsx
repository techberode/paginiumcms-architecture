import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { Lock, Plus, RefreshCw, Shield, Trash2 } from 'lucide-react';
import { rolesApi, type CustomRole, isValidRoleId, normalizeRoleId } from '../../api/roles';
import { useAuth } from '../../hooks/useAuth';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useI18n } from '../../context/I18nContext';
import { AdminHintCard } from './AdminHintCard';
import { BulkActionBar } from './BulkActionBar';
import { summarizeBulkResult } from '../../types/bulk';

function roleLabel(role: CustomRole, t: (key: string) => string): string {
  const known = t(`users.roles.${role.id}`);
  if (known !== `users.roles.${role.id}`) {
    return known;
  }

  return role.name || role.id;
}

export const RolesManager: React.FC = () => {
  const { t } = useI18n();
  const { user } = useAuth();
  const toast = useToast();
  const isSuperAdmin = user?.roles?.includes('SUPER_ADMIN') ?? false;
  const [loading, setLoading] = useState(true);
  const [roles, setRoles] = useState<CustomRole[]>([]);
  const [permissionsCatalog, setPermissionsCatalog] = useState<string[]>([]);
  const [showCreate, setShowCreate] = useState(false);
  const [newId, setNewId] = useState('');
  const [newName, setNewName] = useState('');
  const [newPermissions, setNewPermissions] = useState<string[]>([]);
  const [idTouched, setIdTouched] = useState(false);
  const [creating, setCreating] = useState(false);
  const [busyId, setBusyId] = useState<string | null>(null);
  const [drafts, setDrafts] = useState<Record<string, { name: string; permissions: string[] }>>({});

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await rolesApi.list();
      setRoles(data.roles);
      setPermissionsCatalog(data.permissions);
      setDrafts(
        Object.fromEntries(
          data.roles.map((role) => [role.id, { name: role.name, permissions: [...role.permissions] }])
        )
      );
    } catch {
      toast.error(t('platform.roles.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  useEffect(() => {
    void load();
  }, [load]);

  const deletableRoleIds = useMemo(
    () => roles.filter((role) => !role.system).map((role) => role.id),
    [roles]
  );
  const bulkSelection = useBulkSelection(deletableRoleIds, String(roles.length));

  const suggestedId = useMemo(() => normalizeRoleId(newName), [newName]);

  const toggleNewPermission = (permission: string) => {
    setNewPermissions((current) =>
      current.includes(permission) ? current.filter((entry) => entry !== permission) : [...current, permission]
    );
  };

  const toggleDraftPermission = (roleId: string, permission: string) => {
    setDrafts((current) => {
      const draft = current[roleId];
      if (!draft) {
        return current;
      }
      const nextPermissions = draft.permissions.includes(permission)
        ? draft.permissions.filter((entry) => entry !== permission)
        : [...draft.permissions, permission];
      return { ...current, [roleId]: { ...draft, permissions: nextPermissions } };
    });
  };

  const handleCreate = async () => {
    const id = (idTouched ? newId : suggestedId).trim().toUpperCase();
    const name = newName.trim();

    if (name === '') {
      toast.error(t('platform.roles.toast.nameRequired'));
      return;
    }

    if (!isValidRoleId(id) || id === 'SUPER_ADMIN') {
      toast.error(t('platform.roles.toast.idInvalid'));
      return;
    }

    if (newPermissions.length === 0) {
      toast.error(t('platform.roles.toast.permissionsRequired'));
      return;
    }

    setCreating(true);
    try {
      const created = await rolesApi.create({ id, name, permissions: newPermissions });
      if (!created) {
        toast.error(t('platform.roles.toast.createFailed'));
        return;
      }

      toast.success(t('platform.roles.toast.created'));
      setShowCreate(false);
      setNewId('');
      setNewName('');
      setNewPermissions([]);
      setIdTouched(false);
      await load();
    } finally {
      setCreating(false);
    }
  };

  const handleSave = async (role: CustomRole) => {
    const draft = drafts[role.id];
    if (!draft) {
      return;
    }

    const name = draft.name.trim();
    if (name === '') {
      toast.error(t('platform.roles.toast.nameRequired'));
      return;
    }

    if (draft.permissions.length === 0) {
      toast.error(t('platform.roles.toast.permissionsRequired'));
      return;
    }

    setBusyId(role.id);
    try {
      const updated = await rolesApi.update(role.id, {
        name,
        permissions: draft.permissions,
      });
      if (!updated) {
        toast.error(t('platform.roles.toast.updateFailed'));
        return;
      }

      toast.success(t('platform.roles.toast.updated'));
      await load();
    } finally {
      setBusyId(null);
    }
  };

  const handleDelete = async (role: CustomRole) => {
    if (role.system) {
      return;
    }

    if (!confirm(t('platform.roles.confirmDelete', { id: role.id }))) {
      return;
    }

    setBusyId(role.id);
    try {
      const ok = await rolesApi.remove(role.id);
      if (!ok) {
        toast.error(t('platform.roles.toast.deleteFailed'));
        return;
      }

      toast.success(t('platform.roles.toast.deleted'));
      await load();
    } finally {
      setBusyId(null);
    }
  };

  const handleBulkDelete = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(t('platform.roles.confirmBulkDelete', { count: String(bulkSelection.count) }))) {
      return;
    }

    setBusyId('bulk');
    try {
      const result = await rolesApi.bulkDelete(bulkSelection.selectedIds);
      if (!result) {
        toast.error(t('platform.roles.toast.bulkDeleteFailed'));
        return;
      }
      toast.success(summarizeBulkResult(result, t));
      bulkSelection.clear();
      await load();
    } finally {
      setBusyId(null);
    }
  };

  if (!isSuperAdmin) {
    return (
      <div className="p-6 text-sm text-slate-600">
        {t('platform.roles.superAdminOnly')}
      </div>
    );
  }

  return (
    <div className="max-w-5xl mx-auto p-6 space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <Shield className="w-7 h-7 text-indigo-500" />
            {t('platform.roles.title')}
          </h1>
          <p className="text-sm text-slate-500 mt-1">{t('platform.roles.subtitle')}</p>
        </div>
        <div className="flex gap-2">
          <button
            type="button"
            onClick={() => void load()}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-bold"
          >
            <RefreshCw className="w-4 h-4" />
            {t('platform.roles.refresh')}
          </button>
          <button
            type="button"
            onClick={() => setShowCreate((value) => !value)}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
          >
            <Plus className="w-4 h-4" />
            {t('platform.roles.create')}
          </button>
        </div>
      </div>

      <AdminHintCard tone="info" title={t('platform.roles.hintTitle')}>
        {t('platform.roles.hint')}{' '}
        <Link to="/settings?category=security&group=accessControl" className="text-indigo-600 font-semibold underline">
          {t('platform.roles.settingsLink')}
        </Link>
        .
      </AdminHintCard>

      {showCreate && (
        <section className="rounded-2xl border border-indigo-200 dark:border-indigo-800 p-5 space-y-4 bg-indigo-50/40 dark:bg-indigo-950/20">
          <h2 className="text-sm font-black uppercase tracking-wide text-indigo-700 dark:text-indigo-300">
            {t('platform.roles.createTitle')}
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label className="block text-xs font-bold uppercase text-slate-500">
              {t('platform.roles.name')}
              <input
                className="mt-1 w-full rounded-xl border px-3 py-2 text-sm dark:bg-slate-900"
                value={newName}
                onChange={(event) => setNewName(event.target.value)}
                placeholder={t('platform.roles.namePlaceholder')}
              />
            </label>
            <label className="block text-xs font-bold uppercase text-slate-500">
              {t('platform.roles.id')}
              <input
                className="mt-1 w-full rounded-xl border px-3 py-2 text-sm font-mono dark:bg-slate-900"
                value={idTouched ? newId : suggestedId}
                onChange={(event) => {
                  setIdTouched(true);
                  setNewId(event.target.value.toUpperCase());
                }}
                placeholder={t('platform.roles.idPlaceholder')}
              />
            </label>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
            {permissionsCatalog.map((permission) => (
              <label key={`new-${permission}`} className="inline-flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={newPermissions.includes(permission)}
                  onChange={() => toggleNewPermission(permission)}
                  className="h-4 w-4 rounded border-gray-300 text-indigo-600"
                />
                <span>{t(`settings.accessControl.permissions.${permission}`)}</span>
              </label>
            ))}
          </div>
          <div className="flex gap-2">
            <button
              type="button"
              disabled={creating}
              onClick={() => void handleCreate()}
              className="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold disabled:opacity-60"
            >
              {creating ? t('platform.roles.saving') : t('platform.roles.save')}
            </button>
            <button
              type="button"
              onClick={() => {
                setShowCreate(false);
                setNewId('');
                setNewName('');
                setNewPermissions([]);
                setIdTouched(false);
              }}
              className="px-4 py-2 rounded-xl border text-sm font-bold"
            >
              {t('platform.roles.cancel')}
            </button>
          </div>
        </section>
      )}

      {loading ? (
        <p className="text-sm text-slate-500">{t('platform.roles.loading')}</p>
      ) : roles.length === 0 ? (
        <p className="text-sm text-slate-500">{t('platform.roles.empty')}</p>
      ) : (
        <div className="space-y-4">
          <BulkActionBar
            count={bulkSelection.count}
            itemLabel={t('platform.roles.bulkItemLabel')}
            onClear={bulkSelection.clear}
            actions={[
              {
                id: 'delete',
                label: t('platform.roles.bulkDelete'),
                variant: 'danger',
                disabled: busyId === 'bulk',
                onClick: () => void handleBulkDelete(),
              },
            ]}
          />
          {deletableRoleIds.length > 0 && (
            <label className="inline-flex items-center gap-2 text-sm font-semibold">
              <input
                type="checkbox"
                checked={bulkSelection.allSelected}
                onChange={() => bulkSelection.toggleAll()}
                aria-label={t('platform.roles.bulkDelete')}
              />
              {t('platform.roles.bulkDelete')}
            </label>
          )}
          {roles.map((role) => {
            const draft = drafts[role.id] ?? { name: role.name, permissions: role.permissions };
            const dirty =
              draft.name !== role.name ||
              draft.permissions.length !== role.permissions.length ||
              draft.permissions.some((entry) => !role.permissions.includes(entry));

            return (
              <section
                key={role.id}
                className="rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-4"
              >
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <div className="flex items-center gap-2">
                    {!role.system && (
                      <input
                        type="checkbox"
                        checked={bulkSelection.isSelected(role.id)}
                        onChange={() => bulkSelection.toggle(role.id)}
                        aria-label={role.id}
                        className="h-4 w-4 rounded border-gray-300 text-indigo-600"
                      />
                    )}
                    <Lock className="w-4 h-4 text-indigo-500" />
                    <div>
                      <h3 className="text-sm font-black text-slate-900 dark:text-white">
                        {roleLabel(role, t)}
                        <span className="ml-2 font-mono text-xs text-slate-400">{role.id}</span>
                      </h3>
                      {role.system && (
                        <span className="text-xs font-bold uppercase text-amber-600">{t('platform.roles.systemBadge')}</span>
                      )}
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <button
                      type="button"
                      disabled={!dirty || busyId === role.id}
                      onClick={() => void handleSave(role)}
                      className="px-3 py-1.5 rounded-xl bg-indigo-600 text-white text-xs font-bold disabled:opacity-50"
                    >
                      {busyId === role.id ? t('platform.roles.saving') : t('platform.roles.save')}
                    </button>
                    {!role.system && (
                      <button
                        type="button"
                        disabled={busyId === role.id}
                        onClick={() => void handleDelete(role)}
                        className="px-3 py-1.5 rounded-xl border border-rose-200 text-rose-600 text-xs font-bold inline-flex items-center gap-1"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                        {t('platform.roles.delete')}
                      </button>
                    )}
                  </div>
                </div>

                <label className="block text-xs font-bold uppercase text-slate-500 max-w-md">
                  {t('platform.roles.displayName')}
                  <input
                    className="mt-1 w-full rounded-xl border px-3 py-2 text-sm dark:bg-slate-900"
                    value={draft.name}
                    onChange={(event) =>
                      setDrafts((current) => ({
                        ...current,
                        [role.id]: { ...draft, name: event.target.value },
                      }))
                    }
                  />
                </label>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                  {permissionsCatalog.map((permission) => (
                    <label key={`${role.id}-${permission}`} className="inline-flex items-center gap-2 text-sm">
                      <input
                        type="checkbox"
                        checked={draft.permissions.includes(permission)}
                        onChange={() => toggleDraftPermission(role.id, permission)}
                        className="h-4 w-4 rounded border-gray-300 text-indigo-600"
                      />
                      <span>{t(`settings.accessControl.permissions.${permission}`)}</span>
                    </label>
                  ))}
                </div>
              </section>
            );
          })}
        </div>
      )}
    </div>
  );
};

export default RolesManager;
