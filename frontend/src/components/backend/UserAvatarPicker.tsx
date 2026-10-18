import React, { useState } from 'react';
import { Trash2 } from 'lucide-react';
import {
  assignUserAvatarFromUrl,
  removeUserAvatar,
  uploadUserAvatar,
} from '../../api/users';
import { authApi } from '../../api/auth';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { AuthorAvatarField } from './AuthorAvatarField';
import { updateUser } from '../../api/users';
import type { AvatarDisplaySize } from '../../utils/avatarDisplaySize';
import { normalizeAvatarDisplaySize } from '../../utils/avatarDisplaySize';

interface UserAvatarPickerProps {
  userId: string;
  name: string;
  avatarUrl?: string | null;
  avatarDisplaySize?: AvatarDisplaySize;
  disabled?: boolean;
  selfService?: boolean;
  /** Skip the outer card when nested inside AdminWidgetCard. */
  embedded?: boolean;
  onAvatarUpdated: (url: string | null) => void;
  onDisplaySizeUpdated?: (size: AvatarDisplaySize) => void;
}

export const UserAvatarPicker: React.FC<UserAvatarPickerProps> = ({
  userId,
  name,
  avatarUrl,
  avatarDisplaySize = 'md',
  disabled = false,
  selfService = false,
  embedded = false,
  onAvatarUpdated,
  onDisplaySizeUpdated,
}) => {
  const { t } = useI18n();
  const { error: toastError, success } = useToast();
  const [busy, setBusy] = useState(false);

  const handleUpload = async (file: File): Promise<boolean> => {
    const res = selfService ? await authApi.uploadMyAvatar(file) : await uploadUserAvatar(userId, file);
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
      const res = selfService
        ? await authApi.assignMyAvatarFromUrl(url)
        : await assignUserAvatarFromUrl(userId, url);
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

  const handleDisplaySizeChange = async (size: AvatarDisplaySize) => {
    const normalized = normalizeAvatarDisplaySize(size);
    setBusy(true);
    try {
      const res = selfService
        ? await authApi.updateProfile({ avatarDisplaySize: normalized })
        : await updateUser(userId, { avatarDisplaySize: normalized });
      if (!res.success || !res.data?.user) {
        toastError(res.error || t('users.avatar.failed'));
        return;
      }
      onDisplaySizeUpdated?.(normalizeAvatarDisplaySize(res.data.user.avatarDisplaySize));
    } finally {
      setBusy(false);
    }
  };

  const handleRemove = async () => {
    setBusy(true);
    try {
      const res = selfService ? await authApi.removeMyAvatar() : await removeUserAvatar(userId);
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

  const body = (
    <div className="space-y-3">
      {embedded ? null : (
        <div>
          <div className="font-semibold text-admin-text">{t('users.avatar.title')}</div>
          <p className="text-sm text-admin-muted">{t('users.avatar.hint')}</p>
        </div>
      )}

      <AuthorAvatarField
        value={avatarUrl ?? ''}
        onChange={(url) => void handleAssignFromMedia(url)}
        disabled={disabled || busy}
        mode="upload"
        previewName={name}
        onUploadFile={handleUpload}
        displaySize={avatarDisplaySize}
        showDisplaySizeControl
        onDisplaySizeChange={(size) => void handleDisplaySizeChange(size)}
      />

      {avatarUrl && (
        <button
          type="button"
          disabled={disabled || busy}
          onClick={() => void handleRemove()}
          className="inline-flex items-center gap-2 rounded-lg border border-admin-border px-3 py-2 text-sm font-semibold text-rose-600"
        >
          <Trash2 className="w-4 h-4" />
          {t('users.avatar.remove')}
        </button>
      )}
    </div>
  );

  if (embedded) {
    return body;
  }

  return <div className="rounded-lg border border-admin-border bg-admin-card shadow-admin p-5">{body}</div>;
};

export default UserAvatarPicker;
