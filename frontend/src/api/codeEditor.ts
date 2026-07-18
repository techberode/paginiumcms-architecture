// frontend/src/api/codeEditor.ts
import apiClient from './client';
import { FileInfo, CodeEditorFile } from './types';

export const codeEditorApi = {
  getAllowedDirectories: async (): Promise<string[]> => {
    const response = await apiClient.get<{ directories?: string[] }>(
      '/api/admin/code-editor/directories'
    );
    return response.data?.directories ?? [];
  },

  getFiles: async (directory?: string): Promise<FileInfo[]> => {
    const response = await apiClient.get<FileInfo[]>('/api/admin/code-editor/files', {
      params: { directory: directory ?? 'all' },
    });
    return response.data || [];
  },

  getFile: async (path: string): Promise<CodeEditorFile | null> => {
    const response = await apiClient.get<CodeEditorFile>('/api/admin/code-editor/file', {
      params: { path },
    });
    if (!response.success || !response.data) {
      return null;
    }
    return {
      ...response.data,
      content: response.data.content ?? '',
      path: response.data.path ?? path,
      language: response.data.language ?? 'plaintext',
    };
  },

  saveFile: async (path: string, content: string): Promise<{ success: boolean; error?: string }> => {
    const response = await apiClient.post('/api/admin/code-editor/save', {
      path,
      content,
      message: 'File updated via Code Editor',
    });
    return { success: Boolean(response.success), error: response.error };
  },

  getBackups: async (path: string): Promise<string[]> => {
    const response = await apiClient.get<string[]>('/api/admin/code-editor/backups', {
      params: { path },
    });
    return response.data ?? [];
  },

  restoreBackup: async (path: string, backupFile: string): Promise<string | null> => {
    const response = await apiClient.post<{ content?: string }>('/api/admin/code-editor/restore', {
      path,
      backup_file: backupFile,
    });
    if (!response.success) {
      return null;
    }
    return response.data?.content ?? null;
  },

  deleteFile: async (path: string): Promise<boolean> => {
    const response = await apiClient.delete('/api/admin/code-editor/file', {
      params: { path },
    });
    return Boolean(response.success);
  },

  createFile: async (path: string, content: string): Promise<boolean> => {
    const response = await apiClient.post('/api/admin/code-editor/file', { path, content });
    return Boolean(response.success);
  },
};
