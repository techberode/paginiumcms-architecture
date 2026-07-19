import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useToast } from '../../hooks/useToast';
import { FileTree } from './FileTree';
import { EditorToolbar } from './EditorToolbar';
import { DeveloperUnlockGate, useDeveloperUnlockGate } from './DeveloperUnlockGate';
import { MonacoCodeEditor, type MonacoCodeEditorHandle } from './MonacoCodeEditor';
import { FileInfo } from '../../api/types';
import { codeEditorApi } from '../../api/codeEditor';
import { CodeEditorSafetyBanner } from './CodeEditorSafetyBanner';
import { CodeEditorFileActions } from './CodeEditorFileActions';
import './CodeEditor.css';

interface CodeEditorProps {
  initialPath?: string;
}

const CodeEditorContent: React.FC<CodeEditorProps> = ({ initialPath = '' }) => {
  const { lock, locking } = useDeveloperUnlockGate();
  const [files, setFiles] = useState<FileInfo[]>([]);
  const [allowedRoots, setAllowedRoots] = useState<string[]>([]);
  const [loadingFiles, setLoadingFiles] = useState(true);
  const [currentFile, setCurrentFile] = useState<string>(initialPath);
  const [content, setContent] = useState<string>('');
  const [originalContent, setOriginalContent] = useState<string>('');
  const [saving, setSaving] = useState(false);
  const [loadingFile, setLoadingFile] = useState(false);
  const [isDirty, setIsDirty] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [language, setLanguage] = useState<string>('plaintext');
  const [wordWrap, setWordWrap] = useState(false);
  const monacoRef = useRef<MonacoCodeEditorHandle>(null);
  const toast = useToast();

  const loadFiles = useCallback(async () => {
    setLoadingFiles(true);
    try {
      const [roots, allFiles] = await Promise.all([
        codeEditorApi.getAllowedDirectories(),
        codeEditorApi.getFiles('all'),
      ]);
      setAllowedRoots(roots);
      setFiles(allFiles);
    } catch (err) {
      toast.error('Nepodarilo sa načítať súbory');
      console.error(err);
    } finally {
      setLoadingFiles(false);
    }
  }, [toast]);

  const loadFile = useCallback(async (path: string) => {
    setLoadingFile(true);
    try {
      setError(null);
      const data = await codeEditorApi.getFile(path);
      if (data) {
        setContent(data.content || '');
        setOriginalContent(data.content || '');
        setLanguage(data.language || 'plaintext');
        setIsDirty(false);
      } else {
        setError('Nepodarilo sa načítať súbor');
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Nepodarilo sa načítať súbor';
      setError(message);
      toast.error('Nepodarilo sa načítať súbor');
      console.error(err);
    } finally {
      setLoadingFile(false);
    }
  }, [toast]);

  useEffect(() => {
    void loadFiles();
  }, [loadFiles]);

  useEffect(() => {
    if (currentFile) {
      void loadFile(currentFile);
    }
  }, [currentFile, loadFile]);

  const handleSave = async () => {
    if (!currentFile || !isDirty) return;

    const ok = window.confirm(
      `Uložiť zmeny do súboru?\n\n${currentFile}\n\nChybný PHP kód môže znefunkčniť CMS. Pokračovať?`
    );
    if (!ok) return;

    setSaving(true);
    setError(null);
    setSuccess(null);

    try {
      const result = await codeEditorApi.saveFile(currentFile, content);

      if (result.success) {
        setOriginalContent(content);
        setIsDirty(false);
        setSuccess('Súbor uložený');
        toast.success('Súbor uložený');
        setTimeout(() => setSuccess(null), 3000);
      } else {
        const message = result.error || 'Uloženie zlyhalo';
        setError(message);
        toast.error(message);
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Uloženie zlyhalo';
      setError(message);
      toast.error('Uloženie zlyhalo');
    } finally {
      setSaving(false);
    }
  };

  const handleContentChange = (newContent: string) => {
    setContent(newContent);
    setIsDirty(newContent !== originalContent);
  };

  const handleFileSelect = (path: string) => {
    if (path === currentFile) {
      return;
    }
    setCurrentFile(path);
  };

  const handleRevert = () => {
    setContent(originalContent);
    setIsDirty(false);
    toast.info('Changes reverted');
  };

  const handleLockEditor = async () => {
    if (isDirty) {
      const ok = window.confirm(
        'Máte neuložené zmeny. Naozaj chcete zamknúť Code Editor bez uloženia?'
      );
      if (!ok) return;
    } else {
      const ok = window.confirm(
        'Zamknúť Code Editor? Na ďalšie úpravy kódu budete musieť znova zadať TOTP kód.'
      );
      if (!ok) return;
    }

    await lock();
  };

  const lineCount = content === '' ? 1 : content.split('\n').length;

  return (
      <div className="code-editor-container">
        <div className="code-editor-header">
          <div className="flex items-center gap-3 min-w-0">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white shrink-0">
              Code Editor
            </h2>
            {currentFile && (
              <span className="text-sm text-gray-500 dark:text-gray-400 truncate max-w-[300px]">
                {currentFile}
              </span>
            )}
          </div>
          <div className="flex items-center gap-2 flex-wrap justify-end">
            {isDirty && (
              <span className="text-sm text-yellow-600 dark:text-yellow-400">
                Unsaved changes
              </span>
            )}
            <button
              type="button"
              onClick={() => void handleLockEditor()}
              disabled={locking}
              className="px-3 py-1.5 text-sm border border-rose-300 dark:border-rose-800 text-rose-700 dark:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded disabled:opacity-50"
              title="Zamknúť Code Editor (vyžaduje znova TOTP)"
            >
              {locking ? 'Zamykam…' : 'Zamknúť editor'}
            </button>
            <button
              type="button"
              onClick={handleRevert}
              disabled={!isDirty}
              className="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Revert
            </button>
            <button
              type="button"
              onClick={() => void handleSave()}
              disabled={!isDirty || saving}
              className="px-4 py-1.5 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              {saving ? (
                <>
                  <svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                  Saving...
                </>
              ) : (
                'Save'
              )}
            </button>
          </div>
        </div>

        <CodeEditorSafetyBanner />

        <CodeEditorFileActions
          currentFile={currentFile}
          allowedRoots={allowedRoots}
          isDirty={isDirty}
          onFileCreated={(path) => {
            void loadFiles();
            setCurrentFile(path);
          }}
          onFileDeleted={() => {
            setCurrentFile('');
            setContent('');
            setOriginalContent('');
            setIsDirty(false);
            void loadFiles();
          }}
          onBackupRestored={(restored) => {
            setContent(restored);
            setOriginalContent(restored);
            setIsDirty(false);
          }}
        />

        {error && (
          <div className="mx-4 mt-2 p-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg text-sm">
            {error}
          </div>
        )}
        {success && (
          <div className="mx-4 mt-2 p-3 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-lg text-sm">
            {success}
          </div>
        )}

        <div className="code-editor-body">
          <div className="file-tree hidden md:block">
            {loadingFiles ? (
              <div className="p-4 text-sm text-gray-500">Načítavam strom súborov…</div>
            ) : (
              <FileTree
                files={files}
                roots={allowedRoots}
                currentFile={currentFile}
                onFileSelect={handleFileSelect}
              />
            )}
          </div>

          <div className="flex-1 flex flex-col min-h-[500px]">
            <EditorToolbar
              language={language}
              onLanguageChange={setLanguage}
              onFormat={() => monacoRef.current?.formatDocument()}
              wordWrap={wordWrap}
              onWordWrapToggle={() => setWordWrap((prev) => !prev)}
            />

            <div className="flex-1 relative min-h-[400px]">
              <MonacoCodeEditor
                ref={monacoRef}
                value={content}
                onChange={handleContentChange}
                language={language}
                path={currentFile}
                wordWrap={wordWrap}
                loading={loadingFile}
              />
              {!loadingFile && (
                <div className="code-editor-status">
                  {lineCount} lines
                </div>
              )}
            </div>
          </div>
        </div>

        <div className="block md:hidden border-t border-gray-200 dark:border-gray-700">
          <details className="group">
            <summary className="px-4 py-2 text-sm font-medium cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
              Files
            </summary>
            <div className="p-2 max-h-64 overflow-y-auto">
              <FileTree
                files={files}
                roots={allowedRoots}
                currentFile={currentFile}
                onFileSelect={handleFileSelect}
                compact
              />
            </div>
          </details>
        </div>
      </div>
  );
};

export const CodeEditor: React.FC<CodeEditorProps> = (props) => (
  <DeveloperUnlockGate>
    <CodeEditorContent {...props} />
  </DeveloperUnlockGate>
);

export default CodeEditor;
