// frontend/src/components/auth/ChangePasswordModal.tsx
import React, { useState } from 'react';
import { authApi } from '../../api/auth';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { FieldError } from '../ui/FieldError';

interface ChangePasswordModalProps {
  open: boolean;
  onClose: () => void;
}

export const ChangePasswordModal: React.FC<ChangePasswordModalProps> = ({ open, onClose }) => {
  const { t } = useI18n();
  const [oldPassword, setOldPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [confirmMismatch, setConfirmMismatch] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const toast = useToast();

  if (!open) return null;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (newPassword !== confirmPassword) {
      const message = t('auth.changePassword.mismatch');
      setConfirmMismatch(message);
      toast.warning(message);
      return;
    }
    setConfirmMismatch(null);

    setLoading(true);
    try {
      const ok = await authApi.changePassword(oldPassword, newPassword);
      if (ok) {
        toast.success(t('auth.changePassword.success'));
        setOldPassword('');
        setNewPassword('');
        setConfirmPassword('');
        onClose();
      } else {
        toast.error(t('auth.changePassword.failed'));
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">{t('auth.changePassword.title')}</h2>
        <form className="space-y-3" onSubmit={handleSubmit}>
          <input
            type="password"
            required
            value={oldPassword}
            onChange={(e) => setOldPassword(e.target.value)}
            className="form-input w-full"
            placeholder={t('auth.changePassword.current')}
            autoComplete="current-password"
          />
          <input
            type="password"
            required
            value={newPassword}
            onChange={(e) => setNewPassword(e.target.value)}
            className="form-input w-full"
            placeholder={t('auth.changePassword.new')}
            autoComplete="new-password"
          />
          <div>
            <input
              type="password"
              required
              value={confirmPassword}
              onChange={(e) => {
                setConfirmPassword(e.target.value);
                if (confirmMismatch) {
                  setConfirmMismatch(null);
                }
              }}
              className="form-input w-full"
              placeholder={t('auth.changePassword.confirm')}
              autoComplete="new-password"
              aria-invalid={confirmMismatch ? true : undefined}
            />
            <FieldError message={confirmMismatch} />
          </div>
          <div className="flex gap-2 justify-end pt-2">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              {t('admin.confirm.cancel')}
            </button>
            <button type="submit" disabled={loading} className="btn btn-primary">
              {loading ? t('auth.changePassword.saving') : t('auth.changePassword.save')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ChangePasswordModal;
