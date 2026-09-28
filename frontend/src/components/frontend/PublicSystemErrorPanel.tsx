import React from 'react';
import { useNavigate } from 'react-router-dom';
import { useI18n } from '../../context/I18nContext';
import { BTN_PRIMARY } from '../../theme/publicUiClasses';
import type { PublicSystemErrorKind } from '../../utils/publicErrorPages';

type Props = {
  kind: PublicSystemErrorKind;
  missingSlug?: string;
};

export const PublicSystemErrorPanel: React.FC<Props> = ({ kind, missingSlug }) => {
  const { t } = useI18n();
  const navigate = useNavigate();
  const is404 = kind === 'notFound';

  return (
    <section className="min-h-[55vh] flex flex-col items-center justify-center px-4 py-16 text-center">
      <div className="pg-system-error-card max-w-lg w-full rounded-3xl border border-theme-border bg-theme-surface/80 shadow-xl px-8 py-10">
        <p className="text-xs font-black uppercase tracking-[0.2em] text-theme-primary">
          {is404 ? t('public.errors.notFoundCode') : t('public.errors.serverErrorCode')}
        </p>
        <h1 className="mt-3 text-3xl font-black text-theme-text">
          {is404 ? t('public.errors.notFoundTitle') : t('public.errors.serverErrorTitle')}
        </h1>
        <p className="mt-3 text-sm text-theme-text-muted leading-relaxed">
          {is404
            ? t('public.errors.pageNotFound', { slug: missingSlug ?? '' })
            : t('public.errors.serverErrorBody')}
        </p>
        <button
          type="button"
          onClick={() => navigate('/')}
          className={`mt-8 px-6 py-2.5 rounded-xl text-sm font-bold ${BTN_PRIMARY}`}
        >
          {t('public.nav.home')}
        </button>
      </div>
    </section>
  );
};
