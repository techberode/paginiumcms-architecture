import React, { useEffect } from 'react';
import { X } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';

interface KeyboardShortcutsModalProps {
  open: boolean;
  onClose: () => void;
}

function Kbd({ children }: { children: React.ReactNode }) {
  return (
    <kbd className="px-2 py-0.5 rounded-md border border-slate-200 bg-slate-100 text-xs font-mono dark:border-slate-600 dark:bg-slate-800">
      {children}
    </kbd>
  );
}

export const KeyboardShortcutsModal: React.FC<KeyboardShortcutsModalProps> = ({ open, onClose }) => {
  const { t } = useI18n();

  useEffect(() => {
    if (!open) {
      return;
    }
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onClose();
      }
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open, onClose]);

  if (!open) {
    return null;
  }

  const mod = typeof navigator !== 'undefined' && /Mac/i.test(navigator.platform) ? '⌘' : 'Ctrl';

  const rows: Array<{ keys: React.ReactNode; label: string }> = [
    {
      keys: (
        <>
          <Kbd>{mod}</Kbd>+<Kbd>K</Kbd>
        </>
      ),
      label: t('admin.shortcuts.openPalette'),
    },
    { keys: <Kbd>?</Kbd>, label: t('admin.shortcuts.showShortcuts') },
    {
      keys: (
        <>
          <Kbd>↑</Kbd> <Kbd>↓</Kbd>
        </>
      ),
      label: t('admin.shortcuts.navigatePalette'),
    },
    { keys: <Kbd>Enter</Kbd>, label: t('admin.shortcuts.selectPalette') },
    { keys: <Kbd>Esc</Kbd>, label: t('admin.shortcuts.dismissPalette') },
  ];

  return (
    <div className="fixed inset-0 z-[85] flex items-center justify-center bg-slate-950/50 p-4">
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="admin-shortcuts-title"
        className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
      >
        <div className="flex items-start justify-between gap-3 mb-4">
          <h2 id="admin-shortcuts-title" className="text-lg font-bold text-slate-900 dark:text-white">
            {t('admin.shortcuts.title')}
          </h2>
          <button
            type="button"
            className="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800"
            aria-label={t('admin.shortcuts.close')}
            onClick={onClose}
          >
            <X className="w-5 h-5" />
          </button>
        </div>
        <ul className="space-y-3">
          {rows.map((row, index) => (
            <li key={index} className="flex items-center justify-between gap-4 text-sm">
              <span className="text-slate-600 dark:text-slate-300">{row.label}</span>
              <span className="flex flex-wrap items-center gap-1 shrink-0">{row.keys}</span>
            </li>
          ))}
        </ul>
        <p className="mt-4 text-xs text-slate-500 dark:text-slate-400">{t('admin.shortcuts.openDocs')}</p>
      </div>
    </div>
  );
};
