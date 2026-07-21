import React, { useRef, useState } from 'react';
import { FolderOpen, Upload, X } from 'lucide-react';
import {
  listMediaFormats,
  resolveAdminMediaPreviewUrl,
  resolvePublicMediaUrl,
  uploadMedia,
} from '../../api/media';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { MediaPickerModal } from './MediaPickerModal';

const PICKER_I18N = 'settings.fields.login.backgroundPicker';

interface LoginBackgroundImagePickerProps {
  value: string;
  onChange: (url: string) => void;
  disabled?: boolean;
  label: string;
  help?: string;
  error?: string;
}

function loginBackgroundPreviewSrc(url: string): string {
  const raw = url.trim();
  if (!raw) {
    return '';
  }

  if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('/api/')) {
    return raw;
  }

  if (raw.startsWith('/storage/')) {
    return resolvePublicMediaUrl(raw);
  }

  if (raw.startsWith('media/')) {
    return resolveAdminMediaPreviewUrl(raw);
  }

  if (raw.startsWith('/')) {
    return raw;
  }

  return raw;
}

export const LoginBackgroundImagePicker: React.FC<LoginBackgroundImagePickerProps> = ({
  value,
  onChange,
  disabled = false,
  label,
  help,
  error,
}) => {
  const { t } = useI18n();
  const { error: toastError } = useToast();
  const inputRef = useRef<HTMLInputElement>(null);
  const [mediaPickerOpen, setMediaPickerOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const previewSrc = loginBackgroundPreviewSrc(value);
  const errorClass = error ? 'border-red-500 focus:ring-red-500' : '';

  const handleLocalUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file || disabled) {
      return;
    }

    setBusy(true);
    try {
      const formats = await listMediaFormats();
      if (!formats.mimeTypes.some((mime) => mime === file.type)) {
        toastError(t(`${PICKER_I18N}.invalidType`));
        return;
      }

      const result = await uploadMedia(file, t(`${PICKER_I18N}.uploadAlt`));
      if (!result.ok) {
        toastError(result.error || t(`${PICKER_I18N}.uploadFailed`));
        return;
      }

      onChange(resolvePublicMediaUrl(result.media.url));
    } finally {
      setBusy(false);
    }
  };

  return (
    <div>
      <label htmlFor="setting-backgroundImageUrl" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
        {label}
      </label>

      <div className="space-y-2">
        <input
          id="setting-backgroundImageUrl"
          type="url"
          value={value}
          onChange={(event) => onChange(event.target.value)}
          disabled={disabled || busy}
          className={`form-input w-full ${errorClass}`}
          placeholder="/storage/app/content/media/… alebo https://…"
        />

        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            className="btn btn-secondary inline-flex items-center gap-2 text-sm"
            disabled={disabled || busy}
            onClick={() => setMediaPickerOpen(true)}
          >
            <FolderOpen className="h-4 w-4 shrink-0" />
            {t(`${PICKER_I18N}.pickFromMedia`)}
          </button>
          <button
            type="button"
            className="btn btn-secondary inline-flex items-center gap-2 text-sm"
            disabled={disabled || busy}
            onClick={() => inputRef.current?.click()}
          >
            <Upload className="h-4 w-4 shrink-0" />
            {busy ? t(`${PICKER_I18N}.uploading`) : t(`${PICKER_I18N}.uploadLocal`)}
          </button>
          {value.trim() !== '' && (
            <button
              type="button"
              className="btn btn-secondary inline-flex items-center gap-2 text-sm text-rose-600 dark:text-rose-400"
              disabled={disabled || busy}
              onClick={() => onChange('')}
            >
              <X className="h-4 w-4 shrink-0" />
              {t(`${PICKER_I18N}.remove`)}
            </button>
          )}
        </div>
      </div>

      {previewSrc !== '' && (
        <div className="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40">
          <img
            src={previewSrc}
            alt={t(`${PICKER_I18N}.previewAlt`)}
            className="max-h-44 w-full object-cover"
            onError={(event) => {
              event.currentTarget.style.display = 'none';
            }}
          />
        </div>
      )}

      {help && !error && (
        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{help}</p>
      )}
      {error && <p className="mt-1 text-xs text-red-600 dark:text-red-400">{error}</p>}

      <input
        ref={inputRef}
        type="file"
        accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
        className="hidden"
        onChange={(event) => void handleLocalUpload(event)}
      />

      <MediaPickerModal
        open={mediaPickerOpen}
        onClose={() => setMediaPickerOpen(false)}
        title={t(`${PICKER_I18N}.mediaModalTitle`)}
        urlFormat="storage"
        onSelect={(url) => onChange(url)}
      />
    </div>
  );
};

export default LoginBackgroundImagePicker;
