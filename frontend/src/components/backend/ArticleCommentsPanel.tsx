import React from 'react';
import { MessageSquare } from 'lucide-react';
import {
  type ArticleCommentsSettings,
  type TriStateSetting,
} from '../../utils/articleCommentsSettings';

interface ArticleCommentsPanelProps {
  value: ArticleCommentsSettings;
  onChange: (value: ArticleCommentsSettings) => void;
  disabled?: boolean;
  globalRequireApproval?: boolean;
  globalAllowGuests?: boolean;
}

const TRI_STATE_OPTIONS: Array<{ value: TriStateSetting; label: string }> = [
  { value: 'inherit', label: 'Globálne nastavenie' },
  { value: 'yes', label: 'Áno' },
  { value: 'no', label: 'Nie' },
];

export const ArticleCommentsPanel: React.FC<ArticleCommentsPanelProps> = ({
  value,
  onChange,
  disabled = false,
  globalRequireApproval = true,
  globalAllowGuests = true,
}) => (
  <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/40 p-4 space-y-4">
    <div className="flex items-center gap-2">
      <MessageSquare className="w-4 h-4 text-indigo-500" />
      <h3 className="text-sm font-bold text-slate-900 dark:text-white">Komentáre k článku</h3>
    </div>

    <label className="flex items-center gap-3 text-sm">
      <input
        type="checkbox"
        checked={value.commentsEnabled}
        disabled={disabled}
        onChange={(e) => onChange({ ...value, commentsEnabled: e.target.checked })}
        className="rounded border-gray-300"
      />
      <span>Povoliť komentáre pri tomto článku</span>
    </label>

    <div className="grid gap-3 sm:grid-cols-2">
      <div className="form-group">
        <label className="form-label">Schvaľovanie komentárov</label>
        <select
          className="form-input"
          disabled={disabled || !value.commentsEnabled}
          value={value.commentsRequireApproval}
          onChange={(e) =>
            onChange({ ...value, commentsRequireApproval: e.target.value as TriStateSetting })
          }
        >
          {TRI_STATE_OPTIONS.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
              {option.value === 'inherit'
                ? ` (${globalRequireApproval ? 'schvaľovať' : 'bez schvaľovania'})`
                : ''}
            </option>
          ))}
        </select>
      </div>

      <div className="form-group">
        <label className="form-label">Komentáre od hostí</label>
        <select
          className="form-input"
          disabled={disabled || !value.commentsEnabled}
          value={value.commentsAllowGuests}
          onChange={(e) =>
            onChange({ ...value, commentsAllowGuests: e.target.value as TriStateSetting })
          }
        >
          {TRI_STATE_OPTIONS.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
              {option.value === 'inherit' ? ` (${globalAllowGuests ? 'povolené' : 'zakázané'})` : ''}
            </option>
          ))}
        </select>
      </div>
    </div>
  </div>
);
