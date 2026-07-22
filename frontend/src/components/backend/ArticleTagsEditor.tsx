import React, { useMemo, useState } from 'react';
import { Plus, Tag, X } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';

function parseTags(value: string): string[] {
  return value
    .split(',')
    .map((tag) => tag.trim())
    .filter(Boolean);
}

function joinTags(tags: string[]): string {
  return tags.join(', ');
}

export interface ArticleTagsEditorProps {
  value: string;
  onChange: (value: string) => void;
  disabled?: boolean;
}

export const ArticleTagsEditor: React.FC<ArticleTagsEditorProps> = ({
  value,
  onChange,
  disabled = false,
}) => {
  const { t } = useI18n();
  const [draft, setDraft] = useState('');
  const tags = useMemo(() => parseTags(value), [value]);

  const addTag = (raw: string) => {
    const next = raw.trim();
    if (!next) {
      return;
    }
    const normalized = next.replace(/,/g, '');
    if (tags.some((tag) => tag.toLowerCase() === normalized.toLowerCase())) {
      setDraft('');
      return;
    }
    onChange(joinTags([...tags, normalized]));
    setDraft('');
  };

  const removeTag = (tag: string) => {
    onChange(joinTags(tags.filter((item) => item !== tag)));
  };

  return (
    <div className="rounded-xl border border-slate-200 dark:border-slate-800 p-4 space-y-3">
      <div className="flex items-center gap-2">
        <Tag className="w-4 h-4 text-indigo-500" />
        <div>
          <p className="text-sm font-semibold text-slate-900 dark:text-white">{t('editor.tags.title')}</p>
          <p className="text-xs text-slate-500">{t('editor.tags.hint')}</p>
        </div>
      </div>

      <div className="flex flex-wrap gap-2">
        {tags.map((tag) => (
          <span
            key={tag}
            className="inline-flex items-center gap-1 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 px-3 py-1 text-xs font-bold"
          >
            {tag}
            {!disabled && (
              <button
                type="button"
                className="rounded-full p-0.5 hover:bg-indigo-100 dark:hover:bg-indigo-900"
                aria-label={t('editor.tags.removeAria', { tag })}
                onClick={() => removeTag(tag)}
              >
                <X className="w-3 h-3" />
              </button>
            )}
          </span>
        ))}
        {tags.length === 0 && (
          <span className="text-xs text-slate-400">{t('editor.tags.empty')}</span>
        )}
      </div>

      {!disabled && (
        <form
          className="flex flex-col sm:flex-row gap-2"
          onSubmit={(event) => {
            event.preventDefault();
            addTag(draft);
          }}
        >
          <input
            type="text"
            value={draft}
            onChange={(event) => setDraft(event.target.value)}
            className="form-input flex-1"
            placeholder={t('editor.tags.placeholder')}
            aria-label={t('editor.tags.ariaLabel')}
          />
          <button type="submit" className="btn btn-secondary inline-flex items-center gap-1">
            <Plus className="w-4 h-4" />
            {t('editor.tags.add')}
          </button>
        </form>
      )}
    </div>
  );
};

export default ArticleTagsEditor;
