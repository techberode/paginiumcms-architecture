// frontend/src/components/auth/RegisterModal.tsx
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import { useToast } from '../../hooks/useToast';

type RegisterStep = 'form' | 'otp';

export const RegisterModal: React.FC = () => {
  const [step, setStep] = useState<RegisterStep>('form');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [otpCode, setOtpCode] = useState('');
  const [challengeId, setChallengeId] = useState('');
  const [loading, setLoading] = useState(false);
  const { register, verifyRegisterOtp, resendRegisterOtp } = useAuth();
  const toast = useToast();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name || !email || !password) {
      toast.warning('Fill in all fields');
      return;
    }

    setLoading(true);
    try {
      const result = await register(email, password, name);
      if (result.success && result.requiresOtp && result.challengeId) {
        setChallengeId(result.challengeId);
        setStep('otp');
        toast.info('Verification code sent to your email');
        if (result.debugCode) {
          toast.warning(`Dev OTP: ${result.debugCode}`);
        }
        return;
      }
      if (result.success) {
        toast.success('Registration successful');
      } else {
        toast.error(result.error || 'Registration failed');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!otpCode.trim()) {
      toast.warning('Enter the verification code');
      return;
    }

    setLoading(true);
    try {
      const result = await verifyRegisterOtp(challengeId, otpCode.trim());
      if (result.success) {
        toast.success('Registration successful');
      } else {
        toast.error(result.error || 'Invalid verification code');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    if (!challengeId) {
      return;
    }

    setLoading(true);
    try {
      const result = await resendRegisterOtp(challengeId);
      if (result.success) {
        if (result.challengeId) {
          setChallengeId(result.challengeId);
        }
        toast.info('New verification code sent');
        if (result.debugCode) {
          toast.warning(`Dev OTP: ${result.debugCode}`);
        }
      } else {
        toast.error(result.error || 'Could not resend code');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4">
      <div className="max-w-md w-full space-y-8">
        <div className="text-center">
          <h2 className="text-3xl font-extrabold text-gray-900 dark:text-white">
            {step === 'otp' ? 'Verify email' : 'Create account'}
          </h2>
          <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">PaginiumCMS administration</p>
        </div>

        {step === 'form' ? (
          <form className="mt-8 space-y-4" onSubmit={handleSubmit}>
            <input
              type="text"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
              className="form-input w-full"
              placeholder="Full name"
              autoComplete="name"
            />
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="form-input w-full"
              placeholder="Email"
              autoComplete="email"
            />
            <input
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="form-input w-full"
              placeholder="Password"
              autoComplete="new-password"
            />
            <button type="submit" disabled={loading} className="w-full btn btn-primary">
              {loading ? 'Registering…' : 'Register'}
            </button>
          </form>
        ) : (
          <form className="mt-8 space-y-4" onSubmit={handleVerifyOtp}>
            <p className="text-sm text-gray-600 dark:text-gray-400 text-center">
              Enter the 6-digit code sent to <strong>{email}</strong>
            </p>
            <input
              type="text"
              inputMode="numeric"
              pattern="[0-9]*"
              maxLength={6}
              required
              value={otpCode}
              onChange={(e) => setOtpCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
              className="form-input w-full text-center tracking-widest text-lg"
              placeholder="000000"
              autoComplete="one-time-code"
            />
            <button type="submit" disabled={loading} className="w-full btn btn-primary">
              {loading ? 'Verifying…' : 'Verify and create account'}
            </button>
            <button
              type="button"
              disabled={loading}
              onClick={handleResend}
              className="w-full btn btn-secondary"
            >
              Resend code
            </button>
            <button
              type="button"
              disabled={loading}
              onClick={() => {
                setStep('form');
                setOtpCode('');
              }}
              className="w-full text-sm text-gray-500 hover:underline"
            >
              Back to registration form
            </button>
          </form>
        )}

        <p className="text-center text-sm text-gray-500">
          Already have an account?{' '}
          <Link to="/login" className="text-indigo-600 hover:underline">
            Sign in
          </Link>
        </p>
      </div>
    </div>
  );
};

export default RegisterModal;
