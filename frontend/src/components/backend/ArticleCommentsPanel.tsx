import React from 'react';
import { MessageSquare } from 'lucide-react';
import {
  type ArticleCommentsSettings,
  type TriStateSetting,
} from '../../utils/articleCommentsSettings';
import { useI18n } from '../../context/I18nContext';

interface ArticleCommentsPanelProps {
  value: ArticleCommentsSettings;
  onChange: (value: ArticleCommentsSettings) => void;
  disabled?: boolean;
  globalRequireApproval?: boolean;
  globalAllowGuests?: boolean;
}

export const ArticleCommentsPanel: React.FC<ArticleCommentsPanelProps> = ({
  value,
  onChange,
  disabled = false,
  globalRequireApproval = true,
  globalAllowGuests = true,
}) => {
  const { t } = useI18n();

  const triStateOptions: Array<{ value: TriStateSetting; label: string }> = [
    { value: 'inherit', label: t('editor.comments.triState.inherit') },
    { value: 'yes', label: t('editor.comments.triState.yes') },
    { value: 'no', label: t('editor.comments.triState.no') },
  ];

  return (
    <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/40 p-4 space-y-4">
      <div className="flex items-center gap-2">
        <MessageSquare className="w-4 h-4 text-indigo-500" />
        <h3 className="text-sm font-bold text-slate-900 dark:text-white">{t('editor.comments.title')}</h3>
      </div>

      <label className="flex items-center gap-3 text-sm">
        <input
          type="checkbox"
          checked={value.commentsEnabled}
          disabled={disabled}
          onChange={(e) => onChange({ ...value, commentsEnabled: e.target.checked })}
          className="rounded border-gray-300"
        />
        <span>{t('editor.comments.enable')}</span>
      </label>

      <div className="grid gap-3 sm:grid-cols-2">
        <div className="form-group">
          <label className="form-label">{t('editor.comments.approval')}</label>
          <select
            className="form-input"
            disabled={disabled || !value.commentsEnabled}
            value={value.commentsRequireApproval}
            onChange={(e) =>
              onChange({ ...value, commentsRequireApproval: e.target.value as TriStateSetting })
            }
          >
            {triStateOptions.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
                {option.value === 'inherit'
                  ? ` (${
                      globalRequireApproval
                        ? t('editor.comments.inheritApprovalOn')
                        : t('editor.comments.inheritApprovalOff')
                    })`
                  : ''}
              </option>
            ))}
          </select>
        </div>

        <div className="form-group">
          <label className="form-label">{t('editor.comments.guests')}</label>
          <select
            className="form-input"
            disabled={disabled || !value.commentsEnabled}
            value={value.commentsAllowGuests}
            onChange={(e) =>
              onChange({ ...value, commentsAllowGuests: e.target.value as TriStateSetting })
            }
          >
            {triStateOptions.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
                {option.value === 'inherit'
                  ? ` (${
                      globalAllowGuests
                        ? t('editor.comments.inheritGuestsOn')
                        : t('editor.comments.inheritGuestsOff')
                    })`
                  : ''}
              </option>
            ))}
          </select>
        </div>
      </div>
    </div>
  );
};
