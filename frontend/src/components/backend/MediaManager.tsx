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
import { useOpenLinksInNewTab } from '../../hooks/useOpenLinksInNewTab';
import { openExternalUrl } from '../../utils/linkTarget';
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
  resolvePublicMediaUrl,
  StockImageTopic,
  updateMediaMetadata,
  uploadMedia,
} from '../../api/media';
import {
  MediaPreviewLightbox,
  MediaPreviewMode,
} from './MediaPreviewLightbox';
import { AdminListToolbar } from './AdminListToolbar';
import { AdminListPagination } from './AdminListPagination';
import { BulkActionBar } from './BulkActionBar';
import { AdminListSkeleton } from '../ui/AdminListSkeleton';
import { MediaMetadataModal } from './MediaMetadataModal';
import { SeoHealthBadge } from './SeoHealthBadge';
import { useAdminViewMode } from '../../hooks/useAdminViewMode';
import { useAdminListPageSize } from '../../hooks/useAdminListPageSize';
import { useMediaListQueryParams } from '../../hooks/useAdminListQueryParams';
import { SortableTableHeader } from './SortableTableHeader';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { applyClientListView } from '../../utils/clientListView';
import { evaluateMediaSeo } from '../../utils/seoHealth';

type TypeFilter = 'all' | 'image';

function folderLabel(folder: string): string {
  return folder === '' ? 'All media' : folder;
}

export const MediaManager: React.FC = () => {
  const toast = useToast();
  const openInNewTab = useOpenLinksInNewTab();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [items, setItems] = useState<MediaFile[]>([]);
  const [folders, setFolders] = useState<string[]>(['']);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [dragOver, setDragOver] = useState(false);
  const {
    page,
    search,
    seoIssuesOnly,
    sortField,
    sortDirection,
    handleSort,
    setSearch,
    setPage,
    setSeoIssuesOnly,
    resetFilters,
    folder: currentFolder,
    typeFilter,
    setFolder: setCurrentFolder,
    setTypeFilter,
  } = useMediaListQueryParams('uploadedAt', 'desc');
  const [editingPath, setEditingPath] = useState<string | null>(null);
  const [editingFile, setEditingFile] = useState<MediaFile | null>(null);
  const [editAlt, setEditAlt] = useState('');
  const [editTitle, setEditTitle] = useState('');
  const [savingMeta, setSavingMeta] = useState(false);
  const [stockTopics, setStockTopics] = useState<StockImageTopic[]>([]);
  const [stockTopic, setStockTopic] = useState('tech');
  const [stockImporting, setStockImporting] = useState(false);
  const [previewFile, setPreviewFile] = useState<MediaFile | null>(null);
  const [previewMode, setPreviewMode] = useState<MediaPreviewMode>('fit');
  const [uploadAccept, setUploadAccept] = useState(
    'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf'
  );
  const [previewableMimeTypes, setPreviewableMimeTypes] = useState<string[]>([]);
  const [pageSize, setPageSize] = useAdminListPageSize('media');
  const { mode: viewMode, setMode: setViewMode } = useAdminViewMode('media', 'preview');
  const hasActiveFilters =
    search.trim().length >= 2 ||
    seoIssuesOnly ||
    typeFilter !== 'all' ||
    currentFolder !== '' ||
    sortField !== 'uploadedAt' ||
    sortDirection !== 'desc' ||
    page > 1;

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
    } catch (error) {
      toast.error('Failed to load media library.');
      console.error(error);
    } finally {
      setLoading(false);
    }
  }, [currentFolder, typeFilter, toast]);

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
    if (seoIssuesOnly && evaluateMediaSeo(item) === 'ok') {
      return false;
    }

    return true;
  });

  useEffect(() => {
    setPage(1);
  }, [pageSize, setPage]);

  const listView = applyClientListView(filteredItems, {
    search,
    searchText: (item) =>
      `${item.fileName} ${item.title ?? ''} ${item.altText ?? ''} ${item.mimeType}`,
    sortField,
    sortDirection,
    sortFields: [
      { value: 'fileName', label: 'Názov', getValue: (item) => item.fileName },
      { value: 'title', label: 'Titulok', getValue: (item) => item.title || item.altText || '' },
      { value: 'mimeType', label: 'Typ', getValue: (item) => item.mimeType },
      { value: 'size', label: 'Veľkosť', getValue: (item) => item.sizeBytes },
      { value: 'uploadedAt', label: 'Dátum', getValue: (item) => item.uploadedAt },
    ],
    page,
    pageSize,
  });

  const pagedItems = listView.items;

  const navigableItems = applyClientListView(filteredItems, {
    search,
    searchText: (item) =>
      `${item.fileName} ${item.title ?? ''} ${item.altText ?? ''} ${item.mimeType}`,
    sortField,
    sortDirection,
    sortFields: [
      { value: 'fileName', label: 'Názov', getValue: (item) => item.fileName },
      { value: 'title', label: 'Titulok', getValue: (item) => item.title || item.altText || '' },
      { value: 'mimeType', label: 'Typ', getValue: (item) => item.mimeType },
      { value: 'size', label: 'Veľkosť', getValue: (item) => item.sizeBytes },
      { value: 'uploadedAt', label: 'Dátum', getValue: (item) => item.uploadedAt },
    ],
    page: 1,
    pageSize: Math.max(filteredItems.length, 1),
  }).items;

  const bulkSelection = useBulkSelection(
    pagedItems.map((file) => file.path),
    `${currentFolder}:${typeFilter}:${search}:${seoIssuesOnly}:${sortField}:${sortDirection}:${page}:${pageSize}`
  );

  const handleBulkDelete = async () => {
    if (bulkSelection.count === 0) {
      return;
    }

    if (
      !confirm(`Delete ${bulkSelection.count} selected file(s)? This cannot be undone.`)
    ) {
      return;
    }

    const deleted = await bulkDeleteMedia(bulkSelection.selectedIds);
    if (deleted > 0) {
      toast.success(`${deleted} file(s) deleted.`);
      bulkSelection.clear();
      await loadMedia();
    } else {
      toast.error('Failed to delete selected files.');
    }
  };

  const openPreview = (file: MediaFile, mode: MediaPreviewMode = 'fit') => {
    if (!isPreviewableMedia(file, previewableMimeTypes)) {
      openExternalUrl(resolvePublicMediaUrl(file.url), openInNewTab);
      return;
    }
    setPreviewMode(mode);
    setPreviewFile(file);
  };

  const closePreview = () => setPreviewFile(null);

  const previewIndex = previewFile
    ? navigableItems.findIndex((item) => item.path === previewFile.path)
    : -1;

  const showPreviousPreview = () => {
    if (previewIndex > 0) {
      setPreviewFile(navigableItems[previewIndex - 1]);
    }
  };

  const showNextPreview = () => {
    if (previewIndex >= 0 && previewIndex < navigableItems.length - 1) {
      setPreviewFile(navigableItems[previewIndex + 1]);
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
    setEditAlt(file.altText);
    setEditTitle(file.title ?? '');
    if (viewMode === 'preview') {
      setEditingPath(file.path);
      setEditingFile(null);
      return;
    }
    setEditingFile(file);
    setEditingPath(null);
  };

  const cancelEditMeta = () => {
    setEditingPath(null);
    setEditingFile(null);
    setEditAlt('');
    setEditTitle('');
  };

  const saveEditMeta = async (path: string) => {
    setSavingMeta(true);
    try {
      const ok = await updateMediaMetadata(path, { altText: editAlt, title: editTitle });
      if (ok) {
        toast.success('Metadata updated.');
        cancelEditMeta();
        await loadMedia();
      } else {
        toast.error('Failed to update metadata.');
      }
    } finally {
      setSavingMeta(false);
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

      <div className="w-full">
        <AdminListToolbar
          search={search}
          onSearchChange={setSearch}
          searchPlaceholder="Hľadať podľa názvu, titulku, alt textu alebo typu…"
          viewMode={viewMode}
          onViewModeChange={setViewMode}
          showViewToggle
          seoIssuesOnly={seoIssuesOnly}
          onSeoIssuesOnlyChange={setSeoIssuesOnly}
          showSeoFilter
          pageSize={pageSize}
          onPageSizeChange={setPageSize}
          onResetFilters={resetFilters}
          showResetFilters={hasActiveFilters}
        >
          <select
            value={typeFilter}
            onChange={(e) => setTypeFilter(e.target.value as TypeFilter)}
            className="form-input w-full sm:min-w-[140px]"
            aria-label="Filter typu súboru"
          >
            <option value="all">Všetky súbory</option>
            <option value="image">Len obrázky</option>
          </select>
        </AdminListToolbar>
      </div>

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel="vybraných súborov"
        onClear={bulkSelection.clear}
        actions={[
          {
            id: 'delete',
            label: 'Zmazať vybrané',
            variant: 'danger',
            onClick: () => void handleBulkDelete(),
          },
        ]}
      />

      {loading ? (
        <AdminListSkeleton rows={8} />
      ) : listView.total === 0 ? (
        <div className="card">
          <div className="card-body text-center py-12 text-gray-500 dark:text-gray-400">
            V priečinku {folderLabel(currentFolder)} nie sú žiadne súbory.
          </div>
        </div>
      ) : viewMode === 'preview' ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          {pagedItems.map((file) => (
            <MediaCard
              key={file.id}
              file={file}
              selected={bulkSelection.isSelected(file.path)}
              onToggleSelect={() => bulkSelection.toggle(file.path)}
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
      ) : (
        <MediaListTable
          files={pagedItems}
          showThumbnail={viewMode === 'list-preview'}
          selectedPaths={bulkSelection.selectedIds}
          allSelected={bulkSelection.allSelected}
          onToggleSelect={bulkSelection.toggle}
          onToggleSelectAll={bulkSelection.toggleAll}
          onCopyUrl={handleCopyUrl}
          onPreview={openPreview}
          onDelete={handleDelete}
          onStartEdit={startEditMeta}
          sortField={sortField}
          sortDirection={sortDirection}
          onSort={handleSort}
        />
      )}

      {listView.total > 0 && (
        <AdminListPagination
          page={listView.page}
          totalPages={listView.totalPages}
          total={listView.total}
          pageSize={pageSize}
          loading={loading}
          onPageChange={setPage}
          itemLabel="súborov"
        />
      )}

      <MediaMetadataModal
        open={editingFile !== null}
        file={editingFile}
        title={editTitle}
        altText={editAlt}
        saving={savingMeta}
        onTitleChange={setEditTitle}
        onAltChange={setEditAlt}
        onSave={() => {
          if (editingFile) {
            void saveEditMeta(editingFile.path);
          }
        }}
        onClose={cancelEditMeta}
      />

      <MediaPreviewLightbox
        file={previewFile}
        mode={previewMode}
        onClose={closePreview}
        onModeChange={setPreviewMode}
        onPrevious={showPreviousPreview}
        onNext={showNextPreview}
        hasPrevious={previewIndex > 0}
        hasNext={previewIndex >= 0 && previewIndex < navigableItems.length - 1}
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
        <SeoHealthBadge level={evaluateMediaSeo(file)} className="self-start" />

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

interface MediaListTableProps {
  files: MediaFile[];
  showThumbnail: boolean;
  selectedPaths: string[];
  allSelected: boolean;
  onToggleSelect: (path: string) => void;
  onToggleSelectAll: () => void;
  onCopyUrl: (file: MediaFile) => void;
  onPreview: (file: MediaFile) => void;
  onDelete: (file: MediaFile) => void;
  onStartEdit: (file: MediaFile) => void;
  sortField: string;
  sortDirection: 'asc' | 'desc';
  onSort: (field: string) => void;
}

const MediaListTable: React.FC<MediaListTableProps> = ({
  files,
  showThumbnail,
  selectedPaths,
  allSelected,
  onToggleSelect,
  onToggleSelectAll,
  onCopyUrl,
  onPreview,
  onDelete,
  onStartEdit,
  sortField,
  sortDirection,
  onSort,
}) => (
  <div className="card w-full">
    <div className="card-body p-0 table-container w-full">
      <table className="table w-full min-w-0">
        <thead>
          <tr>
            <th className="w-10">
              <input
                type="checkbox"
                checked={allSelected && files.length > 0}
                onChange={onToggleSelectAll}
                aria-label="Select all visible files"
              />
            </th>
            {showThumbnail && <th className="w-24 hide-mobile">Preview</th>}
            <SortableTableHeader
              label="Name"
              field="fileName"
              activeField={sortField}
              direction={sortDirection}
              onSort={onSort}
            />
            <SortableTableHeader
              label="Type"
              field="mimeType"
              activeField={sortField}
              direction={sortDirection}
              onSort={onSort}
              thClassName="hide-mobile"
            />
            <SortableTableHeader
              label="Size"
              field="size"
              activeField={sortField}
              direction={sortDirection}
              onSort={onSort}
            />
            <th>SEO</th>
            <th className="w-[120px]">Actions</th>
          </tr>
        </thead>
        <tbody>
          {files.map((file) => {
            const thumb = resolveAdminMediaPreviewUrl(file.path);
            const fallback = resolvePublicMediaUrl(file.url);
            return (
              <tr key={file.id}>
                <td>
                  <input
                    type="checkbox"
                    checked={selectedPaths.includes(file.path)}
                    onChange={() => onToggleSelect(file.path)}
                    aria-label={`Select ${file.fileName}`}
                  />
                </td>
                {showThumbnail && (
                  <td>
                    {isImageMedia(file) ? (
                      <button type="button" onClick={() => onPreview(file)} className="block w-16 h-12 rounded overflow-hidden bg-gray-100">
                        <img
                          src={thumb}
                          alt=""
                          className="w-full h-full object-cover"
                          onError={(e) => {
                            e.currentTarget.src = fallback;
                          }}
                        />
                      </button>
                    ) : (
                      <FileText className="w-8 h-8 text-gray-400" />
                    )}
                  </td>
                )}
                <td className="!whitespace-normal max-w-[240px] sm:max-w-xs">
                  <p className="font-medium truncate" title={file.title || file.fileName}>
                    {file.title || file.fileName}
                  </p>
                  <p className="text-xs text-gray-500 dark:text-gray-400 line-clamp-2" title={file.altText || undefined}>
                    {file.altText || '—'}
                  </p>
                </td>
                <td className="text-sm">{file.mimeType}</td>
                <td className="text-sm">{formatMediaSize(file.sizeBytes)}</td>
                <td>
                  <SeoHealthBadge level={evaluateMediaSeo(file)} />
                </td>
                <td>
                  <div className="flex flex-wrap gap-1">
                    <button
                      type="button"
                      className="btn btn-secondary text-xs px-2 py-1"
                      title="Edit metadata"
                      onClick={() => onStartEdit(file)}
                    >
                      <Pencil className="w-3 h-3" />
                    </button>
                    <button type="button" className="btn btn-secondary text-xs px-2 py-1" onClick={() => onCopyUrl(file)}>
                      <Copy className="w-3 h-3" />
                    </button>
                    <button type="button" className="btn btn-danger text-xs px-2 py-1" onClick={() => onDelete(file)}>
                      <Trash2 className="w-3 h-3" />
                    </button>
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  </div>
);
