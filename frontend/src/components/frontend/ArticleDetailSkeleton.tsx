import React from 'react';
import { ArrowLeft } from 'lucide-react';
import { PUBLIC_CARD } from '../../theme/publicUiClasses';

type ArticleDetailSkeletonProps = {
  backLabel: string;
  onBack: () => void;
};

/**
 * Stable-height placeholder while the next blog article loads (reduces CLS vs full-page spinner).
 */
export const ArticleDetailSkeleton: React.FC<ArticleDetailSkeletonProps> = ({ backLabel, onBack }) => {
  return (
    <>
      <div className="pt-10 pg-no-print">
        <button
          type="button"
          onClick={onBack}
          className="inline-flex items-center gap-2 text-sm font-bold text-theme-text-muted hover:text-theme-primary transition-colors cursor-pointer mb-6"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>{backLabel}</span>
        </button>
      </div>

      <div className="animate-pulse" aria-busy="true" aria-live="polite">
        <div className="flex gap-2 mb-4">
          <div className="h-6 w-16 rounded-lg bg-theme-surface-elevated" />
          <div className="h-6 w-20 rounded-lg bg-theme-surface-elevated" />
        </div>
        <div className="space-y-3 mb-6">
          <div className="h-10 sm:h-12 w-full max-w-3xl rounded-xl bg-theme-surface-elevated" />
          <div className="h-10 sm:h-12 w-4/5 max-w-2xl rounded-xl bg-theme-surface-elevated" />
        </div>
        <div className="h-14 border-y border-theme-border/80 mb-8 rounded-sm bg-theme-surface-elevated/40" />
        <div className="aspect-[21/9] max-h-[480px] w-full rounded-3xl bg-theme-surface-elevated mb-10" />
        <div className={`${PUBLIC_CARD} p-8 sm:p-12 min-h-[42vh] space-y-4`}>
          <div className="h-4 w-full rounded bg-theme-surface-elevated" />
          <div className="h-4 w-full rounded bg-theme-surface-elevated" />
          <div className="h-4 w-11/12 rounded bg-theme-surface-elevated" />
          <div className="h-4 w-full rounded bg-theme-surface-elevated" />
          <div className="h-4 w-10/12 rounded bg-theme-surface-elevated" />
          <div className="h-4 w-full rounded bg-theme-surface-elevated" />
          <div className="h-4 w-9/12 rounded bg-theme-surface-elevated" />
        </div>
        <div className="mt-12 min-h-[22rem] space-y-4" aria-hidden>
          <div className="h-8 w-48 rounded-lg bg-theme-surface-elevated" />
          <div className={`${PUBLIC_CARD} min-h-[18rem] p-6 space-y-3`}>
            <div className="h-4 w-1/3 rounded bg-theme-surface-elevated" />
            <div className="h-10 w-full rounded-lg bg-theme-surface-elevated" />
            <div className="h-10 w-full rounded-lg bg-theme-surface-elevated" />
            <div className="h-24 w-full rounded-lg bg-theme-surface-elevated" />
          </div>
        </div>
      </div>
    </>
  );
};
