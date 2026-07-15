// frontend/src/components/auth/ChangePasswordModal.tsx
import React, { useState } from 'react';
import { authApi } from '../../api/auth';
import { useToast } from '../../hooks/useToast';

interface ChangePasswordModalProps {
  open: boolean;
  onClose: () => void;
}

export const ChangePasswordModal: React.FC<ChangePasswordModalProps> = ({ open, onClose }) => {
  const [oldPassword, setOldPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [loading, setLoading] = useState(false);
  const toast = useToast();

  if (!open) return null;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (newPassword !== confirm) {
      toast.warning('New passwords do not match');
      return;
    }

    setLoading(true);
    try {
      const ok = await authApi.changePassword(oldPassword, newPassword);
      if (ok) {
        toast.success('Password changed');
        setOldPassword('');
        setNewPassword('');
        setConfirm('');
        onClose();
      } else {
        toast.error('Password change failed');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Change password</h2>
        <form className="space-y-3" onSubmit={handleSubmit}>
          <input
            type="password"
            required
            value={oldPassword}
            onChange={(e) => setOldPassword(e.target.value)}
            className="form-input w-full"
            placeholder="Current password"
            autoComplete="current-password"
          />
          <input
            type="password"
            required
            value={newPassword}
            onChange={(e) => setNewPassword(e.target.value)}
            className="form-input w-full"
            placeholder="New password"
            autoComplete="new-password"
          />
          <input
            type="password"
            required
            value={confirm}
            onChange={(e) => setConfirm(e.target.value)}
            className="form-input w-full"
            placeholder="Confirm new password"
            autoComplete="new-password"
          />
          <div className="flex gap-2 justify-end pt-2">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              Cancel
            </button>
            <button type="submit" disabled={loading} className="btn btn-primary">
              {loading ? 'Saving…' : 'Save'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ChangePasswordModal;
