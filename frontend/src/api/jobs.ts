// frontend/src/api/jobs.ts
// === Job scheduler API (Iteration 29) ===
import apiClient from './client';

export interface JobHandler {
  key: string;
  label: string;
}

export interface ScheduledJob {
  id: string;
  name: string;
  handler: string;
  cron: string;
  enabled: boolean;
  system?: boolean;
  payload?: Record<string, unknown>;
  last_run_at?: string | null;
  next_run?: string | null;
  due_now?: boolean;
}

export interface JobRunEntry {
  id?: string;
  job_id: string;
  success?: boolean;
  message?: string;
  reason?: string | null;
  finished_at?: string;
  duration_ms?: number;
}

export interface JobsOverview {
  enabled: boolean;
  handlers: JobHandler[];
  jobs: ScheduledJob[];
  recent_runs: JobRunEntry[];
  queue: Array<Record<string, unknown>>;
  cron_hint: string;
}

export async function getJobsOverview(): Promise<JobsOverview | null> {
  const response = await apiClient.get<JobsOverview>('/api/admin/jobs');
  return response.success && response.data ? response.data : null;
}

export async function updateJob(id: string, payload: Partial<ScheduledJob>): Promise<ScheduledJob | null> {
  const response = await apiClient.put<ScheduledJob>(`/api/admin/jobs/${encodeURIComponent(id)}`, payload);
  return response.success && response.data ? response.data : null;
}

export async function runJob(
  id: string,
  options?: { force_report?: boolean; async?: boolean }
): Promise<{ result?: JobRunEntry; queued?: boolean } | null> {
  const response = await apiClient.post<{ result?: JobRunEntry; queued?: boolean }>(
    `/api/admin/jobs/${encodeURIComponent(id)}/run`,
    options ?? {}
  );
  return response.success && response.data ? response.data : null;
}

export async function runDueJobs(): Promise<{ executed: number; results: JobRunEntry[] } | null> {
  const response = await apiClient.post<{ executed: number; results: JobRunEntry[] }>('/api/admin/jobs/run-due');
  return response.success && response.data ? response.data : null;
}

export async function processJobQueue(limit = 10): Promise<{ processed: number; results: JobRunEntry[] } | null> {
  const response = await apiClient.post<{ processed: number; results: JobRunEntry[] }>(
    '/api/admin/jobs/queue/process',
    { limit }
  );
  return response.success && response.data ? response.data : null;
}
