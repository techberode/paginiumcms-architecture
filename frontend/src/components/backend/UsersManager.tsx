// frontend/src/components/backend/UsersManager.tsx
// === Správa používateľov (Iterácia 5, UI refresh 2.0.18) ===
import React, { useEffect, useMemo, useState } from 'react';
import {
  Users,
  Pencil,
  Mail,
  KeyRound,
  Eye,
  EyeOff,
  Check,
  Shield,
  FileArchive,
  UserX,
} from 'lucide-react';
import { User } from '../../api/types';
import {
  listUsers,
  createUser,
  updateUser,
  deleteUser,
  getUser,
  bulkDeleteUsers,
  uploadUserAvatar,
  removeUserAvatar,
  exportUserGdprZip,
  anonymizeUserGdpr,
  USER_ROLES,
  UserRole,
  CreateUserPayload,
  isStaffRole,
  deriveUsername,
} from '../../api/users';
import { validate, validatePasswordPolicy, validatePasswordConfirmation, ValidationErrors } from '../../utils/validation';
import { getValidationRulesFor } from '../../api/validation';
import { useToast } from '../../hooks/useToast';
import { useAuth } from '../../hooks/useAuth';
import { useI18n } from '../../context/I18nContext';
import { usePasswordPolicy } from '../../hooks/usePasswordPolicy';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { BulkActionBar } from './BulkActionBar';
import { AdminHintCard } from './AdminHintCard';
import { UserAvatarPicker } from './UserAvatarPicker';
import { summarizeBulkResult } from '../../types/bulk';

type FormState = CreateUserPayload & {
  password?: string;
  passwordConfirm?: string;
  active: boolean;
  twoFactorEnabled: boolean;
};

const emptyForm = (): FormState => ({
  email: '',
  username: '',
  name: '',
  role: 'USER',
  password: '',
  passwordConfirm: '',
  active: true,
  twoFactorEnabled: false,
});

export const UsersManager: React.FC = () => {
  const { t, locale } = useI18n();
  const passwordPolicy = usePasswordPolicy();
  const [users, setUsers] = useState<User[]>([]);
  const [requireTwoFactorStaff, setRequireTwoFactorStaff] = useState(true);
  const [actorIsSuperAdmin, setActorIsSuperAdmin] = useState(false);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editingAvatarUrl, setEditingAvatarUrl] = useState<string | null>(null);
  const [twoFactorEnforced, setTwoFactorEnforced] = useState(false);
  const [twoFactorSecret, setTwoFactorSecret] = useState<string | null>(null);
  const [showPassword, setShowPassword] = useState(false);
  const [gdprBusy, setGdprBusy] = useState(false);
  const [form, setForm] = useState<FormState>(emptyForm());
  const [errors, setErrors] = useState<ValidationErrors>({});
  const [userRules, setUserRules] = useState<Record<string, string[]>>({});
  const { success, error: toastError } = useToast();
  const { user: currentUser } = useAuth();

  useEffect(() => {
    void load();
    void getValidationRulesFor('user').then((set) => {
      if (set) setUserRules(set.rules);
    });
  }, []);

  const load = async () => {
    setLoading(true);
    try {
      const data = await listUsers();
      setUsers(data.users);
      setRequireTwoFactorStaff(data.meta?.require_two_factor_staff ?? true);
      setActorIsSuperAdmin(data.meta?.actor_is_super_admin ?? false);
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setForm(emptyForm());
    setEditingId(null);
    setEditingAvatarUrl(null);
    setTwoFactorEnforced(false);
    setTwoFactorSecret(null);
    setShowPassword(false);
    setErrors({});
  };

  const validateForm = (): boolean => {
    const rules = userRules.email ? userRules : {
      email: ['required', 'email', 'max:255'],
      username: ['required', 'string', 'min:2', 'max:64'],
      name: ['required', 'string', 'min:2', 'max:120'],
      role: ['required', 'in:USER,EDITOR,ADMIN,SUPER_ADMIN'],
    };

    const result = validate(
      { email: form.email, username: form.username, name: form.name, role: form.role },
      rules
    );

    const allErrors = { ...result.errors };

    if (!editingId || form.password) {
      const pw = form.password ?? '';
      const pwConfirm = form.passwordConfirm ?? '';
      if (!editingId && !pw) {
        allErrors.password = [t('users.validation.passwordRequired')];
      } else if (pw) {
        const pwErrors = validatePasswordPolicy(pw, passwordPolicy, locale);
        if (pwErrors.length) {
          allErrors.password = pwErrors;
        }
        const confirmErrors = validatePasswordConfirmation(pw, pwConfirm, locale);
        if (confirmErrors.length) {
          allErrors.passwordConfirm = confirmErrors;
        }
      }
    }

    setErrors(allErrors);
    return Object.keys(allErrors).length === 0;
  };

  const handleSave = async () => {
    if (!validateForm()) {
      toastError(t('users.toast.formInvalid'));
      return;
    }

    setSaving(true);
    setErrors({});
    try {
      if (editingId) {
        const res = await updateUser(editingId, {
          email: form.email,
          username: form.username,
          name: form.name,
          role: form.role as UserRole,
          active: form.active,
          twoFactorEnabled: form.twoFactorEnabled,
          ...(form.password ? { password: form.password, passwordConfirm: form.passwordConfirm } : {}),
        });
        if (res.success) {
          success(t('users.toast.updated'));
          resetForm();
          await load();
        } else if (res.errors) {
          setErrors(res.errors);
          toastError(res.error || t('users.toast.saveFailed'));
        } else {
          toastError(res.error || t('users.toast.saveFailed'));
        }
      } else {
        const res = await createUser(form);
        if (res.success) {
          success(t('users.toast.created'));
          resetForm();
          await load();
        } else if (res.errors) {
          setErrors(res.errors);
          toastError(res.error || t('users.toast.createFailed'));
        } else {
          toastError(res.error || t('users.toast.createFailed'));
        }
      }
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = async (user: User) => {
    setEditingId(user.id);
    setEditingAvatarUrl(user.avatarUrl ?? null);
    setForm({
      email: user.email,
      username: user.username ?? deriveUsername(user.email),
      name: user.name,
      role: (user.roles[0] as UserRole) || 'USER',
      password: '',
      passwordConfirm: '',
      active: user.active ?? true,
      twoFactorEnabled: user.twoFactorEnabled,
    });
    setErrors({});
    setShowPassword(false);

    const detail = await getUser(user.id);
    if (detail) {
      setTwoFactorEnforced(Boolean(detail.meta?.two_factor_enforced));
      setTwoFactorSecret(detail.user.twoFactorSecret ?? null);
      setForm((prev) => ({
        ...prev,
        twoFactorEnabled: detail.user.twoFactorEnabled,
        active: detail.user.active ?? true,
        username: detail.user.username ?? prev.username,
      }));
    }
  };

  const handleDelete = async (user: User) => {
    if (!confirm(t('users.confirm.delete', { email: user.email }))) return;
    const res = await deleteUser(user.id);
    if (res.success) {
      success(t('users.toast.deleted'));
      await load();
    } else {
      toastError(res.error || t('users.toast.deleteFailed'));
    }
  };

  const needsPasswordConfirm = !editingId || Boolean(form.password?.trim());

  const availableRoles = useMemo(
    () =>
      actorIsSuperAdmin
        ? USER_ROLES
        : USER_ROLES.filter((role) => role !== 'SUPER_ADMIN'),
    [actorIsSuperAdmin]
  );

  const handleRoleChange = (role: UserRole) => {
    const enforced = requireTwoFactorStaff && isStaffRole(role);
    setForm((prev) => ({
      ...prev,
      role,
      twoFactorEnabled: enforced ? true : prev.twoFactorEnabled,
    }));
    setTwoFactorEnforced(enforced);
  };

  const deletableUsers = users.filter((user) => user.id !== currentUser?.id);
  const bulkSelection = useBulkSelection(
    deletableUsers.map((user) => user.id),
    users.map((user) => user.id).join('\0')
  );

  const handleBulkDelete = async () => {
    if (bulkSelection.count === 0) return;
    if (!confirm(t('users.confirm.bulkDelete', { count: String(bulkSelection.count) }))) return;
    const result = await bulkDeleteUsers(bulkSelection.selectedIds);
    if (result) {
      success(summarizeBulkResult(result, t));
      bulkSelection.clear();
      await load();
    } else {
      toastError(t('users.toast.bulkFailed'));
    }
  };

  const enforced =
    twoFactorEnforced || (requireTwoFactorStaff && isStaffRole(form.role as UserRole));

  const isAnonymizedAccount = form.email.toLowerCase().endsWith('@anonymized.invalid');

  const handleGdprExport = async () => {
    if (!editingId) return;
    setGdprBusy(true);
    try {
      const result = await exportUserGdprZip(editingId);
      if (!result.ok) {
        toastError(result.error || t('users.gdpr.exportFailed'));
        return;
      }
      const url = URL.createObjectURL(result.blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = `gdpr-export-${editingId}.zip`;
      anchor.click();
      URL.revokeObjectURL(url);
      success(t('users.gdpr.exportSuccess'));
    } finally {
      setGdprBusy(false);
    }
  };

  const handleGdprAnonymize = async () => {
    if (!editingId) return;
    if (currentUser?.id === editingId) {
      toastError(t('users.gdpr.selfBlocked'));
      return;
    }
    if (isAnonymizedAccount) {
      toastError(t('users.gdpr.alreadyAnonymized'));
      return;
    }
    if (!confirm(t('users.gdpr.anonymizeConfirm', { email: form.email }))) return;

    setGdprBusy(true);
    try {
      const res = await anonymizeUserGdpr(editingId);
      if (res.success) {
        success(t('users.gdpr.anonymizeSuccess'));
        resetForm();
        await load();
      } else {
        toastError(res.error || t('users.gdpr.anonymizeFailed'));
      }
    } finally {
      setGdprBusy(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center py-16">
        <div className="animate-spin h-10 w-10 border-b-2 border-indigo-600 rounded-full" />
      </div>
    );
  }

  return (
    <div className="p-6 lg:p-10 max-w-6xl mx-auto space-y-8">
      <header>
        <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
          <span className="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-300">
            <Users size={22} />
          </span>
          {t('users.page.title')}
        </h1>
        <p className="text-sm text-slate-500 mt-2 max-w-2xl">{t('users.page.subtitle')}</p>
        {actorIsSuperAdmin && (
          <div className="mt-4">
            <AdminHintCard tone="info" title={t('users.superAdmin.badge')}>
              {t('users.superAdmin.hint')}
            </AdminHintCard>
          </div>
        )}
      </header>

      <section className="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
          <Pencil size={18} className="text-indigo-500" />
          <h2 className="font-bold text-slate-900 dark:text-white">
            {editingId ? t('users.form.editTitle') : t('users.form.createTitle')}
          </h2>
        </div>

        <div className="p-6 space-y-6">
          {editingId && (
            <UserAvatarPicker
              name={form.name || form.email}
              avatarUrl={editingAvatarUrl}
              onUpload={async (file) => {
                const res = await uploadUserAvatar(editingId, file);
                if (!res.success || !res.data?.user) {
                  toastError(res.error || t('users.avatar.failed'));
                  return;
                }
                setEditingAvatarUrl(res.data.user.avatarUrl ?? null);
                success(t('users.avatar.success'));
                await load();
              }}
              onRemove={async () => {
                const res = await removeUserAvatar(editingId);
                if (!res.success) {
                  toastError(res.error || t('users.avatar.failed'));
                  return;
                }
                setEditingAvatarUrl(null);
                success(t('users.avatar.removed'));
                await load();
              }}
            />
          )}

          <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
            <Field label={t('users.form.username')} error={errors.username?.[0]}>
              <input
                className={inputClass(Boolean(errors.username))}
                value={form.username}
                onChange={(e) => setForm((f) => ({ ...f, username: e.target.value.toLowerCase() }))}
                onBlur={() => {
                  if (!form.username && form.email) {
                    setForm((f) => ({ ...f, username: deriveUsername(f.email) }));
                  }
                }}
              />
            </Field>

            <Field label={t('users.form.displayName')} error={errors.name?.[0]}>
              <input
                className={inputClass(Boolean(errors.name))}
                value={form.name}
                onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
              />
            </Field>

            <Field label={t('users.form.email')} error={errors.email?.[0]}>
              <div className="relative">
                <Mail size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                  type="email"
                  className={`${inputClass(Boolean(errors.email))} pl-10`}
                  value={form.email}
                  onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                />
              </div>
            </Field>

            <Field
              label={editingId ? t('users.form.passwordNew') : t('users.form.passwordRequired')}
              error={errors.password?.[0]}
            >
              <div className="relative">
                <KeyRound size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                  type={showPassword ? 'text' : 'password'}
                  placeholder={editingId ? t('users.form.passwordPlaceholder') : ''}
                  className={`${inputClass(Boolean(errors.password))} pl-10 pr-10`}
                  value={form.password ?? ''}
                  onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
                />
                <button
                  type="button"
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                  onClick={() => setShowPassword((v) => !v)}
                  aria-label={showPassword ? t('users.form.hidePassword') : t('users.form.showPassword')}
                >
                  {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                </button>
              </div>
            </Field>

            {needsPasswordConfirm ? (
              <Field label={t('users.form.passwordConfirm')} error={errors.passwordConfirm?.[0]}>
                <div className="relative">
                  <KeyRound size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                  <input
                    type={showPassword ? 'text' : 'password'}
                    className={`${inputClass(Boolean(errors.passwordConfirm))} pl-10`}
                    value={form.passwordConfirm ?? ''}
                    onChange={(e) => setForm((f) => ({ ...f, passwordConfirm: e.target.value }))}
                    autoComplete="new-password"
                  />
                </div>
              </Field>
            ) : null}

            <Field label={t('users.form.role')}>
              <select
                className={inputClass(false)}
                value={form.role}
                onChange={(e) => handleRoleChange(e.target.value as UserRole)}
              >
                {availableRoles.map((r) => (
                  <option key={r} value={r}>
                    {t(`users.roles.${r}`)}
                  </option>
                ))}
              </select>
            </Field>

            <Field label={t('users.form.status')}>
              <select
                className={inputClass(false)}
                value={form.active ? 'active' : 'inactive'}
                onChange={(e) => setForm((f) => ({ ...f, active: e.target.value === 'active' }))}
              >
                <option value="active">{t('users.form.statusActive')}</option>
                <option value="inactive">{t('users.form.statusInactive')}</option>
              </select>
            </Field>
          </div>

          <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-950/50 p-5 space-y-3">
            <label className="flex items-start gap-3 cursor-pointer">
              <input
                type="checkbox"
                checked={form.twoFactorEnabled || enforced}
                disabled={enforced}
                onChange={(e) => setForm((f) => ({ ...f, twoFactorEnabled: e.target.checked }))}
                className="mt-1 rounded"
              />
              <span>
                <span className="font-semibold text-slate-900 dark:text-white flex items-center gap-2 flex-wrap">
                  <Shield size={16} className="text-indigo-500" />
                  {t('users.form.twoFactor')}
                  {enforced && (
                    <span className="text-xs font-bold text-rose-600">{t('users.form.twoFactorEnforced')}</span>
                  )}
                </span>
                <span className="block text-sm text-slate-500 mt-1">{t('users.form.twoFactorHint')}</span>
              </span>
            </label>

            {editingId && twoFactorSecret && form.twoFactorEnabled && actorIsSuperAdmin && (
              <div className="flex items-center gap-3 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-100 dark:border-sky-900 px-4 py-3 text-sm">
                <KeyRound size={18} className="text-sky-600 shrink-0" />
                <span className="text-slate-600 dark:text-slate-300">
                  {t('users.form.secretLabel')}{' '}
                  <code className="ml-1 px-2 py-1 rounded-lg bg-white dark:bg-slate-900 border border-sky-100 dark:border-sky-800 font-mono text-slate-900 dark:text-white">
                    {twoFactorSecret}
                  </code>
                </span>
              </div>
            )}
          </div>

          {editingId && (
            <div className="rounded-2xl border border-amber-200 dark:border-amber-900/60 bg-amber-50/70 dark:bg-amber-950/20 p-5 space-y-4">
              <div>
                <h3 className="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                  <Shield size={16} className="text-amber-600" />
                  {t('users.gdpr.title')}
                </h3>
                <p className="text-sm text-slate-600 dark:text-slate-400 mt-1">{t('users.gdpr.hint')}</p>
              </div>
              <div className="flex flex-wrap gap-3">
                <button
                  type="button"
                  disabled={gdprBusy}
                  onClick={() => void handleGdprExport()}
                  className="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-amber-300 dark:border-amber-800 text-sm font-semibold text-amber-900 dark:text-amber-200 disabled:opacity-50"
                >
                  <FileArchive size={16} />
                  {t('users.gdpr.exportZip')}
                </button>
                {currentUser?.id !== editingId && !isAnonymizedAccount && (
                  <button
                    type="button"
                    disabled={gdprBusy}
                    onClick={() => void handleGdprAnonymize()}
                    className="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-rose-300 dark:border-rose-800 text-sm font-semibold text-rose-700 dark:text-rose-300 disabled:opacity-50"
                  >
                    <UserX size={16} />
                    {t('users.gdpr.anonymize')}
                  </button>
                )}
              </div>
            </div>
          )}

          <div className="flex flex-wrap gap-3 pt-2">
            <button
              type="button"
              onClick={() => void handleSave()}
              disabled={saving}
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-sm font-bold shadow-lg shadow-indigo-500/20 disabled:opacity-50"
            >
              <Check size={16} />
              {saving ? t('users.form.saving') : editingId ? t('users.form.save') : t('users.form.create')}
            </button>
            {editingId && (
              <button
                type="button"
                onClick={resetForm}
                className="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-300"
              >
                {t('users.form.cancel')}
              </button>
            )}
          </div>
        </div>
      </section>

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel={t('users.bulk.itemLabel')}
        onClear={bulkSelection.clear}
        actions={[
          {
            id: 'delete',
            label: t('users.bulk.delete'),
            variant: 'danger',
            onClick: () => void handleBulkDelete(),
          },
        ]}
      />

      <div className="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-x-auto">
        <table className="min-w-full text-sm">
          <thead>
            <tr className="border-b border-slate-100 dark:border-slate-800 text-left text-xs uppercase tracking-wider text-slate-400">
              <th className="p-4 w-10">
                <input
                  type="checkbox"
                  checked={bulkSelection.allSelected && deletableUsers.length > 0}
                  onChange={bulkSelection.toggleAll}
                  aria-label={t('users.table.selectAll')}
                />
              </th>
              <th className="p-4">{t('users.table.user')}</th>
              <th className="p-4">{t('users.table.email')}</th>
              <th className="p-4">{t('users.table.role')}</th>
              <th className="p-4">{t('users.table.status')}</th>
              <th className="p-4">{t('users.table.twoFactor')}</th>
              <th className="p-4">{t('users.table.actions')}</th>
            </tr>
          </thead>
          <tbody>
            {users.map((u) => (
              <tr key={u.id} className="border-b border-slate-50 dark:border-slate-800/80 hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                <td className="p-4">
                  {currentUser?.id !== u.id ? (
                    <input
                      type="checkbox"
                      checked={bulkSelection.isSelected(u.id)}
                      onChange={() => bulkSelection.toggle(u.id)}
                      aria-label={t('users.table.selectUser', { email: u.email })}
                    />
                  ) : null}
                </td>
                <td className="p-4">
                  <div className="flex items-center gap-3">
                    {u.avatarUrl ? (
                      <img src={u.avatarUrl} alt={u.name} className="h-10 w-10 rounded-xl object-cover" />
                    ) : (
                      <div className="h-10 w-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                        <Users size={16} />
                      </div>
                    )}
                    <div>
                      <div className="font-semibold text-slate-900 dark:text-white">{u.name}</div>
                      <div className="text-xs font-mono text-slate-400">{u.username ?? deriveUsername(u.email)}</div>
                    </div>
                  </div>
                </td>
                <td className="p-4">{u.email}</td>
                <td className="p-4">{t(`users.roles.${u.roles[0] as UserRole}`)}</td>
                <td className="p-4">
                  {(u.active ?? true) ? (
                    <span className="text-emerald-600 font-medium">{t('users.table.active')}</span>
                  ) : (
                    <span className="text-amber-600 font-medium">{t('users.table.inactive')}</span>
                  )}
                </td>
                <td className="p-4">{u.twoFactorEnabled ? t('users.table.twoFactorOn') : t('users.table.twoFactorOff')}</td>
                <td className="p-4 space-x-3">
                  <button
                    type="button"
                    onClick={() => void handleEdit(u)}
                    className="text-indigo-600 font-semibold hover:underline"
                  >
                    {t('users.table.edit')}
                  </button>
                  {currentUser?.id !== u.id && (
                    <button
                      type="button"
                      onClick={() => void handleDelete(u)}
                      className="text-rose-600 font-semibold hover:underline"
                    >
                      {t('users.table.delete')}
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

function Field({
  label,
  error,
  children,
}: {
  label: string;
  error?: string;
  children: React.ReactNode;
}) {
  return (
    <div>
      <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
        {label}
      </label>
      {children}
      {error && <p className="text-xs text-rose-600 mt-1">{error}</p>}
    </div>
  );
}

function inputClass(hasError: boolean): string {
  return `w-full rounded-xl border px-3 py-2.5 text-sm bg-white dark:bg-slate-950 ${
    hasError
      ? 'border-rose-400 focus:ring-rose-200'
      : 'border-slate-200 dark:border-slate-700 focus:border-indigo-400 focus:ring-indigo-100'
  } outline-none transition`;
}

export default UsersManager;
