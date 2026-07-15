// frontend/src/components/auth/ResetPasswordModal.tsx
import React, { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { authApi } from '../../api/auth';
import { useToast } from '../../hooks/useToast';

export const ResetPasswordModal: React.FC = () => {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') || '';
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);
  const toast = useToast();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) {
      toast.error('Missing reset token');
      return;
    }
    if (password !== confirm) {
      toast.warning('Passwords do not match');
      return;
    }

    setLoading(true);
    try {
      const ok = await authApi.verifyResetToken(token, password);
      if (ok) {
        setDone(true);
        toast.success('Password updated — you can sign in now.');
      } else {
        toast.error('Invalid or expired reset token');
      }
    } finally {
      setLoading(false);
    }
  };

  if (!token) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 px-4">
        <div className="text-center space-y-4">
          <p className="text-gray-600 dark:text-gray-400">Invalid reset link.</p>
          <Link to="/forgot-password" className="text-indigo-600 hover:underline">
            Request a new link
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4">
      <div className="max-w-md w-full space-y-8">
        <div className="text-center">
          <h2 className="text-3xl font-extrabold text-gray-900 dark:text-white">Set new password</h2>
        </div>

        {done ? (
          <p className="text-center">
            <Link to="/login" className="btn btn-primary inline-block">
              Go to sign in
            </Link>
          </p>
        ) : (
          <form className="mt-8 space-y-4" onSubmit={handleSubmit}>
            <input
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
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
              placeholder="Confirm password"
              autoComplete="new-password"
            />
            <button type="submit" disabled={loading} className="w-full btn btn-primary">
              {loading ? 'Saving…' : 'Update password'}
            </button>
          </form>
        )}
      </div>
    </div>
  );
};

export default ResetPasswordModal;
