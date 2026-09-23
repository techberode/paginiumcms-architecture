import type { JobRunEntry } from '../api/jobs';

export type PublishedRunItem = { type: string; slug: string };
export type SkippedRunItem = { type: string; slug: string; reason: string };

export function extractPublishedFromRun(run: JobRunEntry): PublishedRunItem[] {
  const data = run.data;
  if (!data || typeof data !== 'object') {
    return [];
  }
  const published = (data as { published?: unknown }).published;
  if (!Array.isArray(published)) {
    return [];
  }
  return published
    .filter((row): row is PublishedRunItem => typeof row === 'object' && row !== null)
    .map((row) => ({
      type: String((row as PublishedRunItem).type ?? ''),
      slug: String((row as PublishedRunItem).slug ?? ''),
    }))
    .filter((row) => row.slug !== '');
}

export function extractSkippedFromRun(run: JobRunEntry): SkippedRunItem[] {
  const data = run.data;
  if (!data || typeof data !== 'object') {
    return [];
  }
  const skipped = (data as { skipped?: unknown }).skipped;
  if (!Array.isArray(skipped)) {
    return [];
  }
  return skipped
    .filter((row): row is SkippedRunItem => typeof row === 'object' && row !== null)
    .map((row) => ({
      type: String((row as SkippedRunItem).type ?? ''),
      slug: String((row as SkippedRunItem).slug ?? ''),
      reason: String((row as SkippedRunItem).reason ?? ''),
    }))
    .filter((row) => row.slug !== '');
}

export function formatPublishedRunLabel(items: PublishedRunItem[]): string {
  return items.map((row) => `${row.type}/${row.slug}`).join(', ');
}
