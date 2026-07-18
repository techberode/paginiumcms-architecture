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

  // Získanie zoznamu súborov (prázdne / all = všetky povolené korene)
  getFiles: async (directory?: string): Promise<FileInfo[]> => {
    const response = await apiClient.get<FileInfo[]>('/api/admin/code-editor/files', {
      params: { directory: directory ?? 'all' },
    });
    return response.data || [];
  },

  // Získanie obsahu súboru
  getFile: async (path: string): Promise<CodeEditorFile> => {
    const response = await apiClient.get<CodeEditorFile>('/api/admin/code-editor/file', {
      params: { path },
    });
    return response.data as CodeEditorFile;
  },

  // Uloženie súboru
  saveFile: async (path: string, content: string, message?: string): Promise<{ success: boolean }> => {
    const response = await apiClient.post('/api/admin/code-editor/save', {
      path,
      content,
      message: message || 'File updated via Code Editor',
    });
    return response.data as { success: boolean };
  },

  // Získanie záloh súboru
  getBackups: async (path: string): Promise<string[]> => {
    const response = await apiClient.get<string[]>('/api/admin/code-editor/backups', {
      params: { path },
    });
    return response.data || [];
  },

  // Obnova zálohy
  restoreBackup: async (path: string, backupFile: string): Promise<{ success: boolean }> => {
    const response = await apiClient.post('/api/admin/code-editor/restore', {
      path,
      backup_file: backupFile,
    });
    return response.data as { success: boolean };
  },

  // Vymazanie súboru
  deleteFile: async (path: string): Promise<{ success: boolean }> => {
    const response = await apiClient.delete(`/api/admin/code-editor/file`, {
      params: { path },
    });
    return response.data as { success: boolean };
  },

  // Vytvorenie adresára
  createDirectory: async (path: string): Promise<{ success: boolean }> => {
    const response = await apiClient.post('/api/admin/code-editor/directory', { path });
    return response.data as { success: boolean };
  },

  // Získanie informácií o súbore
  getFileInfo: async (path: string): Promise<FileInfo> => {
    const response = await apiClient.get<FileInfo>('/api/admin/code-editor/info', {
      params: { path },
    });
    return response.data as FileInfo;
  },
};
