// frontend/src/components/backend/MediaManager.tsx
import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  Copy,
  FolderPlus,
  Loader2,
  Trash2,
  Upload,
  FileText,
  Pencil,
  Check,
  X,
  Folder,
  ChevronRight,
  Zap,
  Expand,
} from 'lucide-react';
import { useToast } from '../../hooks/useToast';
import { getSettings } from '../../api/settings';
import {
  bulkDeleteMedia,
  createMediaFolder,
  deleteMedia,
  formatMediaSize,
  importStockImage,
  isImageMedia,
  isPreviewableMedia,
  listMedia,
  listMediaFolders,
  listMediaFormats,
  listStockImageTopics,
  MediaFile,
  resolveAdminMediaPreviewUrl,
  resolveMediaUrl,
  resolvePublicMediaUrl,
  StockImageTopic,
  updateMediaMetadata,
  uploadMedia,
} from '../../api/media';
import {
  MediaPreviewLightbox,
  MediaPreviewMode,
} from './MediaPreviewLightbox';

type TypeFilter = 'all' | 'image';

function folderLabel(folder: string): string {
  return folder === '' ? 'All media' : folder;
}

export const MediaManager: React.FC = () => {
  const toast = useToast();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [items, setItems] = useState<MediaFile[]>([]);
  const [folders, setFolders] = useState<string[]>(['']);
  const [currentFolder, setCurrentFolder] = useState('');
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [dragOver, setDragOver] = useState(false);
  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState<TypeFilter>('all');
  const [editingPath, setEditingPath] = useState<string | null>(null);
  const [editAlt, setEditAlt] = useState('');
  const [editTitle, setEditTitle] = useState('');
  const [selectedPaths, setSelectedPaths] = useState<string[]>([]);
  const [stockTopics, setStockTopics] = useState<StockImageTopic[]>([]);
  const [stockTopic, setStockTopic] = useState('tech');
  const [stockImporting, setStockImporting] = useState(false);
  const [previewFile, setPreviewFile] = useState<MediaFile | null>(null);
  const [previewMode, setPreviewMode] = useState<MediaPreviewMode>('fit');
  const [uploadAccept, setUploadAccept] = useState(
    'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf'
  );
  const [previewableMimeTypes, setPreviewableMimeTypes] = useState<string[]>([]);

  useEffect(() => {
    void (async () => {
      const [topics, settings, formats] = await Promise.all([
        listStockImageTopics(),
        getSettings(),
        listMediaFormats(),
      ]);
      if (topics.length > 0) {
        setStockTopics(topics);
      }
      const configured = String(settings?.values?.media?.stockImageTopic ?? 'tech');
      setStockTopic(configured);
      if (formats.accept) {
        setUploadAccept(formats.accept);
      }
      setPreviewableMimeTypes(formats.previewableMimeTypes);
    })();
  }, []);

  const loadMedia = useCallback(async () => {
    setLoading(true);
    try {
      const filters =
        typeFilter === 'image'
          ? { type: 'image' as const, folder: currentFolder }
          : { folder: currentFolder };
      const [files, folderList] = await Promise.all([
        listMedia(filters),
        listMediaFolders(),
      ]);
      setItems(files);
      setFolders(folderList.length > 0 ? folderList : ['']);
      setSelectedPaths((prev) => prev.filter((path) => files.some((file) => file.path === path)));
    } catch (error) {
      toast.error('Failed to load media library.');
      console.error(error);
    } finally {
      setLoading(false);
    }
  }, [currentFolder, typeFilter]);

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
      const result = await uploadMedia(file, '', currentFolder);
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

  const handleStockImport = async () => {
    setStockImporting(true);
    const result = await importStockImage(stockTopic, currentFolder);
    if (result.ok) {
      const label = stockTopics.find((topic) => topic.id === stockTopic)?.label ?? stockTopic;
      toast.success(`Stock image imported (${label}).`);
      await loadMedia();
    } else {
      toast.error(result.error);
    }
    setStockImporting(false);
  };

  const handleCreateFolder = async () => {
    const base = currentFolder === '' ? '' : `${currentFolder}/`;
    const name = window.prompt('New folder name (letters, numbers, dash, underscore):');
    if (!name) {
      return;
    }

    const folder = `${base}${name.trim()}`.replace(/^\/+/, '');
    const ok = await createMediaFolder(folder);
    if (ok) {
      toast.success('Folder created.');
      setCurrentFolder(folder);
      await loadMedia();
    } else {
      toast.error('Failed to create folder.');
    }
  };

  const toggleSelected = (path: string) => {
    setSelectedPaths((prev) =>
      prev.includes(path) ? prev.filter((item) => item !== path) : [...prev, path]
    );
  };

  const handleBulkDelete = async () => {
    if (selectedPaths.length === 0) {
      return;
    }

    if (
      !confirm(`Delete ${selectedPaths.length} selected file(s)? This cannot be undone.`)
    ) {
      return;
    }

    const deleted = await bulkDeleteMedia(selectedPaths);
    if (deleted > 0) {
      toast.success(`${deleted} file(s) deleted.`);
      setSelectedPaths([]);
      await loadMedia();
    } else {
      toast.error('Failed to delete selected files.');
    }
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
    const relative = resolvePublicMediaUrl(file.url);
    const url =
      typeof window !== 'undefined' && window.location?.origin
        ? `${window.location.origin}${relative}`
        : relative;
    try {
      await navigator.clipboard.writeText(url);
      toast.success('URL copied to clipboard.');
    } catch {
      toast.error('Could not copy URL.');
    }
  };

  const filteredItems = items.filter((item) => {
    const q = search.toLowerCase();
    return (
      item.fileName.toLowerCase().includes(q) ||
      item.altText.toLowerCase().includes(q) ||
      (item.title ?? '').toLowerCase().includes(q) ||
      item.mimeType.toLowerCase().includes(q)
    );
  });

  const openPreview = (file: MediaFile, mode: MediaPreviewMode = 'fit') => {
    if (!isPreviewableMedia(file, previewableMimeTypes)) {
      window.open(resolvePublicMediaUrl(file.url), '_blank', 'noopener,noreferrer');
      return;
    }
    setPreviewMode(mode);
    setPreviewFile(file);
  };

  const closePreview = () => setPreviewFile(null);

  const previewIndex = previewFile
    ? filteredItems.findIndex((item) => item.path === previewFile.path)
    : -1;

  const showPreviousPreview = () => {
    if (previewIndex > 0) {
      setPreviewFile(filteredItems[previewIndex - 1]);
    }
  };

  const showNextPreview = () => {
    if (previewIndex >= 0 && previewIndex < filteredItems.length - 1) {
      setPreviewFile(filteredItems[previewIndex + 1]);
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

  const startEditMeta = (file: MediaFile) => {
    setEditingPath(file.path);
    setEditAlt(file.altText);
    setEditTitle(file.title ?? '');
  };

  const cancelEditMeta = () => {
    setEditingPath(null);
    setEditAlt('');
    setEditTitle('');
  };

  const saveEditMeta = async (path: string) => {
    const ok = await updateMediaMetadata(path, { altText: editAlt, title: editTitle });
    if (ok) {
      toast.success('Metadata updated.');
      setEditingPath(null);
      setEditAlt('');
      setEditTitle('');
      await loadMedia();
    } else {
      toast.error('Failed to update metadata.');
    }
  };

  const childFolders = folders.filter(
    (folder) =>
      folder !== '' &&
      (currentFolder === ''
        ? !folder.includes('/')
        : folder.startsWith(`${currentFolder}/`) &&
          folder.slice(currentFolder.length + 1).split('/').length === 1)
  );

  const breadcrumbParts =
    currentFolder === '' ? [] : currentFolder.split('/').filter(Boolean);

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center flex-wrap gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Media Library</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Upload, browse folders, and manage site assets.
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          {selectedPaths.length > 0 && (
            <button type="button" className="btn btn-danger" onClick={() => void handleBulkDelete()}>
              <Trash2 className="w-4 h-4 inline mr-2" />
              Delete selected ({selectedPaths.length})
            </button>
          )}
          <button type="button" className="btn btn-secondary" onClick={() => void handleCreateFolder()}>
            <FolderPlus className="w-4 h-4 inline mr-2" />
            New folder
          </button>
          {stockTopics.length > 0 && (
            <>
              <select
                value={stockTopic}
                onChange={(e) => setStockTopic(e.target.value)}
                className="form-input w-auto"
                aria-label="Stock image topic"
                title="Topic for generated stock images"
              >
                {stockTopics.map((topic) => (
                  <option key={topic.id} value={topic.id}>
                    {topic.label} ({topic.count})
                  </option>
                ))}
              </select>
              <button
                type="button"
                className="btn btn-secondary"
                disabled={stockImporting || uploading}
                onClick={() => void handleStockImport()}
                title="Import a random stock image matching the site topic"
              >
                {stockImporting ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin inline mr-2" />
                    Generating…
                  </>
                ) : (
                  <>
                    <Zap className="w-4 h-4 inline mr-2" />
                    Generovať z knižnice
                  </>
                )}
              </button>
            </>
          )}
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
        </div>
        <input
          ref={fileInputRef}
          type="file"
          multiple
          accept={uploadAccept}
          className="hidden"
          onChange={handleFileInputChange}
        />
      </div>

      <nav className="flex flex-wrap items-center gap-1 text-sm text-gray-600 dark:text-gray-300">
        <button
          type="button"
          className={`hover:text-indigo-600 ${currentFolder === '' ? 'font-semibold text-indigo-600' : ''}`}
          onClick={() => setCurrentFolder('')}
        >
          All media
        </button>
        {breadcrumbParts.map((part, index) => {
          const path = breadcrumbParts.slice(0, index + 1).join('/');
          const isLast = index === breadcrumbParts.length - 1;
          return (
            <span key={path} className="flex items-center gap-1">
              <ChevronRight className="w-4 h-4 text-gray-400" />
              <button
                type="button"
                className={`hover:text-indigo-600 ${isLast ? 'font-semibold text-indigo-600' : ''}`}
                onClick={() => setCurrentFolder(path)}
              >
                {part}
              </button>
            </span>
          );
        })}
      </nav>

      {childFolders.length > 0 && (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
          {childFolders.map((folder) => (
            <button
              key={folder}
              type="button"
              className="card card-body flex items-center gap-3 text-left hover:border-indigo-400 transition-colors"
              onClick={() => setCurrentFolder(folder)}
            >
              <Folder className="w-8 h-8 text-indigo-500 shrink-0" />
              <span className="font-medium text-sm truncate">{folder.split('/').pop()}</span>
            </button>
          ))}
        </div>
      )}

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
            Upload to: {folderLabel(currentFolder)}
          </p>
        </div>
      </div>

      <div className="flex flex-wrap gap-4">
        <div className="flex-1 min-w-[200px]">
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search by name, title, alt text, or type…"
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
            No media files in {folderLabel(currentFolder)}.
          </div>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          {filteredItems.map((file) => (
            <MediaCard
              key={file.id}
              file={file}
              selected={selectedPaths.includes(file.path)}
              onToggleSelect={() => toggleSelected(file.path)}
              editing={editingPath === file.path}
              editAlt={editAlt}
              editTitle={editTitle}
              onEditAltChange={setEditAlt}
              onEditTitleChange={setEditTitle}
              onStartEdit={() => startEditMeta(file)}
              onCancelEdit={cancelEditMeta}
              onSaveEdit={() => saveEditMeta(file.path)}
              onCopyUrl={() => handleCopyUrl(file)}
              onPreview={() => openPreview(file)}
              onPreviewNative={() => openPreview(file, 'native')}
              onDelete={() => handleDelete(file)}
            />
          ))}
        </div>
      )}

      <MediaPreviewLightbox
        file={previewFile}
        mode={previewMode}
        onClose={closePreview}
        onModeChange={setPreviewMode}
        onPrevious={showPreviousPreview}
        onNext={showNextPreview}
        hasPrevious={previewIndex > 0}
        hasNext={previewIndex >= 0 && previewIndex < filteredItems.length - 1}
      />
    </div>
  );
};

interface MediaCardProps {
  file: MediaFile;
  selected: boolean;
  onToggleSelect: () => void;
  editing: boolean;
  editAlt: string;
  editTitle: string;
  onEditAltChange: (value: string) => void;
  onEditTitleChange: (value: string) => void;
  onStartEdit: () => void;
  onCancelEdit: () => void;
  onSaveEdit: () => void;
  onCopyUrl: () => void;
  onPreview: () => void;
  onPreviewNative: () => void;
  onDelete: () => void;
}

const MediaCard: React.FC<MediaCardProps> = ({
  file,
  selected,
  onToggleSelect,
  editing,
  editAlt,
  editTitle,
  onEditAltChange,
  onEditTitleChange,
  onStartEdit,
  onCancelEdit,
  onSaveEdit,
  onCopyUrl,
  onPreview,
  onPreviewNative,
  onDelete,
}) => {
  const previewUrl = resolveAdminMediaPreviewUrl(file.path);
  const fallbackUrl = resolvePublicMediaUrl(file.url);
  const [thumbnailSrc, setThumbnailSrc] = useState(previewUrl);
  const isImage = isImageMedia(file);

  useEffect(() => {
    setThumbnailSrc(resolveAdminMediaPreviewUrl(file.path));
  }, [file.path]);

  return (
    <div className={`card overflow-hidden flex flex-col ${selected ? 'ring-2 ring-indigo-500' : ''}`}>
      <div className="aspect-video bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden relative">
        <label className="absolute top-2 left-2 z-10 bg-white/90 dark:bg-gray-900/90 rounded p-1 cursor-pointer">
          <input
            type="checkbox"
            checked={selected}
            onChange={onToggleSelect}
            aria-label={`Select ${file.fileName}`}
            className="rounded border-gray-300"
          />
        </label>
        {isImage ? (
          <button
            type="button"
            className="w-full h-full group/preview relative"
            onClick={onPreview}
            aria-label={`Preview ${file.fileName}`}
          >
            <img
              src={thumbnailSrc}
              alt={file.altText || file.fileName}
              className="w-full h-full object-cover"
              loading="lazy"
              onError={() => {
                if (thumbnailSrc !== fallbackUrl) {
                  setThumbnailSrc(fallbackUrl);
                }
              }}
            />
            <span className="absolute inset-0 bg-black/0 group-hover/preview:bg-black/30 transition-colors flex items-center justify-center opacity-0 group-hover/preview:opacity-100">
              <Expand className="w-8 h-8 text-white drop-shadow" />
            </span>
          </button>
        ) : (
          <FileText className="w-12 h-12 text-gray-400" />
        )}
      </div>
      <div className="card-body p-4 flex-1 flex flex-col gap-2">
        <p className="font-medium text-sm text-gray-900 dark:text-white truncate" title={file.fileName}>
          {file.title || file.fileName}
        </p>
        <p className="text-xs text-gray-500 dark:text-gray-400 truncate" title={file.fileName}>
          {file.fileName}
        </p>
        <p className="text-xs text-gray-500 dark:text-gray-400">
          {file.mimeType} · {formatMediaSize(file.sizeBytes)}
        </p>

        {editing ? (
          <div className="space-y-2">
            <input
              type="text"
              value={editTitle}
              onChange={(e) => onEditTitleChange(e.target.value)}
              placeholder="Title"
              className="form-input text-sm"
              aria-label="Title"
            />
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
          {!editing && isImage && (
            <>
              <button
                type="button"
                className="btn btn-secondary text-xs px-2 py-1"
                title="Preview (fit to screen)"
                onClick={onPreview}
              >
                <Expand className="w-3 h-3" />
              </button>
              <button
                type="button"
                className="btn btn-secondary text-xs px-2 py-1"
                title="Preview at native resolution"
                onClick={onPreviewNative}
              >
                1:1
              </button>
            </>
          )}
          {!editing && (
            <button
              type="button"
              className="btn btn-secondary text-xs px-2 py-1"
              title="Edit metadata"
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
