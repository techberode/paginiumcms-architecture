import React, { useRef, useState } from 'react';
import { Camera, FolderOpen, Trash2, User as UserIcon } from 'lucide-react';
import { listMediaFormats } from '../../api/media';
import {
  assignUserAvatarFromUrl,
  removeUserAvatar,
  resolveUserAvatarUrl,
  uploadUserAvatar,
} from '../../api/users';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { MediaPickerModal } from './MediaPickerModal';

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
  const inputRef = useRef<HTMLInputElement>(null);
  const [busy, setBusy] = useState(false);
  const [mediaPickerOpen, setMediaPickerOpen] = useState(false);
  const previewSrc = resolveUserAvatarUrl(avatarUrl);

  const handleAssign = async (url: string) => {
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

  const handleFile = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file || disabled) {
      return;
    }

    setBusy(true);
    try {
      const formats = await listMediaFormats();
      if (!formats.mimeTypes.some((mime) => mime === file.type)) {
        toastError(t('users.avatar.invalidType'));
        return;
      }

      const res = await uploadUserAvatar(userId, file);
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

  return (
    <div className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-950/50 p-5">
      <div className="flex flex-col sm:flex-row sm:items-center gap-4">
        <div className="relative h-20 w-20 shrink-0">
          {previewSrc ? (
            <img
              src={previewSrc}
              alt={name}
              className="h-20 w-20 rounded-2xl object-cover border border-slate-200 dark:border-slate-700"
            />
          ) : (
            <div className="h-20 w-20 rounded-2xl bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center text-indigo-600 dark:text-indigo-300">
              <UserIcon className="w-9 h-9" />
            </div>
          )}
          <span className="absolute -bottom-1 -right-1 inline-flex h-7 w-7 items-center justify-center rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
            <Camera className="w-3.5 h-3.5 text-slate-500" />
          </span>
        </div>

        <div className="flex-1 space-y-2">
          <div className="font-semibold text-slate-900 dark:text-white">{t('users.avatar.title')}</div>
          <p className="text-sm text-slate-500">{t('users.avatar.hint')}</p>
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              disabled={disabled || busy}
              onClick={() => setMediaPickerOpen(true)}
              className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-700 dark:text-slate-200 disabled:opacity-50"
            >
              <FolderOpen className="w-4 h-4" />
              {t('users.avatar.pickFromMedia')}
            </button>
            <button
              type="button"
              disabled={disabled || busy}
              onClick={() => inputRef.current?.click()}
              className="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold disabled:opacity-50"
            >
              {busy ? t('users.avatar.uploading') : t('users.avatar.upload')}
            </button>
            {avatarUrl && (
              <button
                type="button"
                disabled={disabled || busy}
                onClick={() => void handleRemove()}
                className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-rose-600"
              >
                <Trash2 className="w-4 h-4" />
                {t('users.avatar.remove')}
              </button>
            )}
          </div>
        </div>
      </div>
      <input
        ref={inputRef}
        type="file"
        accept="image/jpeg,image/png,image/webp,image/gif"
        className="hidden"
        onChange={(event) => void handleFile(event)}
      />

      <MediaPickerModal
        open={mediaPickerOpen}
        onClose={() => setMediaPickerOpen(false)}
        title={t('users.avatar.mediaModalTitle')}
        urlFormat="storage"
        onSelect={(url) => void handleAssign(url)}
      />
    </div>
  );
};

export default UserAvatarPicker;
