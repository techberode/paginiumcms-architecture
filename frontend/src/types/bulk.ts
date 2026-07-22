// frontend/src/types/bulk.ts

export interface BulkBatchItemResult {
  id: string;
  ok: boolean;
  error?: string;
}

export interface BulkBatchResult {
  processed: number;
  succeeded: number;
  failed: number;
  results: BulkBatchItemResult[];
}

type BulkTranslate = (key: string, params?: Record<string, string>) => string;

export function summarizeBulkResult(result: BulkBatchResult, t: BulkTranslate): string {
  if (result.failed === 0) {
    return t('list.bulk.allSucceeded', { count: String(result.succeeded) });
  }
  return t('list.bulk.partialResult', {
    succeeded: String(result.succeeded),
    failed: String(result.failed),
  });
}
