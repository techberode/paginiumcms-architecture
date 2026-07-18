// frontend/src/components/backend/UsersManager.tsx
// === Správa používateľov (Iterácia 5) ===
import React, { useEffect, useState } from 'react';
import { User } from '../../api/types';
import {
  listUsers,
  createUser,
  updateUser,
  deleteUser,
  bulkDeleteUsers,
  USER_ROLES,
  UserRole,
  CreateUserPayload,
} from '../../api/users';
import { validate, validatePasswordPolicy, ValidationErrors } from '../../utils/validation';
import { getValidationRulesFor } from '../../api/validation';
import { useToast } from '../../hooks/useToast';
import { useAuth } from '../../hooks/useAuth';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { BulkActionBar } from './BulkActionBar';
import { summarizeBulkResult } from '../../types/bulk';

const emptyForm: CreateUserPayload = {
  email: '',
  name: '',
  role: 'USER',
  password: '',
};

export const UsersManager: React.FC = () => {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [form, setForm] = useState<CreateUserPayload & { password?: string }>(emptyForm);
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
      setUsers(await listUsers());
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setForm(emptyForm);
    setEditingId(null);
    setErrors({});
  };

  const validateForm = (): boolean => {
    const rules = userRules.email ? userRules : {
      email: ['required', 'email', 'max:255'],
      name: ['required', 'string', 'min:2', 'max:120'],
      role: ['required', 'in:USER,EDITOR,ADMIN,SUPER_ADMIN'],
    };

    const result = validate(
      { email: form.email, name: form.name, role: form.role },
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
          name: form.name,
          role: form.role as UserRole,
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

  const handleEdit = (user: User) => {
    setEditingId(user.id);
    setForm({
      email: user.email,
      name: user.name,
      role: (user.roles[0] as UserRole) || 'USER',
      password: '',
    });
    setErrors({});
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

  const deletableUsers = users.filter((user) => user.id !== currentUser?.id);
  const bulkSelection = useBulkSelection(
    deletableUsers.map((user) => user.id),
    users.map((user) => user.id).join('\0')
  );

  const handleBulkDelete = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(`Zmazať ${bulkSelection.count} vybraných používateľov?`)) {
      return;
    }
    const result = await bulkDeleteUsers(bulkSelection.selectedIds);
    if (result) {
      success(summarizeBulkResult(result));
      bulkSelection.clear();
      await load();
    } else {
      toastError('Hromadné mazanie zlyhalo');
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
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Používatelia</h1>

      <div className="card">
        <div className="card-body space-y-4">
          <h2 className="font-semibold">{editingId ? 'Upraviť používateľa' : 'Nový používateľ'}</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm mb-1">E-mail</label>
              <input
                className={`form-input w-full ${errors.email ? 'border-red-500' : ''}`}
                value={form.email}
                onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
              />
              {errors.email?.[0] && <p className="text-xs text-red-600 mt-1">{errors.email[0]}</p>}
            </div>
            <div>
              <label className="block text-sm mb-1">Meno</label>
              <input
                className={`form-input w-full ${errors.name ? 'border-red-500' : ''}`}
                value={form.name}
                onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
              />
              {errors.name?.[0] && <p className="text-xs text-red-600 mt-1">{errors.name[0]}</p>}
            </div>
            <div>
              <label className="block text-sm mb-1">Rola</label>
              <select
                className="form-input w-full"
                value={form.role}
                onChange={(e) => setForm((f) => ({ ...f, role: e.target.value as UserRole }))}
              >
                {USER_ROLES.map((r) => (
                  <option key={r} value={r}>{r}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm mb-1">
                Heslo {editingId && <span className="text-gray-400">(prázdne = bez zmeny)</span>}
              </label>
              <input
                type="password"
                className={`form-input w-full ${errors.password ? 'border-red-500' : ''}`}
                value={form.password ?? ''}
                onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
              />
              {errors.password?.[0] && <p className="text-xs text-red-600 mt-1">{errors.password[0]}</p>}
            </div>
          </div>
          <div className="flex gap-2">
            <button onClick={handleSave} disabled={saving} className="btn btn-primary">
              {saving ? 'Ukladám…' : editingId ? 'Uložiť zmeny' : 'Vytvoriť'}
            </button>
            {editingId && (
              <button onClick={resetForm} className="btn btn-secondary">Zrušiť</button>
            )}
          </div>
        </div>
      </div>

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

      <div className="card overflow-x-auto">
        <table className="min-w-full text-sm">
          <thead>
            <tr className="border-b dark:border-gray-700 text-left">
              <th className="p-3 w-10">
                <input
                  type="checkbox"
                  checked={bulkSelection.allSelected && deletableUsers.length > 0}
                  onChange={bulkSelection.toggleAll}
                  aria-label="Vybrať všetkých"
                />
              </th>
              <th className="p-3">E-mail</th>
              <th className="p-3">Meno</th>
              <th className="p-3">Rola</th>
              <th className="p-3">2FA</th>
              <th className="p-3">Akcie</th>
            </tr>
          </thead>
          <tbody>
            {users.map((u) => (
              <tr key={u.id} className="border-b dark:border-gray-800">
                <td className="p-3">
                  {currentUser?.id !== u.id ? (
                    <input
                      type="checkbox"
                      checked={bulkSelection.isSelected(u.id)}
                      onChange={() => bulkSelection.toggle(u.id)}
                      aria-label={`Vybrať ${u.email}`}
                    />
                  ) : null}
                </td>
                <td className="p-3">{u.email}</td>
                <td className="p-3">{u.name}</td>
                <td className="p-3">{u.roles.join(', ')}</td>
                <td className="p-3">{u.twoFactorEnabled ? 'áno' : 'nie'}</td>
                <td className="p-3 space-x-2">
                  <button onClick={() => handleEdit(u)} className="text-indigo-600 hover:underline">Upraviť</button>
                  {currentUser?.id !== u.id && (
                    <button onClick={() => handleDelete(u)} className="text-red-600 hover:underline">Zmazať</button>
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

export default UsersManager;
