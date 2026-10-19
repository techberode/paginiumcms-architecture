// frontend/src/components/CodeEditor/CodeEditorSafetyBanner.tsx
import React from 'react';
import { Link } from 'react-router-dom';
import { AlertTriangle } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';

export const CodeEditorSafetyBanner: React.FC = () => {
  const { t } = useI18n();

  return (
    <div className="mx-4 mt-3 rounded-xl border border-amber-300/80 dark:border-amber-700/60 bg-amber-50 dark:bg-amber-950/30 p-4">
      <div className="flex gap-3">
        <AlertTriangle className="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
        <div className="text-sm text-amber-950 dark:text-amber-100 space-y-2">
          <p className="font-semibold">{t('devtools.banner.title')}</p>
          <p className="text-amber-900/90 dark:text-amber-100/90 leading-relaxed">
            {t('devtools.banner.introBefore')} <strong>{t('devtools.banner.introCode')}</strong>
            {t('devtools.banner.introAfter')}{' '}
            <Link to="/pages" className="underline font-medium">
              {t('devtools.banner.pages')}
            </Link>
            ,{' '}
            <Link to="/articles" className="underline font-medium">
              {t('devtools.banner.articles')}
            </Link>{' '}
            {t('devtools.banner.and')}{' '}
            <Link to="/settings" className="underline font-medium">
              {t('devtools.banner.settings')}
            </Link>
            .
          </p>
          <ul className="list-disc pl-5 text-xs text-amber-900/80 dark:text-amber-100/80 space-y-1">
            <li>{t('devtools.banner.ruleUnderstand')}</li>
            <li>{t('devtools.banner.ruleSyntax')}</li>
            <li>
              {t('devtools.banner.ruleBackup')}{' '}
              <Link to="/backups" className="underline">
                {t('devtools.banner.backups')}
              </Link>
              .
            </li>
            <li>
              {t('devtools.banner.ruleLockBefore')} <strong>{t('devtools.banner.ruleLockButton')}</strong>{' '}
              {t('devtools.banner.ruleLockAfter')}
            </li>
            <li>{t('devtools.banner.ruleCore')}</li>
          </ul>
        </div>
      </div>
    </div>
  );
};

export default CodeEditorSafetyBanner;
