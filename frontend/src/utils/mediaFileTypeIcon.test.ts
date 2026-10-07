import { describe, expect, it } from 'vitest';
import { mediaFileBadgeStyle, resolveMediaExtensionLabel, resolveMediaFileKind } from './mediaFileTypeIcon';

describe('mediaFileTypeIcon', () => {
  it('maps kinds and extension labels like desktop file badges', () => {
    expect(resolveMediaFileKind({ fileName: 'report.pdf', mimeType: 'application/pdf' })).toBe('pdf');
    expect(resolveMediaExtensionLabel({ fileName: 'report.pdf', mimeType: 'application/pdf' })).toBe('PDF');

    expect(resolveMediaFileKind({ fileName: 'data.xlsx', mimeType: 'application/octet-stream' })).toBe('spreadsheet');
    expect(resolveMediaExtensionLabel({ fileName: 'data.xlsx', mimeType: 'application/octet-stream' })).toBe('XLSX');

    expect(mediaFileBadgeStyle({ fileName: 'letter.docx', mimeType: 'application/octet-stream' }).body).toBe('#2563EB');
    expect(resolveMediaExtensionLabel({ fileName: 'photo.jpeg', mimeType: 'image/jpeg' })).toBe('JPG');
  });
});
