import React, { useState } from 'react';
import { Trash2 } from 'lucide-react';
import {
  assignUserAvatarFromUrl,
  removeUserAvatar,
  uploadUserAvatar,
} from '../../api/users';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { AuthorAvatarField } from './AuthorAvatarField';

interface UserAvatarPickerProps {
  userId: string;
  name: string;
  avatarUrl?: string | null;
  disabled?: boolean;
  onAvatarUpdated: (url: string | null) => void;
}

export const UserAvatarPicker: React.FC<UserAvatarPickerProps> = ({
  userId,
  name,
  avatarUrl,
  disabled = false,
  onAvatarUpdated,
}) => {
  const { t } = useI18n();
  const { error: toastError, success } = useToast();
  const [busy, setBusy] = useState(false);

  const handleUpload = async (file: File): Promise<boolean> => {
    const res = await uploadUserAvatar(userId, file);
    if (!res.success || !res.data?.user) {
      toastError(res.error || t('users.avatar.failed'));
      return false;
    }

    onAvatarUpdated(res.data.user.avatarUrl ?? null);
    success(t('users.avatar.success'));
    return true;
  };

  const handleAssignFromMedia = async (url: string) => {
    setBusy(true);
    try {
      const res = await assignUserAvatarFromUrl(userId, url);
      if (!res.success || !res.data?.user) {
        toastError(res.error || t('users.avatar.failed'));
        return;
      }
      onAvatarUpdated(res.data.user.avatarUrl ?? null);
      success(t('users.avatar.success'));
    } finally {
      setBusy(false);
    }
  };

  const handleRemove = async () => {
    setBusy(true);
    try {
      const res = await removeUserAvatar(userId);
      if (!res.success) {
        toastError(res.error || t('users.avatar.failed'));
        return;
      }
      onAvatarUpdated(null);
      success(t('users.avatar.removed'));
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/50 p-5 space-y-3">
      <div>
        <div className="font-semibold text-slate-900 dark:text-white">{t('users.avatar.title')}</div>
        <p className="text-sm text-slate-500">{t('users.avatar.hint')}</p>
      </div>

      <AuthorAvatarField
        value={avatarUrl ?? ''}
        onChange={(url) => void handleAssignFromMedia(url)}
        disabled={disabled || busy}
        mode="upload"
        previewName={name}
        onUploadFile={handleUpload}
      />

      {avatarUrl && (
        <button
          type="button"
          disabled={disabled || busy}
          onClick={() => void handleRemove()}
          className="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-rose-600 dark:border-slate-700"
        >
          <Trash2 className="w-4 h-4" />
          {t('users.avatar.remove')}
        </button>
      )}
    </div>
  );
};

export default UserAvatarPicker;
