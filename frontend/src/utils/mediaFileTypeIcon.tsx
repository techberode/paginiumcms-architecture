import React, { useId } from 'react';

export type MediaFileKind =
  | 'pdf'
  | 'spreadsheet'
  | 'presentation'
  | 'document'
  | 'markdown'
  | 'text'
  | 'video'
  | 'image'
  | 'archive'
  | 'file';

export interface MediaFileBadgeStyle {
  kind: MediaFileKind;
  body: string;
  fold: string;
  label: string;
}

function fileExtension(fileName: string): string {
  if (!fileName.includes('.')) {
    return '';
  }
  return fileName.slice(fileName.lastIndexOf('.') + 1).toLowerCase();
}

export function resolveMediaFileKind(file: { fileName: string; mimeType: string }): MediaFileKind {
  const mime = file.mimeType.toLowerCase().trim();
  const ext = fileExtension(file.fileName);

  if (mime.startsWith('video/') || ext === 'mp4' || ext === 'webm') {
    return 'video';
  }
  if (mime.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) {
    return 'image';
  }
  if (mime === 'application/pdf' || ext === 'pdf') {
    return 'pdf';
  }
  if (mime.includes('spreadsheet') || mime.includes('excel') || ['xls', 'xlsx', 'ods', 'csv'].includes(ext)) {
    return 'spreadsheet';
  }
  if (mime.includes('presentation') || mime.includes('powerpoint') || ['ppt', 'pptx', 'odp'].includes(ext)) {
    return 'presentation';
  }
  if (mime.includes('word') || mime.includes('opendocument.text') || ['doc', 'docx', 'odt', 'rtf'].includes(ext)) {
    return 'document';
  }
  if (mime === 'text/markdown' || ext === 'md') {
    return 'markdown';
  }
  if (mime.startsWith('text/') || ext === 'txt') {
    return 'text';
  }
  if (mime.includes('zip') || ['zip', '7z', 'rar'].includes(ext)) {
    return 'archive';
  }

  return 'file';
}

const KIND_STYLES: Record<MediaFileKind, Omit<MediaFileBadgeStyle, 'kind' | 'label'>> = {
  pdf: { body: '#DC2626', fold: '#B91C1C' },
  spreadsheet: { body: '#16A34A', fold: '#15803D' },
  presentation: { body: '#EA580C', fold: '#C2410C' },
  document: { body: '#2563EB', fold: '#1D4ED8' },
  markdown: { body: '#4F46E5', fold: '#4338CA' },
  text: { body: '#64748B', fold: '#475569' },
  video: { body: '#7C3AED', fold: '#6D28D9' },
  image: { body: '#0284C7', fold: '#0369A1' },
  archive: { body: '#D97706', fold: '#B45309' },
  file: { body: '#6B7280', fold: '#4B5563' },
};

export function resolveMediaExtensionLabel(file: { fileName: string; mimeType: string }): string {
  const ext = fileExtension(file.fileName);
  if (ext === 'jpeg') {
    return 'JPG';
  }
  if (ext !== '') {
    return ext.length <= 4 ? ext.toUpperCase() : ext.slice(0, 4).toUpperCase();
  }

  const kind = resolveMediaFileKind(file);
  const byKind: Partial<Record<MediaFileKind, string>> = {
    pdf: 'PDF',
    spreadsheet: 'XLS',
    presentation: 'PPT',
    document: 'DOC',
    markdown: 'MD',
    text: 'TXT',
    video: 'VID',
    image: 'IMG',
    archive: 'ZIP',
  };

  return byKind[kind] ?? 'FILE';
}

export function mediaFileBadgeStyle(file: { fileName: string; mimeType: string }): MediaFileBadgeStyle {
  const kind = resolveMediaFileKind(file);
  const palette = KIND_STYLES[kind];

  return {
    kind,
    ...palette,
    label: resolveMediaExtensionLabel(file),
  };
}

interface MediaFileTypeIconProps {
  file: { fileName: string; mimeType: string };
  className?: string;
  /** Icon width in px (height scales to document aspect ratio). */
  size?: number;
}

/** OS-style colored document tile with extension label (PDF, XLSX, …). */
export const MediaFileTypeIcon: React.FC<MediaFileTypeIconProps> = ({ file, className = '', size = 32 }) => {
  const uid = useId().replace(/:/g, '');
  const { body, fold, label } = mediaFileBadgeStyle(file);
  const width = size;
  const height = Math.round(size * 1.2);

  return (
    <svg
      width={width}
      height={height}
      viewBox="0 0 40 48"
      className={`shrink-0 drop-shadow-sm ${className}`}
      aria-hidden
      role="img"
    >
      <title>{label}</title>
      <path
        d="M6 2h20a2 2 0 0 1 2 2v14h14a2 2 0 0 1 2 2v26a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"
        fill={body}
      />
      <path d="M28 2v14a2 2 0 0 0 2 2h14L28 2z" fill={fold} />
      <rect x="4" y="22" width="32" height="14" rx="1" fill={`url(#${uid}-shade)`} opacity="0.12" />
      <text
        x="20"
        y="33"
        textAnchor="middle"
        fill="#FFFFFF"
        fontSize={label.length > 3 ? 7.5 : 9}
        fontWeight={700}
        fontFamily="system-ui, -apple-system, Segoe UI, sans-serif"
        letterSpacing="0.02em"
      >
        {label}
      </text>
      <defs>
        <linearGradient id={`${uid}-shade`} x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor="#000" />
          <stop offset="100%" stopColor="#000" stopOpacity="0" />
        </linearGradient>
      </defs>
    </svg>
  );
};

/** @deprecated use mediaFileBadgeStyle — kept for tests */
export function mediaFileTypeVisual(file: { fileName: string; mimeType: string }): { kind: string } {
  return { kind: resolveMediaFileKind(file) };
}

export default MediaFileTypeIcon;
