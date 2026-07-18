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

export function summarizeBulkResult(result: BulkBatchResult): string {
  if (result.failed === 0) {
    return `${result.succeeded} item(s) updated`;
  }
  return `${result.succeeded} succeeded, ${result.failed} failed`;
}
