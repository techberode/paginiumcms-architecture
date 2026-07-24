import React, { useMemo, useState } from 'react';
import { Sparkles, Tags, FileText, Check, X } from 'lucide-react';
import type { ContentType } from '../../api/drafts';
import type { ContentFormat } from '../../utils/contentEditor';
import { contentApi, type SuggestMetaResponse } from '../../api/content';
import { useI18n } from '../../context/I18nContext';
import { useSettingsContext } from '../../context/SettingsContext';
import { useToast } from '../../hooks/useToast';

function parseTags(value: string): string[] {
  return value
    .split(',')
    .map((tag) => tag.trim())
    .filter(Boolean);
}

function mergeTags(existing: string, suggested: string[]): string {
  const current = parseTags(existing);
  const seen = new Set(current.map((tag) => tag.toLowerCase()));
  const merged = [...current];

  for (const tag of suggested) {
    const key = tag.toLowerCase();
    if (seen.has(key)) {
      continue;
    }
    seen.add(key);
    merged.push(tag);
  }

  return merged.join(', ');
}

export interface ContentMetaSuggestPanelProps {
  type: ContentType;
  title: string;
  body: string;
  bodyFormat: ContentFormat;
  tagsValue: string;
  descriptionValue: string;
  disabled?: boolean;
  onApplyTags: (value: string) => void;
  onApplyDescription: (value: string) => void;
}

export const ContentMetaSuggestPanel: React.FC<ContentMetaSuggestPanelProps> = ({
  type,
  title,
  body,
  bodyFormat,
  tagsValue,
  descriptionValue,
  disabled = false,
  onApplyTags,
  onApplyDescription,
}) => {
  const { t } = useI18n();
  const toast = useToast();
  const { settings } = useSettingsContext();
  const [loading, setLoading] = useState<'tags' | 'description' | null>(null);
  const [preview, setPreview] = useState<SuggestMetaResponse | null>(null);

  const autoTagEnabled = settings.content?.autoTagEnabled !== false;
  const autoDescriptionEnabled = settings.content?.autoDescriptionEnabled !== false;
  const showTags = type === 'article' && autoTagEnabled;

  const previewTags = useMemo(() => preview?.tags ?? [], [preview?.tags]);
  const previewDescription = preview?.description ?? '';

  const requestMeta = async (mode: 'tags' | 'description') => {
    if (!title.trim() && !body.trim()) {
      toast.warning(t('editor.metaSuggest.emptyBody'));
      return;
    }

    setLoading(mode);
    try {
      const result = await contentApi.suggestMeta({
        type,
        title,
        body,
        bodyFormat,
        existingTags: parseTags(tagsValue),
      });
      setPreview(result);
    } catch {
      toast.error(t('editor.metaSuggest.error'));
    } finally {
      setLoading(null);
    }
  };

  if (!showTags && !autoDescriptionEnabled) {
    return null;
  }

  return (
    <div className="rounded-xl border border-indigo-200/80 bg-indigo-50/50 p-4 space-y-3 dark:border-indigo-900/40 dark:bg-indigo-950/20">
      <div className="flex items-start gap-2">
        <Sparkles className="mt-0.5 h-4 w-4 text-indigo-600 dark:text-indigo-400 shrink-0" />
        <div>
          <p className="text-sm font-semibold text-slate-800 dark:text-slate-100">
            {t('editor.metaSuggest.title')}
          </p>
          <p className="text-xs text-slate-600 dark:text-slate-300">{t('editor.metaSuggest.hint')}</p>
        </div>
      </div>

      <div className="flex flex-wrap gap-2">
        {showTags ? (
          <button
            type="button"
            className="btn btn-secondary text-xs inline-flex items-center gap-1.5"
            disabled={disabled || loading !== null}
            onClick={() => void requestMeta('tags')}
          >
            <Tags size={14} />
            {loading === 'tags' ? t('editor.metaSuggest.loading') : t('editor.metaSuggest.suggestTags')}
          </button>
        ) : null}
        {autoDescriptionEnabled ? (
          <button
            type="button"
            className="btn btn-secondary text-xs inline-flex items-center gap-1.5"
            disabled={disabled || loading !== null}
            onClick={() => void requestMeta('description')}
          >
            <FileText size={14} />
            {loading === 'description'
              ? t('editor.metaSuggest.loading')
              : t('editor.metaSuggest.suggestDescription')}
          </button>
        ) : null}
      </div>

      {preview ? (
        <div className="rounded-lg border border-slate-200 bg-white p-3 text-sm dark:border-slate-700 dark:bg-slate-900 space-y-3">
          {showTags && previewTags.length > 0 ? (
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                {t('editor.metaSuggest.previewTags')}
              </p>
              <div className="flex flex-wrap gap-2">
                {previewTags.map((tag) => (
                  <span
                    key={tag}
                    className="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200"
                  >
                    {tag}
                  </span>
                ))}
              </div>
              <button
                type="button"
                className="btn btn-primary text-xs inline-flex items-center gap-1"
                disabled={disabled}
                onClick={() => {
                  onApplyTags(mergeTags(tagsValue, previewTags));
                  toast.success(t('editor.metaSuggest.appliedTags'));
                  setPreview(null);
                }}
              >
                <Check size={14} />
                {t('editor.metaSuggest.applyTags')}
              </button>
            </div>
          ) : null}

          {autoDescriptionEnabled && previewDescription ? (
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                {t('editor.metaSuggest.previewDescription')}
              </p>
              <p className="text-slate-700 dark:text-slate-200 whitespace-pre-wrap">{previewDescription}</p>
              {descriptionValue.trim() && descriptionValue.trim() !== previewDescription.trim() ? (
                <p className="text-xs text-amber-700 dark:text-amber-300">
                  {t('editor.metaSuggest.replaceWarning')}
                </p>
              ) : null}
              <button
                type="button"
                className="btn btn-primary text-xs inline-flex items-center gap-1"
                disabled={disabled}
                onClick={() => {
                  onApplyDescription(previewDescription);
                  toast.success(t('editor.metaSuggest.appliedDescription'));
                  setPreview(null);
                }}
              >
                <Check size={14} />
                {t('editor.metaSuggest.applyDescription')}
              </button>
            </div>
          ) : null}

          <button
            type="button"
            className="text-xs text-slate-500 hover:text-slate-700 inline-flex items-center gap-1"
            onClick={() => setPreview(null)}
          >
            <X size={12} />
            {t('editor.metaSuggest.dismissPreview')}
          </button>
        </div>
      ) : null}
    </div>
  );
};

export default ContentMetaSuggestPanel;
