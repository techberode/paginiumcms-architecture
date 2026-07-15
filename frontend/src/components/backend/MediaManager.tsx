// frontend/src/components/backend/MediaManager.tsx
import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  Copy,
  Loader2,
  Trash2,
  Upload,
  FileText,
  Pencil,
  Check,
  X,
} from 'lucide-react';
import { useToast } from '../../hooks/useToast';
import {
  deleteMedia,
  formatMediaSize,
  isImageMedia,
  listMedia,
  MediaFile,
  resolveMediaUrl,
  updateMediaAlt,
  uploadMedia,
} from '../../api/media';

type TypeFilter = 'all' | 'image';

export const MediaManager: React.FC = () => {
  const toast = useToast();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [items, setItems] = useState<MediaFile[]>([]);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [dragOver, setDragOver] = useState(false);
  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState<TypeFilter>('all');
  const [editingPath, setEditingPath] = useState<string | null>(null);
  const [editAlt, setEditAlt] = useState('');

  const loadMedia = useCallback(async () => {
    setLoading(true);
    try {
      const filters = typeFilter === 'image' ? { type: 'image' as const } : {};
      const files = await listMedia(filters);
      setItems(files);
    } catch (error) {
      toast.error('Failed to load media library.');
      console.error(error);
    } finally {
      setLoading(false);
    }
  }, [typeFilter]);

  useEffect(() => {
    void loadMedia();
  }, [loadMedia]);

  const handleUploadFiles = async (files: FileList | File[]) => {
    const list = Array.from(files);
    if (list.length === 0) {
      return;
    }

    setUploading(true);
    let successCount = 0;

    for (const file of list) {
      const result = await uploadMedia(file);
      if (result.ok) {
        successCount += 1;
      } else {
        toast.error(`${file.name}: ${result.error}`);
      }
    }

    if (successCount > 0) {
      toast.success(
        successCount === 1 ? 'File uploaded successfully.' : `${successCount} files uploaded.`
      );
      await loadMedia();
    }

    setUploading(false);
  };

  const handleFileInputChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    if (event.target.files) {
      void handleUploadFiles(event.target.files);
      event.target.value = '';
    }
  };

  const handleDrop = (event: React.DragEvent) => {
    event.preventDefault();
    setDragOver(false);
    if (event.dataTransfer.files.length > 0) {
      void handleUploadFiles(event.dataTransfer.files);
    }
  };

  const handleCopyUrl = async (file: MediaFile) => {
    const url = resolveMediaUrl(file.url);
    try {
      await navigator.clipboard.writeText(url);
      toast.success('URL copied to clipboard.');
    } catch {
      toast.error('Could not copy URL.');
    }
  };

  const handleDelete = async (file: MediaFile) => {
    if (!confirm(`Delete "${file.fileName}"? This cannot be undone.`)) {
      return;
    }

    const ok = await deleteMedia(file.path);
    if (ok) {
      toast.success('Media deleted.');
      await loadMedia();
    } else {
      toast.error('Failed to delete media.');
    }
  };

  const startEditAlt = (file: MediaFile) => {
    setEditingPath(file.path);
    setEditAlt(file.altText);
  };

  const cancelEditAlt = () => {
    setEditingPath(null);
    setEditAlt('');
  };

  const saveEditAlt = async (path: string) => {
    const ok = await updateMediaAlt(path, editAlt);
    if (ok) {
      toast.success('Alt text updated.');
      setEditingPath(null);
      setEditAlt('');
      await loadMedia();
    } else {
      toast.error('Failed to update alt text.');
    }
  };

  const filteredItems = items.filter((item) => {
    const q = search.toLowerCase();
    return (
      item.fileName.toLowerCase().includes(q) ||
      item.altText.toLowerCase().includes(q) ||
      item.mimeType.toLowerCase().includes(q)
    );
  });

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center flex-wrap gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Media Library</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Upload, browse, and manage site assets.
          </p>
        </div>
        <button
          type="button"
          className="btn btn-primary"
          disabled={uploading}
          onClick={() => fileInputRef.current?.click()}
        >
          {uploading ? (
            <>
              <Loader2 className="w-4 h-4 animate-spin inline mr-2" />
              Uploading…
            </>
          ) : (
            <>
              <Upload className="w-4 h-4 inline mr-2" />
              Upload files
            </>
          )}
        </button>
        <input
          ref={fileInputRef}
          type="file"
          multiple
          accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf"
          className="hidden"
          onChange={handleFileInputChange}
        />
      </div>

      <div
        role="button"
        tabIndex={0}
        className={`card border-2 border-dashed transition-colors cursor-pointer ${
          dragOver
            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30'
            : 'border-gray-200 dark:border-gray-700'
        }`}
        onDragOver={(e) => {
          e.preventDefault();
          setDragOver(true);
        }}
        onDragLeave={() => setDragOver(false)}
        onDrop={handleDrop}
        onClick={() => !uploading && fileInputRef.current?.click()}
        onKeyDown={(e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            fileInputRef.current?.click();
          }
        }}
      >
        <div className="card-body text-center py-10">
          <Upload className="w-10 h-10 mx-auto text-indigo-500 mb-3" />
          <p className="font-medium text-gray-900 dark:text-white">
            Drag & drop files here, or click to browse
          </p>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            JPEG, PNG, GIF, WebP, SVG, PDF
          </p>
        </div>
      </div>

      <div className="flex flex-wrap gap-4">
        <div className="flex-1 min-w-[200px]">
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search by name, alt text, or type…"
            className="form-input"
          />
        </div>
        <select
          value={typeFilter}
          onChange={(e) => setTypeFilter(e.target.value as TypeFilter)}
          className="form-input w-auto"
        >
          <option value="all">All files</option>
          <option value="image">Images only</option>
        </select>
      </div>

      {loading ? (
        <div className="flex justify-center items-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
        </div>
      ) : filteredItems.length === 0 ? (
        <div className="card">
          <div className="card-body text-center py-12 text-gray-500 dark:text-gray-400">
            No media files found.
          </div>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          {filteredItems.map((file) => (
            <MediaCard
              key={file.id}
              file={file}
              editing={editingPath === file.path}
              editAlt={editAlt}
              onEditAltChange={setEditAlt}
              onStartEdit={() => startEditAlt(file)}
              onCancelEdit={cancelEditAlt}
              onSaveEdit={() => saveEditAlt(file.path)}
              onCopyUrl={() => handleCopyUrl(file)}
              onDelete={() => handleDelete(file)}
            />
          ))}
        </div>
      )}
    </div>
  );
};

interface MediaCardProps {
  file: MediaFile;
  editing: boolean;
  editAlt: string;
  onEditAltChange: (value: string) => void;
  onStartEdit: () => void;
  onCancelEdit: () => void;
  onSaveEdit: () => void;
  onCopyUrl: () => void;
  onDelete: () => void;
}

const MediaCard: React.FC<MediaCardProps> = ({
  file,
  editing,
  editAlt,
  onEditAltChange,
  onStartEdit,
  onCancelEdit,
  onSaveEdit,
  onCopyUrl,
  onDelete,
}) => {
  const previewUrl = resolveMediaUrl(file.url);
  const isImage = isImageMedia(file);

  return (
    <div className="card overflow-hidden flex flex-col">
      <div className="aspect-video bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden">
        {isImage ? (
          <img
            src={previewUrl}
            alt={file.altText || file.fileName}
            className="w-full h-full object-cover"
            loading="lazy"
          />
        ) : (
          <FileText className="w-12 h-12 text-gray-400" />
        )}
      </div>
      <div className="card-body p-4 flex-1 flex flex-col gap-2">
        <p className="font-medium text-sm text-gray-900 dark:text-white truncate" title={file.fileName}>
          {file.fileName}
        </p>
        <p className="text-xs text-gray-500 dark:text-gray-400">
          {file.mimeType} · {formatMediaSize(file.sizeBytes)}
        </p>

        {editing ? (
          <div className="space-y-2">
            <input
              type="text"
              value={editAlt}
              onChange={(e) => onEditAltChange(e.target.value)}
              placeholder="Alt text"
              className="form-input text-sm"
              aria-label="Alt text"
            />
            <div className="flex gap-2">
              <button type="button" className="btn btn-primary text-xs px-2 py-1" onClick={onSaveEdit}>
                <Check className="w-3 h-3 inline" />
              </button>
              <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={onCancelEdit}>
                <X className="w-3 h-3 inline" />
              </button>
            </div>
          </div>
        ) : (
          <p className="text-xs text-gray-500 dark:text-gray-400 truncate" title={file.altText || 'No alt text'}>
            {file.altText ? `Alt: ${file.altText}` : 'No alt text'}
          </p>
        )}

        <div className="flex gap-2 mt-auto pt-2">
          {!editing && (
            <button
              type="button"
              className="btn btn-secondary text-xs px-2 py-1"
              title="Edit alt text"
              onClick={onStartEdit}
            >
              <Pencil className="w-3 h-3" />
            </button>
          )}
          <button
            type="button"
            className="btn btn-secondary text-xs px-2 py-1"
            title="Copy URL"
            onClick={onCopyUrl}
          >
            <Copy className="w-3 h-3" />
          </button>
          <button
            type="button"
            className="btn btn-danger text-xs px-2 py-1 ml-auto"
            title="Delete"
            onClick={onDelete}
          >
            <Trash2 className="w-3 h-3" />
          </button>
        </div>
      </div>
    </div>
  );
};

export default MediaManager;
