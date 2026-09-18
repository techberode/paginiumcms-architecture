import React, { useEffect, useId, useRef, useState } from 'react';
import { ExternalLink, HelpCircle } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';

export interface ContextHelpPanelProps {
  /** Short summary (always visible in popover). */
  summary: string;
  /** Optional longer explanation (plain text, paragraphs separated by newlines). */
  detail?: string;
  /** Optional documentation URL (repo docs or external). */
  docUrl?: string;
  docLinkLabel?: string;
}

/**
 * Click-to-open contextual help for complex admin/settings fields (It.94c).
 * Prefer over native title tooltips — readable on touch, supports doc links.
 */
export const ContextHelpPanel: React.FC<ContextHelpPanelProps> = ({
  summary,
  detail,
  docUrl,
  docLinkLabel,
}) => {
  const { t } = useI18n();
  const [open, setOpen] = useState(false);
  const panelId = useId();
  const rootRef = useRef<HTMLSpanElement>(null);

  useEffect(() => {
    if (!open) {
      return undefined;
    }

    const onPointerDown = (event: MouseEvent) => {
      if (!rootRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    };

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setOpen(false);
      }
    };

    document.addEventListener('mousedown', onPointerDown);
    document.addEventListener('keydown', onKeyDown);
    return () => {
      document.removeEventListener('mousedown', onPointerDown);
      document.removeEventListener('keydown', onKeyDown);
    };
  }, [open]);

  const linkLabel = docLinkLabel ?? t('admin.contextHelp.readDocs');

  return (
    <span ref={rootRef} className="relative inline-flex align-middle">
      <button
        type="button"
        aria-expanded={open}
        aria-controls={panelId}
        aria-label={t('settings.helpTooltip.toggle')}
        onClick={() => setOpen((prev) => !prev)}
        className="inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400 transition-colors cursor-pointer"
      >
        <HelpCircle className="h-4 w-4" aria-hidden />
      </button>
      {open ? (
        <div
          id={panelId}
          role="dialog"
          aria-label={t('admin.contextHelp.title')}
          className="absolute left-0 top-full z-50 mt-2 w-[min(26rem,calc(100vw-2rem))] rounded-xl border border-gray-200 bg-white p-4 text-xs leading-relaxed text-gray-700 shadow-lg dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200"
        >
          <p className="font-medium text-gray-900 dark:text-gray-100">{summary}</p>
          {detail ? (
            <div className="mt-2 space-y-2 text-gray-600 dark:text-gray-300 whitespace-pre-wrap">
              {detail.split('\n\n').map((paragraph) => (
                <p key={paragraph.slice(0, 24)}>{paragraph}</p>
              ))}
            </div>
          ) : null}
          {docUrl ? (
            <a
              href={docUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="mt-3 inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
            >
              {linkLabel}
              <ExternalLink className="h-3.5 w-3.5" aria-hidden />
            </a>
          ) : null}
        </div>
      ) : null}
    </span>
  );
};
