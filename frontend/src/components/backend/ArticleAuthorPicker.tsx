import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { UserRound } from 'lucide-react';
import { listUsers, resolveUserAvatarUrl, uploadAuthorOverrideAvatar, type User } from '../../api/users';
import { resolvePublicMediaUrl } from '../../api/media';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { AuthorAvatarField } from './AuthorAvatarField';
import type { ArticleAuthorSettings } from '../../utils/articleAuthorSettings';

const DEFAULT_OPTION = '__default__';
const CUSTOM_OPTION = '__custom__';

export interface ArticleAuthorPickerProps {
  value: ArticleAuthorSettings;
  onChange: (value: ArticleAuthorSettings) => void;
  disabled?: boolean;
  defaultAuthorName?: string;
}

export const ArticleAuthorPicker: React.FC<ArticleAuthorPickerProps> = ({
  value,
  onChange,
  disabled = false,
  defaultAuthorName = '',
}) => {
  const { t } = useI18n();
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const { error: toastError } = useToast();

  useEffect(() => {
    let cancelled = false;

    const load = async () => {
      setLoading(true);
      try {
        const response = await listUsers();
        if (!cancelled) {
          setUsers(response.users.filter((user) => user.active !== false));
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    };

    void load();

    return () => {
      cancelled = true;
    };
  }, []);

  const selectedKey = useMemo(() => {
    if (value.authorId !== '') {
      return value.authorId;
    }

    if (value.author !== '' || value.authorBio !== '' || value.authorAvatarUrl !== '') {
      return CUSTOM_OPTION;
    }

    return DEFAULT_OPTION;
  }, [value.author, value.authorAvatarUrl, value.authorBio, value.authorId]);

  const linkedUser = useMemo(
    () => users.find((user) => user.id === value.authorId) ?? null,
    [users, value.authorId]
  );

  const previewAvatarUrl = useMemo(() => {
    if (value.authorAvatarUrl.trim() !== '') {
      const raw = value.authorAvatarUrl.trim();
      if (raw.startsWith('http://') || raw.startsWith('https://')) {
        return raw;
      }

      return resolvePublicMediaUrl(raw);
    }

    if (linkedUser?.avatarUrl) {
      return resolveUserAvatarUrl(linkedUser.avatarUrl);
    }

    return '';
  }, [linkedUser?.avatarUrl, value.authorAvatarUrl]);

  const previewName = useMemo(() => {
    if (value.author.trim() !== '') {
      return value.author.trim();
    }

    if (linkedUser) {
      return linkedUser.name.trim() !== '' ? linkedUser.name : linkedUser.email;
    }

    return defaultAuthorName || t('editor.author.defaultLabel');
  }, [defaultAuthorName, linkedUser, t, value.author]);

  const handleSelectChange = (nextKey: string) => {
    if (nextKey === DEFAULT_OPTION) {
      onChange({
        authorId: '',
        author: '',
        authorBio: '',
        authorAvatarUrl: '',
      });
      return;
    }

    if (nextKey === CUSTOM_OPTION) {
      onChange({
        authorId: '',
        author: value.author,
        authorBio: value.authorBio,
        authorAvatarUrl: value.authorAvatarUrl,
      });
      return;
    }

    const user = users.find((entry) => entry.id === nextKey);
    if (!user) {
      return;
    }

    onChange({
      authorId: user.id,
      author: user.name.trim(),
      authorBio: user.bio?.trim() ?? '',
      authorAvatarUrl: user.avatarUrl?.trim() ?? '',
    });
  };

  const showCustomFields = selectedKey !== DEFAULT_OPTION;

  return (
    <div className="rounded-xl border border-slate-200 dark:border-slate-800 p-4 space-y-4">
      <div className="space-y-2">
        <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
          {t('editor.author.title')}
        </label>
        <select
          className="form-input w-full"
          value={selectedKey}
          disabled={disabled || loading}
          onChange={(event) => handleSelectChange(event.target.value)}
        >
          <option value={DEFAULT_OPTION}>
            {t('editor.author.useDefault', { name: defaultAuthorName || t('editor.author.defaultLabel') })}
          </option>
          {users.map((user) => (
            <option key={user.id} value={user.id}>
              {user.name.trim() !== '' ? user.name : user.email}
            </option>
          ))}
          <option value={CUSTOM_OPTION}>{t('editor.author.customOption')}</option>
        </select>
      </div>

      {showCustomFields && (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
          <div className="flex shrink-0 items-center gap-3">
            {previewAvatarUrl ? (
              <img
                src={previewAvatarUrl}
                alt={previewName}
                className="h-14 w-14 rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-700"
              />
            ) : (
              <div className="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-tr from-indigo-500 to-violet-500 text-lg font-bold text-white">
                {previewName.charAt(0).toUpperCase() || <UserRound className="h-6 w-6" />}
              </div>
            )}
            <div className="min-w-0">
              <div className="text-sm font-semibold text-slate-800 dark:text-slate-100">{previewName}</div>
              <div className="text-xs text-slate-500">{t('editor.author.previewHint')}</div>
            </div>
          </div>

          <div className="flex-1 space-y-3">
            <div>
              <label className="mb-1 block text-xs font-semibold text-slate-500">
                {t('editor.author.displayName')}
              </label>
              <input
                type="text"
                className="form-input w-full"
                value={value.author}
                disabled={disabled}
                placeholder={linkedUser?.name || defaultAuthorName}
                onChange={(event) => onChange({ ...value, author: event.target.value })}
              />
            </div>

            <div>
              <label className="mb-1 block text-xs font-semibold text-slate-500">
                {t('editor.author.bio')}
              </label>
              <textarea
                className="form-input w-full min-h-[72px]"
                value={value.authorBio}
                disabled={disabled}
                placeholder={t('editor.author.bioPlaceholder')}
                onChange={(event) => onChange({ ...value, authorBio: event.target.value })}
              />
            </div>

            <div>
              <label className="mb-1 block text-xs font-semibold text-slate-500">
                {t('editor.author.avatar')}
              </label>
              <AuthorAvatarField
                value={value.authorAvatarUrl}
                onChange={(url) => onChange({ ...value, authorAvatarUrl: url })}
                disabled={disabled}
                mode="upload"
                previewName={previewName}
                onUploadFile={async (file) => {
                  const result = await uploadAuthorOverrideAvatar(file);
                  if (!result.ok) {
                    toastError(t(`users.avatar.errors.${result.error}`));
                    return false;
                  }
                  onChange({ ...value, authorAvatarUrl: result.url });
                  return true;
                }}
              />
            </div>
          </div>
        </div>
      )}

      <p className="text-xs text-slate-500">
        {t('editor.author.hint')}{' '}
        <Link to="/settings" className="font-semibold text-indigo-600 hover:underline">
          {t('editor.author.settingsLink')}
        </Link>
        {' · '}
        <Link to="/users" className="font-semibold text-indigo-600 hover:underline">
          {t('editor.author.usersLink')}
        </Link>
      </p>
    </div>
  );
};

export default ArticleAuthorPicker;
