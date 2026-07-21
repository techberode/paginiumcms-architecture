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
import { useI18n } from '../../context/I18nContext';

type TypeFilter = 'all' | 'image';

export const MediaManager: React.FC = () => {
  const toast = useToast();
  const { t } = useI18n();
  const folderLabel = (folder: string): string =>
    folder === '' ? t('media.folder.all') : folder;
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
      toast.error(t('media.toast.loadFailed'));
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
        toast.error(t('media.toast.uploadFailed', { name: file.name, error: result.error ?? '' }));
      }
    }

    if (successCount > 0) {
      toast.success(
        successCount === 1
          ? t('media.toast.uploadOne')
          : t('media.toast.uploadMany', { count: String(successCount) })
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
      toast.success(t('media.toast.stockImported', { label }));
      await loadMedia();
    } else {
      toast.error(result.error ?? t('media.toast.stockFailed'));
    }
    setStockImporting(false);
  };

  const handleCreateFolder = async () => {
    const base = currentFolder === '' ? '' : `${currentFolder}/`;
    const name = window.prompt(t('media.folderPrompt'));
    if (!name) {
      return;
    }

    const folder = `${base}${name.trim()}`.replace(/^\/+/, '');
    const ok = await createMediaFolder(folder);
    if (ok) {
      toast.success(t('media.toast.folderCreated'));
      setCurrentFolder(folder);
      await loadMedia();
    } else {
      toast.error(t('media.toast.folderFailed'));
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
      toast.success(t('media.toast.urlCopied'));
    } catch {
      toast.error(t('media.toast.urlCopyFailed'));
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
      { value: 'fileName', label: t('media.table.name'), getValue: (item) => item.fileName },
      { value: 'title', label: t('media.table.title'), getValue: (item) => item.title || item.altText || '' },
      { value: 'mimeType', label: t('media.table.type'), getValue: (item) => item.mimeType },
      { value: 'size', label: t('media.table.size'), getValue: (item) => item.sizeBytes },
      { value: 'uploadedAt', label: t('media.table.date'), getValue: (item) => item.uploadedAt },
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
      { value: 'fileName', label: t('media.table.name'), getValue: (item) => item.fileName },
      { value: 'title', label: t('media.table.title'), getValue: (item) => item.title || item.altText || '' },
      { value: 'mimeType', label: t('media.table.type'), getValue: (item) => item.mimeType },
      { value: 'size', label: t('media.table.size'), getValue: (item) => item.sizeBytes },
      { value: 'uploadedAt', label: t('media.table.date'), getValue: (item) => item.uploadedAt },
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
      !confirm(t('media.confirm.deleteBulk', { count: String(bulkSelection.count) }))
    ) {
      return;
    }

    const deleted = await bulkDeleteMedia(bulkSelection.selectedIds);
    if (deleted > 0) {
      toast.success(t('media.toast.bulkDeleted', { count: String(deleted) }));
      bulkSelection.clear();
      await loadMedia();
    } else {
      toast.error(t('media.toast.bulkDeleteFailed'));
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
    if (!confirm(t('media.confirm.deleteOne', { name: file.fileName }))) {
      return;
    }

    const ok = await deleteMedia(file.path);
    if (ok) {
      toast.success(t('media.toast.deleted'));
      await loadMedia();
    } else {
      toast.error(t('media.toast.deleteFailed'));
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
        toast.success(t('media.toast.metaUpdated'));
        cancelEditMeta();
        await loadMedia();
      } else {
        toast.error(t('media.toast.metaFailed'));
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
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('media.page.title')}</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {t('media.page.subtitle')}
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <button type="button" className="btn btn-secondary" onClick={() => void handleCreateFolder()}>
            <FolderPlus className="w-4 h-4 inline mr-2" />
            {t('media.actions.newFolder')}
          </button>
          {stockTopics.length > 0 && (
            <>
              <select
                value={stockTopic}
                onChange={(e) => setStockTopic(e.target.value)}
                className="form-input w-auto"
                aria-label={t('media.stock.topicLabel')}
                title={t('media.stock.topicTitle')}
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
                title={t('media.stock.importTitle')}
              >
                {stockImporting ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin inline mr-2" />
                    {t('media.actions.generating')}
                  </>
                ) : (
                  <>
                    <Zap className="w-4 h-4 inline mr-2" />
                    {t('media.actions.generateStock')}
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
                {t('media.actions.uploading')}
              </>
            ) : (
              <>
                <Upload className="w-4 h-4 inline mr-2" />
                {t('media.actions.upload')}
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
          {t('media.folder.all')}
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
            {t('media.dropzone.title')}
          </p>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {t('media.dropzone.uploadTo', { folder: folderLabel(currentFolder) })}
          </p>
        </div>
      </div>

      <div className="w-full">
        <AdminListToolbar
          search={search}
          onSearchChange={setSearch}
          searchPlaceholder={t('media.search.placeholder')}
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
            aria-label={t('media.filter.typeLabel')}
          >
            <option value="all">{t('media.filter.all')}</option>
            <option value="image">{t('media.filter.images')}</option>
          </select>
        </AdminListToolbar>
      </div>

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel={t('media.bulk.itemLabel')}
        onClear={bulkSelection.clear}
        actions={[
          {
            id: 'delete',
            label: t('media.bulk.delete'),
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
  const { t } = useI18n();
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
              placeholder={t('media.meta.titlePlaceholder')}
              className="form-input text-sm"
              aria-label={t('media.meta.titlePlaceholder')}
            />
            <input
              type="text"
              value={editAlt}
              onChange={(e) => onEditAltChange(e.target.value)}
              placeholder={t('media.meta.altPlaceholder')}
              className="form-input text-sm"
              aria-label={t('media.meta.altPlaceholder')}
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
          <p className="text-xs text-gray-500 dark:text-gray-400 truncate" title={file.altText || t('media.meta.noAlt')}>
            {file.altText ? t('media.meta.altPrefix', { text: file.altText }) : t('media.meta.noAlt')}
          </p>
        )}

        <div className="flex gap-2 mt-auto pt-2">
          {!editing && isImage && (
            <>
              <button
                type="button"
                className="btn btn-secondary text-xs px-2 py-1"
                title={t('media.actions.previewFit')}
                onClick={onPreview}
              >
                <Expand className="w-3 h-3" />
              </button>
              <button
                type="button"
                className="btn btn-secondary text-xs px-2 py-1"
                title={t('media.actions.previewNative')}
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
              title={t('media.actions.editMeta')}
              onClick={onStartEdit}
            >
              <Pencil className="w-3 h-3" />
            </button>
          )}
          <button
            type="button"
            className="btn btn-secondary text-xs px-2 py-1"
            title={t('media.actions.copyUrl')}
            onClick={onCopyUrl}
          >
            <Copy className="w-3 h-3" />
          </button>
          <button
            type="button"
            className="btn btn-danger text-xs px-2 py-1 ml-auto"
            title={t('media.actions.delete')}
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
}) => {
  const { t } = useI18n();

  return (
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
                aria-label={t('media.table.selectAll')}
              />
            </th>
            {showThumbnail && <th className="w-24 hide-mobile">{t('media.table.preview')}</th>}
            <SortableTableHeader
              label={t('media.table.name')}
              field="fileName"
              activeField={sortField}
              direction={sortDirection}
              onSort={onSort}
            />
            <SortableTableHeader
              label={t('media.table.type')}
              field="mimeType"
              activeField={sortField}
              direction={sortDirection}
              onSort={onSort}
              thClassName="hide-mobile"
            />
            <SortableTableHeader
              label={t('media.table.size')}
              field="size"
              activeField={sortField}
              direction={sortDirection}
              onSort={onSort}
            />
            <th>{t('media.table.seo')}</th>
            <th className="w-[120px]">{t('media.table.actions')}</th>
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
                      title={t('media.actions.editMeta')}
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
};
