// frontend/src/components/CodeEditor/CodeEditorFileActions.tsx
import React, { useEffect, useState } from 'react';
import { codeEditorApi } from '../../api/codeEditor';
import { useToast } from '../../hooks/useToast';

interface CodeEditorFileActionsProps {
  currentFile: string;
  allowedRoots: string[];
  isDirty: boolean;
  onFileCreated: (path: string) => void;
  onFileDeleted: () => void;
  onBackupRestored: (content: string) => void;
}

export const CodeEditorFileActions: React.FC<CodeEditorFileActionsProps> = ({
  currentFile,
  allowedRoots,
  isDirty,
  onFileCreated,
  onFileDeleted,
  onBackupRestored,
}) => {
  const toast = useToast();
  const [backups, setBackups] = useState<string[]>([]);
  const [loadingBackups, setLoadingBackups] = useState(false);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (!currentFile) {
      setBackups([]);
      return;
    }

    const load = async () => {
      setLoadingBackups(true);
      try {
        const list = await codeEditorApi.getBackups(currentFile);
        setBackups(list);
      } catch {
        setBackups([]);
      } finally {
        setLoadingBackups(false);
      }
    };

    void load();
  }, [currentFile]);

  const handleNewFile = async () => {
    const defaultRoot = allowedRoots[0] ?? 'backend/app/Modules';
    const input = window.prompt(
      `Cesta nového súboru (iba povolené adresáre):\n\nPríklad: ${defaultRoot}/MyModule/Service.php`,
      `${defaultRoot}/`
    );
    if (!input?.trim()) return;

    const ok = window.confirm(
      `Vytvoriť súbor?\n\n${input.trim()}\n\nUistite sa, že cesta je v povolenom adresári.`
    );
    if (!ok) return;

    setBusy(true);
    try {
      const created = await codeEditorApi.createFile(input.trim(), '<?php\n\ndeclare(strict_types=1);\n');
      if (created) {
        toast.success('Súbor vytvorený');
        onFileCreated(input.trim());
      } else {
        toast.error('Vytvorenie súboru zlyhalo');
      }
    } finally {
      setBusy(false);
    }
  };

  const handleDelete = async () => {
    if (!currentFile) return;

    const ok = window.confirm(
      `Naozaj zmazať súbor?\n\n${currentFile}\n\nPred zmazaním sa vytvorí záloha. Táto akcia môže znefunkčniť CMS.`
    );
    if (!ok) return;

    setBusy(true);
    try {
      const deleted = await codeEditorApi.deleteFile(currentFile);
      if (deleted) {
        toast.success('Súbor zmazaný');
        onFileDeleted();
      } else {
        toast.error('Zmazanie zlyhalo');
      }
    } finally {
      setBusy(false);
    }
  };

  const handleRestore = async (backupFile: string) => {
    if (!currentFile) return;

    if (isDirty) {
      const ok = window.confirm('Máte neuložené zmeny. Obnoviť zálohu a prepísať editor?');
      if (!ok) return;
    }

    const ok = window.confirm(`Obnoviť zálohu?\n\n${backupFile}\n\n→ ${currentFile}`);
    if (!ok) return;

    setBusy(true);
    try {
      const content = await codeEditorApi.restoreBackup(currentFile, backupFile);
      if (content !== null) {
        toast.success('Záloha obnovená');
        onBackupRestored(content);
        const list = await codeEditorApi.getBackups(currentFile);
        setBackups(list);
      } else {
        toast.error('Obnova zálohy zlyhala');
      }
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="mx-4 mt-2 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/40">
      <div className="flex flex-wrap items-center gap-2 mb-3">
        <button
          type="button"
          onClick={() => void handleNewFile()}
          disabled={busy}
          className="btn btn-secondary text-xs"
        >
          Nový súbor
        </button>
        <button
          type="button"
          onClick={() => void handleDelete()}
          disabled={busy || !currentFile}
          className="btn btn-secondary text-xs text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800"
        >
          Zmazať súbor
        </button>
      </div>

      {currentFile && (
        <div>
          <p className="text-xs font-semibold text-slate-600 dark:text-slate-400 mb-2">
            Zálohy súboru
          </p>
          {loadingBackups ? (
            <p className="text-xs text-slate-500">Načítavam zálohy…</p>
          ) : backups.length === 0 ? (
            <p className="text-xs text-slate-500">Zatiaľ žiadne zálohy (vzniknú pri uložení).</p>
          ) : (
            <ul className="space-y-1 max-h-32 overflow-y-auto">
              {backups.map((backup) => (
                <li key={backup} className="flex items-center justify-between gap-2 text-xs">
                  <span className="font-mono truncate text-slate-600 dark:text-slate-300">{backup}</span>
                  <button
                    type="button"
                    disabled={busy}
                    onClick={() => void handleRestore(backup)}
                    className="shrink-0 text-indigo-600 dark:text-indigo-400 hover:underline"
                  >
                    Obnoviť
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}
    </div>
  );
};

export default CodeEditorFileActions;
