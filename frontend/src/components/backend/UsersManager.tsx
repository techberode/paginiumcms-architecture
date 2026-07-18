// frontend/src/components/backend/UsersManager.tsx
// === Správa používateľov (Iterácia 5, UI refresh 2.0.18) ===
import React, { useEffect, useState } from 'react';
import {
  Users,
  Pencil,
  Mail,
  KeyRound,
  Eye,
  EyeOff,
  Check,
  Shield,
} from 'lucide-react';
import { User } from '../../api/types';
import {
  listUsers,
  createUser,
  updateUser,
  deleteUser,
  getUser,
  bulkDeleteUsers,
  USER_ROLES,
  USER_ROLE_LABELS,
  UserRole,
  CreateUserPayload,
  isStaffRole,
  deriveUsername,
} from '../../api/users';
import { validate, validatePasswordPolicy, ValidationErrors } from '../../utils/validation';
import { getValidationRulesFor } from '../../api/validation';
import { useToast } from '../../hooks/useToast';
import { useAuth } from '../../hooks/useAuth';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { BulkActionBar } from './BulkActionBar';
import { summarizeBulkResult } from '../../types/bulk';

type FormState = CreateUserPayload & {
  password?: string;
  active: boolean;
  twoFactorEnabled: boolean;
};

const emptyForm = (): FormState => ({
  email: '',
  username: '',
  name: '',
  role: 'USER',
  password: '',
  active: true,
  twoFactorEnabled: false,
});

export const UsersManager: React.FC = () => {
  const [users, setUsers] = useState<User[]>([]);
  const [requireTwoFactorStaff, setRequireTwoFactorStaff] = useState(true);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [twoFactorEnforced, setTwoFactorEnforced] = useState(false);
  const [twoFactorSecret, setTwoFactorSecret] = useState<string | null>(null);
  const [showPassword, setShowPassword] = useState(false);
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
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setForm(emptyForm());
    setEditingId(null);
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
      if (!editingId && !pw) {
        allErrors.password = ['Heslo je povinné pri vytváraní používateľa.'];
      } else if (pw) {
        const pwErrors = validatePasswordPolicy(pw);
        if (pwErrors.length) allErrors.password = pwErrors;
      }
    }

    setErrors(allErrors);
    return Object.keys(allErrors).length === 0;
  };

  const handleSave = async () => {
    if (!validateForm()) {
      toastError('Skontrolujte formulár');
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
          ...(form.password ? { password: form.password } : {}),
        });
        if (res.success) {
          success('Používateľ aktualizovaný');
          resetForm();
          await load();
        } else if (res.errors) {
          setErrors(res.errors);
          toastError(res.error || 'Validácia zlyhala');
        } else {
          toastError(res.error || 'Uloženie zlyhalo');
        }
      } else {
        const res = await createUser(form);
        if (res.success) {
          success('Používateľ vytvorený');
          resetForm();
          await load();
        } else if (res.errors) {
          setErrors(res.errors);
          toastError(res.error || 'Validácia zlyhala');
        } else {
          toastError(res.error || 'Vytvorenie zlyhalo');
        }
      }
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = async (user: User) => {
    setEditingId(user.id);
    setForm({
      email: user.email,
      username: user.username ?? deriveUsername(user.email),
      name: user.name,
      role: (user.roles[0] as UserRole) || 'USER',
      password: '',
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
    if (!confirm(`Zmazať používateľa ${user.email}?`)) return;
    const res = await deleteUser(user.id);
    if (res.success) {
      success('Používateľ zmazaný');
      await load();
    } else {
      toastError(res.error || 'Zmazanie zlyhalo');
    }
  };

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
    if (!confirm(`Zmazať ${bulkSelection.count} vybraných používateľov?`)) return;
    const result = await bulkDeleteUsers(bulkSelection.selectedIds);
    if (result) {
      success(summarizeBulkResult(result));
      bulkSelection.clear();
      await load();
    } else {
      toastError('Hromadné mazanie zlyhalo');
    }
  };

  const enforced =
    twoFactorEnforced || (requireTwoFactorStaff && isStaffRole(form.role as UserRole));

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
          Správa používateľov
        </h1>
        <p className="text-sm text-slate-500 mt-2 max-w-2xl">
          Spravujte prístupové účty, role a zabezpečenie používateľov systému.
        </p>
      </header>

      <section className="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
          <Pencil size={18} className="text-indigo-500" />
          <h2 className="font-bold text-slate-900 dark:text-white">
            {editingId ? 'Upraviť používateľa' : 'Nový používateľ'}
          </h2>
        </div>

        <div className="p-6 space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
            <Field label="Používateľské meno *" error={errors.username?.[0]}>
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

            <Field label="Zobrazované meno *" error={errors.name?.[0]}>
              <input
                className={inputClass(Boolean(errors.name))}
                value={form.name}
                onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
              />
            </Field>

            <Field label="E-mailová adresa *" error={errors.email?.[0]}>
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
              label={editingId ? 'Nové heslo (nepovinné)' : 'Heslo *'}
              error={errors.password?.[0]}
            >
              <div className="relative">
                <KeyRound size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                  type={showPassword ? 'text' : 'password'}
                  placeholder={editingId ? 'Ponechajte prázdne pre zachovanie' : ''}
                  className={`${inputClass(Boolean(errors.password))} pl-10 pr-10`}
                  value={form.password ?? ''}
                  onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
                />
                <button
                  type="button"
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                  onClick={() => setShowPassword((v) => !v)}
                  aria-label={showPassword ? 'Skryť heslo' : 'Zobraziť heslo'}
                >
                  {showPassword ? <EyeOff size={16} /> : <Eye size={16} />}
                </button>
              </div>
            </Field>

            <Field label="Rola používateľa">
              <select
                className={inputClass(false)}
                value={form.role}
                onChange={(e) => handleRoleChange(e.target.value as UserRole)}
              >
                {USER_ROLES.map((r) => (
                  <option key={r} value={r}>
                    {USER_ROLE_LABELS[r]}
                  </option>
                ))}
              </select>
            </Field>

            <Field label="Stav účtu">
              <select
                className={inputClass(false)}
                value={form.active ? 'active' : 'inactive'}
                onChange={(e) => setForm((f) => ({ ...f, active: e.target.value === 'active' }))}
              >
                <option value="active">✅ Aktívny</option>
                <option value="inactive">⏸️ Neaktívny</option>
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
                  Dvojfaktorové overenie (2FA)
                  {enforced && (
                    <span className="text-xs font-bold text-rose-600">(Vynútené systémom)</span>
                  )}
                </span>
                <span className="block text-sm text-slate-500 mt-1">
                  Vyžadovať overovací kód pri prihlásení
                </span>
              </span>
            </label>

            {editingId && twoFactorSecret && form.twoFactorEnabled && (
              <div className="flex items-center gap-3 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-100 dark:border-sky-900 px-4 py-3 text-sm">
                <KeyRound size={18} className="text-sky-600 shrink-0" />
                <span className="text-slate-600 dark:text-slate-300">
                  Aktuálny tajný kód (Secret):{' '}
                  <code className="ml-1 px-2 py-1 rounded-lg bg-white dark:bg-slate-900 border border-sky-100 dark:border-sky-800 font-mono text-slate-900 dark:text-white">
                    {twoFactorSecret}
                  </code>
                </span>
              </div>
            )}
          </div>

          <div className="flex flex-wrap gap-3 pt-2">
            <button
              type="button"
              onClick={() => void handleSave()}
              disabled={saving}
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-sm font-bold shadow-lg shadow-indigo-500/20 disabled:opacity-50"
            >
              <Check size={16} />
              {saving ? 'Ukladám…' : editingId ? 'Uložiť zmeny' : 'Vytvoriť používateľa'}
            </button>
            {editingId && (
              <button
                type="button"
                onClick={resetForm}
                className="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-300"
              >
                Zrušiť
              </button>
            )}
          </div>
        </div>
      </section>

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel="používateľov vybraných"
        onClear={bulkSelection.clear}
        actions={[
          {
            id: 'delete',
            label: 'Zmazať vybraných',
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
                  aria-label="Vybrať všetkých"
                />
              </th>
              <th className="p-4">Používateľ</th>
              <th className="p-4">E-mail</th>
              <th className="p-4">Rola</th>
              <th className="p-4">Stav</th>
              <th className="p-4">2FA</th>
              <th className="p-4">Akcie</th>
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
                      aria-label={`Vybrať ${u.email}`}
                    />
                  ) : null}
                </td>
                <td className="p-4">
                  <div className="font-semibold text-slate-900 dark:text-white">{u.name}</div>
                  <div className="text-xs font-mono text-slate-400">{u.username ?? deriveUsername(u.email)}</div>
                </td>
                <td className="p-4">{u.email}</td>
                <td className="p-4">{u.roles[0]}</td>
                <td className="p-4">
                  {(u.active ?? true) ? (
                    <span className="text-emerald-600 font-medium">Aktívny</span>
                  ) : (
                    <span className="text-amber-600 font-medium">Neaktívny</span>
                  )}
                </td>
                <td className="p-4">{u.twoFactorEnabled ? 'Zapnuté' : 'Vypnuté'}</td>
                <td className="p-4 space-x-3">
                  <button
                    type="button"
                    onClick={() => void handleEdit(u)}
                    className="text-indigo-600 font-semibold hover:underline"
                  >
                    Upraviť
                  </button>
                  {currentUser?.id !== u.id && (
                    <button
                      type="button"
                      onClick={() => void handleDelete(u)}
                      className="text-rose-600 font-semibold hover:underline"
                    >
                      Zmazať
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
