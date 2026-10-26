import React, { useMemo, useState } from 'react';
import {
  contentTranslationsApi,
  type ContentTranslationProposal,
} from '../../api/contentTranslations';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';

const TRANSLATABLE_FIELDS = ['title', 'body', 'seoTitle', 'seoDescription'] as const;

interface Props {
  type: string;
  slug: string;
  sourceLocale: string;
  missingLocales: string[];
  sourceRevision: string;
  canEdit: boolean;
  onApplied: (appliedLocales: string[]) => void;
}

function formatLocaleErrors(errors: Record<string, string[]> | undefined): string {
  if (!errors) {
    return '';
  }
  const parts: string[] = [];
  for (const [key, value] of Object.entries(errors)) {
    if (!key.startsWith('locale_')) {
      continue;
    }
    const code = key.slice('locale_'.length).toUpperCase();
    const message = value.join(' ');
    parts.push(`${code}: ${message}`);
  }
  return parts.join(' · ');
}

export const TranslateMissingPanel: React.FC<Props> = ({
  type,
  slug,
  sourceLocale,
  missingLocales,
  sourceRevision,
  canEdit,
  onApplied,
}) => {
  const { t } = useI18n();
  const toast = useToast();
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [proposal, setProposal] = useState<ContentTranslationProposal | null>(null);
  const [fields, setFields] = useState<string[]>([...TRANSLATABLE_FIELDS]);

  const targets = useMemo(
    () => missingLocales.filter((code) => code !== sourceLocale),
    [missingLocales, sourceLocale]
  );

  if (!canEdit || slug === '' || slug === 'new' || targets.length === 0) {
    return null;
  }

  const mapError = (message: string, errors?: Record<string, string[]>): string => {
    const codeStr = errors?.code?.[0];
    if (codeStr === 'NO_RESULT') {
      const detail = formatLocaleErrors(errors);
      return detail !== '' ? detail : t('editor.translation.noResult');
    }
    if (message.includes('disabled')) {
      return t('editor.translation.disabled');
    }
    if (message.includes('revision')) {
      return t('editor.translation.conflict');
    }
    if (message.includes('quota')) {
      return t('editor.translation.quota');
    }
    return message || t('editor.translation.failed');
  };

  const toggleField = (field: string) => {
    setFields((prev) => {
      if (prev.includes(field)) {
        const next = prev.filter((item) => item !== field);
        return next.length === 0 ? prev : next;
      }
      return [...prev, field];
    });
  };

  const useMetadataOnly = () => {
    setFields(['title', 'seoTitle', 'seoDescription']);
  };

  const requestProposal = async () => {
    setBusy(true);
    try {
      const res = await contentTranslationsApi.propose(type, slug, {
        sourceLocale,
        targetLocales: targets,
        sourceRevision,
        fields,
      });
      if (!res.success || !res.data) {
        toast.error(mapError(res.error || res.message || '', res.errors));
        return;
      }
      setProposal(res.data);
      setOpen(true);
    } finally {
      setBusy(false);
    }
  };

  const applyDraft = async () => {
    if (!proposal) {
      return;
    }
    setBusy(true);
    try {
      const res = await contentTranslationsApi.apply(proposal.id);
      if (!res.success) {
        toast.error(mapError(res.error || res.message || '', res.errors));
        return;
      }
      toast.success(t('editor.translation.applied'));
      setProposal(null);
      setOpen(false);
      onApplied(res.data?.appliedLocales ?? []);
    } finally {
      setBusy(false);
    }
  };

  const discard = async () => {
    if (!proposal) {
      setOpen(false);
      return;
    }
    setBusy(true);
    try {
      await contentTranslationsApi.discard(proposal.id);
      setProposal(null);
      setOpen(false);
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="relative" data-testid="translate-missing-panel">
      <div className="flex flex-wrap items-center gap-2">
        <button
          type="button"
          disabled={busy}
          onClick={() => void requestProposal()}
          className="inline-flex items-center rounded-lg border border-admin-border px-2.5 py-1 text-xs font-semibold text-admin-text hover:bg-admin-canvas disabled:opacity-60"
        >
          {busy ? t('editor.translation.proposing') : t('editor.translation.translateMissing')}
        </button>
        <button
          type="button"
          disabled={busy}
          onClick={useMetadataOnly}
          className="text-xs font-medium text-admin-muted underline-offset-2 hover:underline disabled:opacity-60"
        >
          {t('editor.translation.metadataOnly')}
        </button>
      </div>
      <fieldset className="mt-2 space-y-1 border-0 p-0">
        <legend className="text-[11px] font-medium text-admin-muted">{t('editor.translation.fieldsLabel')}</legend>
        <div className="flex flex-wrap gap-x-3 gap-y-1 text-xs text-admin-text">
          {TRANSLATABLE_FIELDS.map((field) => (
            <label key={field} className="inline-flex items-center gap-1">
              <input
                type="checkbox"
                checked={fields.includes(field)}
                onChange={() => toggleField(field)}
                disabled={busy}
              />
              {t(`editor.translation.field.${field}`)}
            </label>
          ))}
        </div>
      </fieldset>
      {open && proposal && (
        <div className="absolute left-0 z-20 mt-2 w-[min(28rem,calc(100vw-2rem))] rounded-lg border border-admin-border bg-admin-card p-3 shadow-lg">
          <div className="mb-2 flex items-center justify-between gap-2">
            <h3 className="text-sm font-semibold text-admin-text">{t('editor.translation.reviewTitle')}</h3>
            <button type="button" onClick={() => void discard()} className="text-xs text-admin-muted">
              {t('editor.translation.close')}
            </button>
          </div>
          <p className="mb-2 text-xs text-admin-muted">
            {t('editor.translation.source')}: {proposal.sourceLocale.toUpperCase()}
          </p>
          <div className="max-h-64 space-y-2 overflow-auto text-sm">
            {Object.entries(proposal.locales).map(([locale, row]) => (
              <div key={locale} className="rounded-md border border-admin-border p-2">
                <p className="font-semibold text-admin-text">{locale.toUpperCase()}</p>
                {row.status === 'ok' ? (
                  <p className="whitespace-pre-wrap text-admin-muted">{row.fields?.title || row.fields?.body}</p>
                ) : (
                  <p className="text-amber-700 dark:text-amber-200">
                    {row.status === 'skipped'
                      ? row.error || t('editor.translation.skipped')
                      : row.error || t('editor.translation.failedLocale')}
                  </p>
                )}
              </div>
            ))}
          </div>
          <div className="mt-3 flex flex-wrap gap-2">
            <button
              type="button"
              disabled={busy}
              onClick={() => void applyDraft()}
              className="rounded-md bg-admin-sidebar-active px-3 py-1.5 text-xs font-semibold text-admin-sidebar-active-text disabled:opacity-60"
            >
              {t('editor.translation.applyDraft')}
            </button>
            <button
              type="button"
              disabled={busy}
              onClick={() => void discard()}
              className="rounded-md border border-admin-border px-3 py-1.5 text-xs font-semibold text-admin-text disabled:opacity-60"
            >
              {t('editor.translation.discard')}
            </button>
          </div>
        </div>
      )}
    </div>
  );
};
