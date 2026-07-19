// frontend/src/components/backend/OtpConfirmModal.tsx
import React, { useState } from 'react';
import { resendWorkflowOtp, verifyWorkflowOtp } from '../../api/workflows';
import { useToast } from '../../hooks/useToast';

interface OtpConfirmModalProps {
  open: boolean;
  title: string;
  description: string;
  challengeId: string;
  debugCode?: string;
  onClose: () => void;
  onVerified: () => void | Promise<void>;
}

export const OtpConfirmModal: React.FC<OtpConfirmModalProps> = ({
  open,
  title,
  description,
  challengeId,
  debugCode,
  onClose,
  onVerified,
}) => {
  const [code, setCode] = useState(debugCode ?? '');
  const [loading, setLoading] = useState(false);
  const toast = useToast();

  if (!open) {
    return null;
  }

  const handleVerify = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!code.trim()) {
      toast.warning('Enter the verification code');
      return;
    }

    setLoading(true);
    try {
      const result = await verifyWorkflowOtp(challengeId, code.trim());
      if (result.ok) {
        toast.success('Action confirmed');
        await onVerified();
        onClose();
      } else {
        toast.error(result.error || 'Invalid verification code');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    setLoading(true);
    try {
      const result = await resendWorkflowOtp(challengeId);
      if (result.ok && result.requiresOtp) {
        toast.info('New verification code sent');
        if (result.debugCode) {
          setCode(result.debugCode);
          toast.warning(`Dev OTP: ${result.debugCode}`);
        }
      } else if (!result.ok) {
        toast.error(result.error || 'Could not resend code');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div className="card max-w-md w-full">
        <div className="card-body space-y-4">
          <div>
            <h3 className="text-lg font-semibold">{title}</h3>
            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">{description}</p>
          </div>
          <form className="space-y-3" onSubmit={handleVerify}>
            <input
              type="text"
              inputMode="numeric"
              pattern="[0-9]*"
              maxLength={6}
              required
              value={code}
              onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
              className="form-input w-full text-center tracking-widest text-lg"
              placeholder="000000"
              autoComplete="one-time-code"
            />
            <div className="flex gap-2">
              <button type="submit" disabled={loading} className="btn btn-primary flex-1">
                {loading ? 'Verifying…' : 'Confirm'}
              </button>
              <button type="button" disabled={loading} onClick={() => void handleResend()} className="btn btn-secondary">
                Resend
              </button>
            </div>
            <button type="button" disabled={loading} onClick={onClose} className="w-full text-sm text-gray-500 hover:underline">
              Cancel
            </button>
          </form>
        </div>
      </div>
    </div>
  );
};

export default OtpConfirmModal;
