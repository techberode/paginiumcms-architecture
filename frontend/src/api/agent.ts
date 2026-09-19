import apiClient from './client';

export interface AgentQuota {
  day: string;
  used: number;
  limit: number;
  remaining: number | null;
}

export interface AgentStatus {
  enabled: boolean;
  provider: string;
  model: string;
  allowedTools: string[];
  quota: AgentQuota;
}

export interface AgentConnection {
  ok: boolean;
  provider: string;
  error?: string;
}

export interface AgentProposal {
  id: string;
  kind: string;
  sourceRevision?: string;
  payload?: {
    kind?: string;
    fields?: Record<string, string>;
    path?: string;
    altText?: string;
    summary?: string;
    translationJobId?: string;
  };
  tokens?: number;
  steps?: number;
  applied?: boolean;
}

export interface AgentRun {
  id: string;
  status: string;
  resourceType: string;
  resourceId: string;
  allowedTools: string[];
  provider: string;
  model: string;
  tokens: number;
  steps: number;
  error: string | null;
  proposal: AgentProposal | null;
}

export interface AgentApplyResult {
  proposalId: string;
  kind: string;
  revision?: string;
  published: boolean;
}

export const agentApi = {
  status: async () => apiClient.get<AgentStatus>('/api/admin/agent/status'),
  testConnection: async () => apiClient.post<AgentConnection>('/api/admin/agent/connection'),
  enqueue: async (payload: {
    resourceType: string;
    resourceId: string;
    locale?: string;
    sourceRevision?: string;
    prompt?: string;
    tools?: string[];
  }) => apiClient.post<AgentRun>('/api/admin/agent/runs', payload),
  show: async (runId: string) => apiClient.get<AgentRun>(`/api/admin/agent/runs/${runId}`),
  execute: async (runId: string) => apiClient.post<AgentRun>(`/api/admin/agent/runs/${runId}/execute`),
  cancel: async (runId: string) => apiClient.post<{ cancelled: boolean }>(`/api/admin/agent/runs/${runId}/cancel`),
  apply: async (proposalId: string) =>
    apiClient.post<AgentApplyResult>(`/api/admin/agent/proposals/${proposalId}/apply`),
  discard: async (proposalId: string) =>
    apiClient.delete<{ discarded: boolean }>(`/api/admin/agent/proposals/${proposalId}`),
};
