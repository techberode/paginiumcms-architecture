import React from 'react';
import { resolvePublicMediaUrl, uploadMedia } from '../../api/media';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { validateAvatarFile } from '../../utils/avatarUpload';
import { AuthorAvatarField } from './AuthorAvatarField';

interface WidgetMediaFieldProps {
  value: string;
  onChange: (url: string) => void;
  disabled?: boolean;
  previewName?: string;
}

export const WidgetMediaField: React.FC<WidgetMediaFieldProps> = ({
  value,
  onChange,
  disabled = false,
  previewName = '',
}) => {
  const { t } = useI18n();
  const toast = useToast();

  return (
    <AuthorAvatarField
      value={value}
      onChange={onChange}
      disabled={disabled}
      mode="upload"
      previewName={previewName}
      onUploadFile={async (file) => {
        const validation = await validateAvatarFile(file);
        if (!validation.ok) {
          toast.error(t(`users.avatar.errors.${validation.messageKey}`));
          return false;
        }
        const result = await uploadMedia(validation.file, previewName);
        if (!result.ok) {
          toast.error(result.error ?? t('users.avatar.failed'));
          return false;
        }
        onChange(result.media.url);
        return true;
      }}
    />
  );
};

export function widgetMediaPreviewSrc(url: string): string {
  const trimmed = url.trim();
  return trimmed === '' ? '' : resolvePublicMediaUrl(trimmed);
}
