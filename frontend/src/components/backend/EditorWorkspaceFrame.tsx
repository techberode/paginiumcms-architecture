import React, { createContext, useContext, useEffect, useLayoutEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import { Eye, Minimize2, PanelRight, Save, X } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';

export const EditorWorkspaceActiveContext = createContext(false);

export function useEditorWorkspaceActive(): boolean {
  return useContext(EditorWorkspaceActiveContext);
}

const WORKSPACE_OVERLAY_SELECTOR =
  '[data-testid="site-preview-modal"], [data-testid="media-picker-modal"]';

const TOOLBAR_BTN =
  'admin-row-hover inline-flex items-center gap-1.5 rounded-lg border border-admin-border px-2.5 py-1.5 text-xs font-semibold';

interface EditorWorkspaceFrameProps {
  heading: string;
  title: string;
  titlePlaceholder: string;
  canEdit: boolean;
  saving: boolean;
  autoSaveLabel?: string;
  detailsOpen: boolean;
  details: React.ReactNode;
  extraToolbar?: React.ReactNode;
  children: React.ReactNode;
  footerExtra?: React.ReactNode;
  statsLabel: string;
  onTitleChange: (value: string) => void;
  onDetailsOpenChange: (open: boolean) => void;
  onOpenPreview?: () => void;
  onSave: () => void;
  onExit: () => void;
  onCloseEditor: () => void;
}

export const EditorWorkspaceFrame: React.FC<EditorWorkspaceFrameProps> = ({
  heading,
  title,
  titlePlaceholder,
  canEdit,
  saving,
  autoSaveLabel,
  detailsOpen,
  details,
  extraToolbar,
  children,
  footerExtra,
  statsLabel,
  onTitleChange,
  onDetailsOpenChange,
  onOpenPreview,
  onSave,
  onExit,
  onCloseEditor,
}) => {
  const { t } = useI18n();
  const titleRef = useRef<HTMLInputElement>(null);
  const dark =
    typeof document !== 'undefined' && document.documentElement.classList.contains('dark');

  useEffect(() => {
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    const appRoot = document.getElementById('root');
    const hadInert = appRoot?.hasAttribute('inert') ?? false;
    appRoot?.setAttribute('inert', '');

    const onKeyDown = (event: KeyboardEvent): void => {
      if (event.key !== 'Escape') {
        return;
      }
      if (document.querySelector(WORKSPACE_OVERLAY_SELECTOR)) {
        return;
      }
      event.preventDefault();
      onExit();
    };
    window.addEventListener('keydown', onKeyDown);
    return () => {
      document.body.style.overflow = previousOverflow;
      if (appRoot && !hadInert) {
        appRoot.removeAttribute('inert');
      }
      window.removeEventListener('keydown', onKeyDown);
    };
  }, [onExit]);

  useLayoutEffect(() => {
    const previous = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    titleRef.current?.focus();
    return () => {
      previous?.focus();
    };
  }, []);

  return createPortal(
    <EditorWorkspaceActiveContext.Provider value={true}>
      <div
        role="dialog"
        aria-modal="true"
        aria-label={heading}
        data-testid="editor-workspace"
        className={`admin-shell isolate fixed inset-0 z-[190] flex flex-col bg-admin-canvas text-admin-text${dark ? ' dark' : ''}`}
      >
        <div className="flex shrink-0 flex-wrap items-center gap-2 border-b border-admin-border bg-admin-card px-3 py-2">
          <p className="hidden text-xs font-semibold uppercase tracking-wide text-admin-muted sm:block">
            {heading}
          </p>
          <input
            ref={titleRef}
            type="text"
            value={title}
            onChange={(event) => onTitleChange(event.target.value)}
            disabled={!canEdit}
            placeholder={titlePlaceholder}
            className="form-input min-w-0 flex-1 py-1.5 text-sm"
            data-testid="editor-workspace-title"
          />
          {autoSaveLabel ? <span className="text-xs text-admin-muted">{autoSaveLabel}</span> : null}
          {extraToolbar}
          <button
            type="button"
            onClick={() => onDetailsOpenChange(!detailsOpen)}
            className={TOOLBAR_BTN}
            data-testid="editor-workspace-details"
          >
            <PanelRight className="h-3.5 w-3.5" />
            {t('editor.shell.workspaceDetails')}
          </button>
          {onOpenPreview ? (
            <button type="button" onClick={onOpenPreview} className={TOOLBAR_BTN}>
              <Eye className="h-3.5 w-3.5" />
              {t('editor.shell.preview')}
            </button>
          ) : null}
          <button
            type="button"
            onClick={onSave}
            disabled={saving || !canEdit}
            className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white disabled:opacity-50"
            data-testid="editor-workspace-save"
          >
            <Save className="h-3.5 w-3.5" />
            {saving ? t('editor.shell.saving') : t('editor.shell.save')}
          </button>
          <button
            type="button"
            onClick={onExit}
            className={TOOLBAR_BTN}
            data-testid="editor-workspace-exit"
            title={t('editor.shell.workspaceExit')}
          >
            <Minimize2 className="h-3.5 w-3.5" />
            {t('editor.shell.workspaceExit')}
          </button>
          <button
            type="button"
            onClick={onCloseEditor}
            className="admin-row-hover inline-flex items-center justify-center rounded-lg border border-admin-border p-2 text-admin-muted"
            title={t('editor.shell.close')}
          >
            <X className="h-4 w-4" />
          </button>
        </div>

        <div className="flex min-h-0 flex-1 overflow-hidden">
          <div
            className="editor-workspace-canvas flex min-h-0 min-w-0 flex-1 flex-col overflow-auto p-3 sm:p-4 has-[[data-testid=page-live-preview-split]]:overflow-hidden"
            data-testid="editor-workspace-canvas"
          >
            {children}
          </div>
          {detailsOpen ? (
            <aside
              className="min-h-0 w-full max-w-md shrink-0 overflow-y-auto border-l border-admin-border bg-admin-card p-4"
              data-testid="editor-workspace-aside"
            >
              {details}
              {footerExtra}
            </aside>
          ) : null}
        </div>

        <div className="flex shrink-0 items-center justify-between gap-3 border-t border-admin-border bg-admin-card px-4 py-2 text-xs text-admin-muted">
          <span>{statsLabel}</span>
          <span>{t('editor.shell.workspaceHint')}</span>
        </div>
      </div>
    </EditorWorkspaceActiveContext.Provider>,
    document.body
  );
};

export default EditorWorkspaceFrame;
