// frontend/src/components/auth/ForgotPasswordModal.tsx
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { authApi } from '../../api/auth';
import { useToast } from '../../hooks/useToast';

export const ForgotPasswordModal: React.FC = () => {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);
  const toast = useToast();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email) {
      toast.warning('Enter your email');
      return;
    }

    setLoading(true);
    try {
      const result = await authApi.resetPassword(email);
      if (result.success) {
        setSent(true);
        if (result.token) {
          toast.info('SMTP not configured — use the reset link from the dev token in console.');
          console.debug('[PaginiumCMS] Reset token (dev only):', result.token);
        } else {
          toast.success('If the account exists, a reset email was sent.');
        }
      } else {
        toast.error('Password reset request failed');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4">
      <div className="max-w-md w-full space-y-8">
        <div className="text-center">
          <h2 className="text-3xl font-extrabold text-gray-900 dark:text-white">Forgot password</h2>
          <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {sent ? 'Check your inbox for a reset link.' : 'We will send a reset link to your email.'}
          </p>
        </div>

        {!sent && (
          <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="form-input w-full"
              placeholder="Email"
              autoComplete="email"
            />
            <button type="submit" disabled={loading} className="w-full btn btn-primary">
              {loading ? 'Sending…' : 'Send reset link'}
            </button>
          </form>
        )}

        <p className="text-center text-sm text-gray-500">
          <Link to="/login" className="text-indigo-600 hover:underline">
            Back to sign in
          </Link>
        </p>
      </div>
    </div>
  );
};

export default ForgotPasswordModal;
