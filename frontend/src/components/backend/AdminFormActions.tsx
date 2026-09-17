import React, { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { Check, Save } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { useMediaQuery } from '../../hooks/useMediaQuery';

const ADMIN_SCROLL_ROOT = '[data-testid="admin-scroll-pane"]';

export interface AdminFormActionsProps {
  onSave: () => void;
  onApply?: () => void;
  showApply?: boolean;
  saveLabel?: string;
  applyLabel?: string;
  saveDisabled?: boolean;
  applyDisabled?: boolean;
  saveBusy?: boolean;
  extra?: React.ReactNode;
  saveTestId?: string;
}

function isSuppressed(el: HTMLElement): boolean {
  return Boolean(el.closest('.hidden, [hidden]'));
}

function isInlineVisible(el: HTMLElement, root: Element | null): boolean {
  const rect = el.getBoundingClientRect();
  if (rect.width === 0 && rect.height === 0) {
    return true;
  }

  const bounds = root
    ? root.getBoundingClientRect()
    : { top: 0, left: 0, bottom: window.innerHeight, right: window.innerWidth };
  const pad = 12;

  return (
    rect.bottom > bounds.top + pad &&
    rect.top < bounds.bottom - pad &&
    rect.right > bounds.left + pad &&
    rect.left < bounds.right - pad
  );
}

function ActionButtons({
  compact,
  showApply,
  onApply,
  onSave,
  applyLabel,
  saveLabel,
  applyDisabled,
  saveDisabled,
  extra,
  saveTestId,
}: {
  compact: boolean;
  showApply: boolean;
  onApply?: () => void;
  onSave: () => void;
  applyLabel: string;
  saveLabel: string;
  applyDisabled: boolean;
  saveDisabled: boolean;
  extra?: React.ReactNode;
  saveTestId?: string;
}) {
  const pad = compact ? 'px-3 py-2 text-xs' : '';

  return (
    <>
      {extra}
      {showApply ? (
        <button
          type="button"
          onClick={onApply}
          disabled={applyDisabled}
          className={`btn btn-secondary inline-flex items-center gap-2 shrink-0 ${pad} ${compact ? 'rounded-full shadow-sm' : ''}`}
        >
          <Check className={compact ? 'h-3.5 w-3.5' : 'h-4 w-4'} aria-hidden="true" />
          {applyLabel}
        </button>
      ) : null}
      <button
        type="button"
        onClick={onSave}
        disabled={saveDisabled}
        data-testid={compact ? undefined : saveTestId}
        className={`btn btn-primary inline-flex items-center gap-2 shrink-0 ${pad} ${compact ? 'rounded-full shadow-sm' : ''}`}
      >
        <Save className={compact ? 'h-3.5 w-3.5' : 'h-4 w-4'} aria-hidden="true" />
        {saveLabel}
      </button>
    </>
  );
}

export const AdminFormActions: React.FC<AdminFormActionsProps> = ({
  onSave,
  onApply,
  showApply = false,
  saveLabel,
  applyLabel,
  saveDisabled = false,
  applyDisabled = false,
  saveBusy = false,
  extra,
  saveTestId,
}) => {
  const { t } = useI18n();
  const inlineRef = useRef<HTMLDivElement>(null);
  const [floating, setFloating] = useState(false);
  const narrow = useMediaQuery('(max-width: 767px)');

  const resolvedApply = applyLabel ?? t('admin.formActions.apply');
  const resolvedSave = saveBusy
    ? (saveLabel ?? t('admin.formActions.saving'))
    : (saveLabel ?? t('admin.formActions.save'));

  useEffect(() => {
    const el = inlineRef.current;
    if (!el) {
      return undefined;
    }

    const root = el.closest(ADMIN_SCROLL_ROOT);

    const update = (intersecting?: boolean) => {
      if (isSuppressed(el)) {
        setFloating(false);
        return;
      }
      const geometryVisible = isInlineVisible(el, root);
      if (typeof intersecting === 'boolean') {
        setFloating(!intersecting || !geometryVisible);
        return;
      }
      setFloating(!geometryVisible);
    };

    update();

    let observer: IntersectionObserver | null = null;
    if (typeof IntersectionObserver !== 'undefined') {
      observer = new IntersectionObserver(
        ([entry]) => {
          update(entry.isIntersecting);
        },
        {
          root: root instanceof Element ? root : null,
          threshold: [0, 0.35, 0.7, 1],
          rootMargin: '0px 0px -8px 0px',
        }
      );
      observer.observe(el);
    }

    const onScrollOrResize = () => update();
    const scrollTarget: EventTarget = root ?? window;
    scrollTarget.addEventListener('scroll', onScrollOrResize, { passive: true });
    window.addEventListener('resize', onScrollOrResize);

    return () => {
      observer?.disconnect();
      scrollTarget.removeEventListener('scroll', onScrollOrResize);
      window.removeEventListener('resize', onScrollOrResize);
    };
  }, []);

  const buttons = (compact: boolean) => (
    <ActionButtons
      compact={compact}
      showApply={showApply}
      onApply={onApply}
      onSave={onSave}
      applyLabel={resolvedApply}
      saveLabel={resolvedSave}
      applyDisabled={applyDisabled}
      saveDisabled={saveDisabled}
      extra={compact ? undefined : extra}
      saveTestId={saveTestId}
    />
  );

  return (
    <>
      <div ref={inlineRef} className="flex flex-wrap items-center justify-end gap-2 shrink-0">
        {buttons(false)}
      </div>
      {floating && typeof document !== 'undefined'
        ? createPortal(
            <div
              data-testid="admin-floating-actions"
              className={
                narrow
                  ? 'fixed bottom-20 left-4 right-4 z-40 flex items-center justify-center gap-2 rounded-full border border-admin-border bg-admin-card/95 p-1.5 shadow-lg shadow-black/20 backdrop-blur'
                  : 'fixed bottom-20 right-6 z-40 flex items-center gap-2 rounded-full border border-admin-border bg-admin-card/95 p-1.5 shadow-lg shadow-black/20 backdrop-blur'
              }
            >
              {buttons(true)}
            </div>,
            document.body
          )
        : null}
    </>
  );
};

export default AdminFormActions;
