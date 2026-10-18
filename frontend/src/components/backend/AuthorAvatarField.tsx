import React, { useRef, useState } from 'react';
import { FolderOpen, RotateCcw, Upload } from 'lucide-react';
import { resolvePublicMediaUrl } from '../../api/media';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import {
  AVATAR_ACCEPT,
  DEFAULT_BLOG_AUTHOR_AVATAR_URL,
  validateAvatarFile,
} from '../../utils/avatarUpload';
import {
  AVATAR_DISPLAY_SIZES,
  avatarPreviewClass,
  type AvatarDisplaySize,
  normalizeAvatarDisplaySize,
} from '../../utils/avatarDisplaySize';
import { MediaPickerModal } from './MediaPickerModal';

export type AuthorAvatarFieldMode = 'media-only' | 'upload';

interface AuthorAvatarFieldProps {
  value: string;
  onChange: (url: string) => void;
  disabled?: boolean;
  mode: AuthorAvatarFieldMode;
  previewName?: string;
  onUploadFile?: (file: File) => Promise<boolean>;
  displaySize?: AvatarDisplaySize;
  onDisplaySizeChange?: (size: AvatarDisplaySize) => void;
  showDisplaySizeControl?: boolean;
}

export const AuthorAvatarField: React.FC<AuthorAvatarFieldProps> = ({
  value,
  onChange,
  disabled = false,
  mode,
  previewName = '',
  onUploadFile,
  displaySize = 'md',
  onDisplaySizeChange,
  showDisplaySizeControl = false,
}) => {
  const { t } = useI18n();
  const { error: toastError } = useToast();
  const inputRef = useRef<HTMLInputElement>(null);
  const [busy, setBusy] = useState(false);
  const [mediaPickerOpen, setMediaPickerOpen] = useState(false);

  const previewSrc = value.trim() !== '' ? resolvePublicMediaUrl(value.trim()) : '';
  const labelInitial = previewName.trim().charAt(0).toUpperCase();
  const previewSizeClass = avatarPreviewClass(normalizeAvatarDisplaySize(displaySize));

  const handleLocalUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file || disabled || !onUploadFile) {
      return;
    }

    const validation = await validateAvatarFile(file);
    if (!validation.ok) {
      toastError(t(`users.avatar.errors.${validation.messageKey}`));
      return;
    }

    setBusy(true);
    try {
      const ok = await onUploadFile(validation.file);
      if (!ok) {
        toastError(t('users.avatar.failed'));
      }
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="space-y-2">
      <div className="flex flex-wrap items-center gap-3">
        {previewSrc ? (
          <img
            src={previewSrc}
            alt={previewName || t('editor.author.avatar')}
            className={`${previewSizeClass} rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-700`}
          />
        ) : (
          <div
            className={`flex ${previewSizeClass} items-center justify-center rounded-full bg-gradient-to-tr from-indigo-500 to-violet-500 text-lg font-bold text-white`}
          >
            {labelInitial || '?'}
          </div>
        )}

        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            disabled={disabled || busy}
            onClick={() => setMediaPickerOpen(true)}
            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200 disabled:opacity-50"
          >
            <FolderOpen className="h-4 w-4" />
            {t('users.avatar.pickFromMedia')}
          </button>

          {mode === 'upload' && onUploadFile && (
            <button
              type="button"
              disabled={disabled || busy}
              onClick={() => inputRef.current?.click()}
              className="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50"
            >
              <Upload className="h-4 w-4" />
              {busy ? t('users.avatar.uploading') : t('users.avatar.upload')}
            </button>
          )}

          {mode === 'media-only' && (
            <button
              type="button"
              disabled={disabled || busy}
              onClick={() => onChange(DEFAULT_BLOG_AUTHOR_AVATAR_URL)}
              className="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200 disabled:opacity-50"
            >
              <RotateCcw className="h-4 w-4" />
              {t('users.avatar.resetDefault')}
            </button>
          )}
        </div>
      </div>

      {showDisplaySizeControl && onDisplaySizeChange && (
        <label className="flex flex-wrap items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
          <span className="font-medium">{t('users.avatar.displaySize')}</span>
          <select
            className="rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm dark:border-slate-700 dark:bg-slate-900"
            value={normalizeAvatarDisplaySize(displaySize)}
            disabled={disabled || busy}
            onChange={(event) => onDisplaySizeChange(event.target.value as AvatarDisplaySize)}
          >
            {AVATAR_DISPLAY_SIZES.map((option) => (
              <option key={option} value={option}>
                {t(`users.avatar.displaySizes.${option}`)}
              </option>
            ))}
          </select>
        </label>
      )}

      <p className="text-xs text-slate-500">{t('users.avatar.limitsHint')}</p>

      {mode === 'upload' && onUploadFile && (
        <input
          ref={inputRef}
          type="file"
          accept={AVATAR_ACCEPT}
          className="hidden"
          onChange={(event) => void handleLocalUpload(event)}
        />
      )}

      <MediaPickerModal
        open={mediaPickerOpen}
        onClose={() => setMediaPickerOpen(false)}
        urlFormat="storage"
        onSelect={(url) => {
          onChange(url);
          setMediaPickerOpen(false);
        }}
      />
    </div>
  );
};

export default AuthorAvatarField;
