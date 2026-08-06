// frontend/src/components/backend/SeoHealthChecklist.tsx
import React from 'react';
import { AlertCircle, AlertTriangle, CheckCircle2 } from 'lucide-react';
import {
  getContentSeoHealthFromFields,
  type ContentSeoFormInput,
  type SeoHealthLevel,
} from '../../utils/seoHealth';
import { useI18n } from '../../context/I18nContext';

const LEVEL_STYLE: Record<SeoHealthLevel, string> = {
  ok: 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-100',
  warning: 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-100',
  critical: 'border-red-200 bg-red-50 text-red-950 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-100',
};

export interface SeoHealthChecklistProps {
  input: ContentSeoFormInput;
  compact?: boolean;
}

export const SeoHealthChecklist: React.FC<SeoHealthChecklistProps> = ({ input, compact = false }) => {
  const { t } = useI18n();
  const health = getContentSeoHealthFromFields(input);
  const publishedCheck = input.status === 'published' || input.status === 'scheduled';
  const Icon = health.level === 'ok' ? CheckCircle2 : health.level === 'warning' ? AlertTriangle : AlertCircle;

  if (health.level === 'ok') {
    return (
      <div className={`rounded-xl border px-4 py-3 text-sm ${LEVEL_STYLE.ok}`}>
        <div className="flex items-start gap-2">
          <Icon className="h-4 w-4 shrink-0 mt-0.5" />
          <div>
            <p className="font-semibold">{t('editor.seo.health.okTitle')}</p>
            {!compact ? <p className="mt-1 text-xs opacity-90">{t('editor.seo.health.okHint')}</p> : null}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className={`rounded-xl border px-4 py-3 text-sm ${LEVEL_STYLE[health.level]}`}>
      <div className="flex items-start gap-2">
        <Icon className="h-4 w-4 shrink-0 mt-0.5" />
        <div className="min-w-0 flex-1 space-y-2">
          <div>
            <p className="font-semibold">{t(`editor.seo.health.title.${health.level}`)}</p>
            {!publishedCheck && !input.checkAsPublished ? (
              <p className="mt-1 text-xs opacity-90">{t('editor.seo.health.draftHint')}</p>
            ) : null}
          </div>
          <ul className="space-y-2 text-xs">
            {health.issues.map((issue) => (
              <li key={issue.code} className="rounded-lg bg-white/60 dark:bg-black/20 px-3 py-2">
                <p className="font-semibold">{t(`editor.seo.issues.${issue.code}.title`)}</p>
                <p className="mt-0.5 opacity-90">{t(`editor.seo.issues.${issue.code}.hint`)}</p>
              </li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  );
};

export default SeoHealthChecklist;
