// frontend/src/components/CodeEditor/CodeEditor.tsx
import React, { useState, useEffect, useCallback } from 'react';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';
import { FileTree } from './FileTree';
import { EditorToolbar } from './EditorToolbar';
import { DeveloperUnlockGate } from './DeveloperUnlockGate';
import { FileInfo } from '../../api/types';

interface CodeEditorProps {
  initialPath?: string;
}

export const CodeEditor: React.FC<CodeEditorProps> = ({ initialPath = '' }) => {
  const [files, setFiles] = useState<FileInfo[]>([]);
  const [currentFile, setCurrentFile] = useState<string>(initialPath);
  const [content, setContent] = useState<string>('');
  const [originalContent, setOriginalContent] = useState<string>('');
  const [saving, setSaving] = useState(false);
  const [isDirty, setIsDirty] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [language, setLanguage] = useState<string>('text');
  const { get, post, del } = useApi();
  const toast = useToast();

  useEffect(() => {
    loadFiles();
  }, []);

  useEffect(() => {
    if (currentFile) {
      loadFile(currentFile);
    }
  }, [currentFile]);

  const loadFiles = async () => {
    try {
      const response = await get<FileInfo[]>('/api/admin/code-editor/files?directory=backend/app/Modules');
      if (response.success) {
        setFiles(response.data || []);
      }
    } catch (err) {
      toast.error('Failed to load files');
      console.error(err);
    }
  };

  const loadFile = async (path: string) => {
    try {
      setError(null);
      const response = await get<any>(`/api/admin/code-editor/file?path=${encodeURIComponent(path)}`);
      if (response.success && response.data) {
        setContent(response.data.content || '');
        setOriginalContent(response.data.content || '');
        setLanguage(response.data.language || 'text');
        setIsDirty(false);
      } else {
        setError(response.error || 'Failed to load file');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to load file');
      toast.error('Failed to load file');
      console.error(err);
    }
  };

  const handleSave = async () => {
    if (!currentFile || !isDirty) return;

    setSaving(true);
    setError(null);
    setSuccess(null);

    try {
      const response = await post('/api/admin/code-editor/save', {
        path: currentFile,
        content: content,
      });
      
      if (response.success) {
        setOriginalContent(content);
        setIsDirty(false);
        setSuccess('File saved successfully!');
        toast.success('File saved successfully!');
        setTimeout(() => setSuccess(null), 3000);
      } else {
        const policyErrors = response.errors
          ? Object.values(response.errors).flat().join('; ')
          : '';
        const message = policyErrors || response.error || 'Failed to save file';
        setError(message);
        toast.error(message);
      }
    } catch (err: any) {
      setError(err.message || 'Failed to save file');
      toast.error('Failed to save file');
    } finally {
      setSaving(false);
    }
  };

  const handleContentChange = (newContent: string) => {
    setContent(newContent);
    setIsDirty(newContent !== originalContent);
  };

  const handleFileSelect = (path: string) => {
    setCurrentFile(path);
  };

  const handleRevert = () => {
    setContent(originalContent);
    setIsDirty(false);
    toast.info('Changes reverted');
  };

  return (
    <DeveloperUnlockGate>
    <div className="flex flex-col h-full bg-white dark:bg-gray-800 rounded-lg shadow">
      {/* Header */}
      <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between flex-wrap gap-2">
        <div className="flex items-center gap-3">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
            Code Editor
          </h2>
          {currentFile && (
            <span className="text-sm text-gray-500 dark:text-gray-400 truncate max-w-[300px]">
              {currentFile}
            </span>
          )}
        </div>
        <div className="flex items-center gap-2">
          {isDirty && (
            <span className="text-sm text-yellow-600 dark:text-yellow-400">
              Unsaved changes
            </span>
          )}
          <button
            onClick={handleRevert}
            disabled={!isDirty}
            className="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Revert
          </button>
          <button
            onClick={handleSave}
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

      {/* Error/Success messages */}
      {error && (
        <div className="mx-4 mt-2 p-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg text-sm">
          ❌ {error}
        </div>
      )}
      {success && (
        <div className="mx-4 mt-2 p-3 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-lg text-sm">
          ✅ {success}
        </div>
      )}

      {/* Editor body */}
      <div className="flex flex-1 min-h-[500px]">
        {/* File tree - hidden on mobile */}
        <div className="hidden md:block w-64 border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
          <FileTree
            files={files}
            currentFile={currentFile}
            onFileSelect={handleFileSelect}
          />
        </div>

        {/* Editor */}
        <div className="flex-1 flex flex-col min-h-[500px]">
          <EditorToolbar
            language={language}
            onLanguageChange={setLanguage}
            onFormat={() => {}}
          />
          
          <div className="flex-1 relative">
            <textarea
              value={content}
              onChange={(e) => handleContentChange(e.target.value)}
              className="w-full h-full p-4 font-mono text-sm bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 resize-none focus:outline-none"
              spellCheck={false}
              placeholder="// Edit your code here..."
            />
            <div className="absolute bottom-2 right-2 text-xs text-gray-400 dark:text-gray-500">
              {content.split('\n').length} lines
            </div>
          </div>
        </div>
      </div>

      {/* Mobile file browser */}
      <div className="block md:hidden border-t border-gray-200 dark:border-gray-700">
        <details className="group">
          <summary className="px-4 py-2 text-sm font-medium cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
            📁 Files
          </summary>
          <div className="p-2 max-h-64 overflow-y-auto">
            <FileTree
              files={files}
              currentFile={currentFile}
              onFileSelect={handleFileSelect}
              compact
            />
          </div>
        </details>
      </div>
    </div>
    </DeveloperUnlockGate>
  );
};

export default CodeEditor;
