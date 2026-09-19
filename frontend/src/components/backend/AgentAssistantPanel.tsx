import React, { useState } from 'react';
import { agentApi, type AgentProposal, type AgentRun } from '../../api/agent';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';

interface Props {
  type: string;
  slug: string;
  locale: string;
  sourceRevision: string;
  canEdit: boolean;
  onApplied: () => void;
}

export const AgentAssistantPanel: React.FC<Props> = ({
  type,
  slug,
  locale,
  sourceRevision,
  canEdit,
  onApplied,
}) => {
  const { t } = useI18n();
  const toast = useToast();
  const [busy, setBusy] = useState(false);
  const [run, setRun] = useState<AgentRun | null>(null);

  if (!canEdit || slug === '' || slug === 'new') {
    return null;
  }

  const proposal: AgentProposal | null = run?.proposal ?? null;

  const mapError = (message: string): string => {
    if (message.includes('disabled') || message.includes('DISABLED')) {
      return t('editor.agent.disabled');
    }
    if (message.includes('revision') || message.includes('CONFLICT')) {
      return t('editor.agent.conflict');
    }
    if (message.includes('budget') || message.includes('BUDGET')) {
      return t('editor.agent.budget');
    }
    return message || t('editor.agent.failed');
  };

  const suggestSeo = async () => {
    setBusy(true);
    try {
      const queued = await agentApi.enqueue({
        resourceType: type,
        resourceId: slug,
        locale,
        sourceRevision,
        prompt: 'Suggest SEO meta for this article',
        tools: ['content.read', 'seo.suggest_meta'],
      });
      if (!queued.success || !queued.data) {
        toast.error(mapError(queued.error || queued.message || ''));
        return;
      }
      const executed = await agentApi.execute(queued.data.id);
      if (!executed.success || !executed.data) {
        toast.error(mapError(executed.error || executed.message || ''));
        setRun(queued.data);
        return;
      }
      setRun(executed.data);
      if (!executed.data.proposal) {
        toast.error(t('editor.agent.noProposal'));
      }
    } finally {
      setBusy(false);
    }
  };

  const applySelected = async () => {
    if (!proposal) {
      return;
    }
    setBusy(true);
    try {
      const res = await agentApi.apply(proposal.id);
      if (!res.success) {
        toast.error(mapError(res.error || res.message || ''));
        return;
      }
      toast.success(t('editor.agent.applied'));
      setRun(null);
      onApplied();
    } finally {
      setBusy(false);
    }
  };

  const discard = async () => {
    if (!proposal) {
      return;
    }
    setBusy(true);
    try {
      await agentApi.discard(proposal.id);
      setRun(null);
    } finally {
      setBusy(false);
    }
  };

  const fields = proposal?.payload?.fields ?? {};

  return (
    <div className="rounded-md border border-admin-border bg-admin-canvas p-3 space-y-2" data-testid="agent-assistant-panel">
      <div className="flex flex-wrap items-center gap-2">
        <p className="text-sm font-semibold text-admin-text">{t('editor.agent.title')}</p>
        <button
          type="button"
          disabled={busy}
          onClick={() => void suggestSeo()}
          className="rounded-md bg-admin-sidebar-active px-3 py-1.5 text-sm font-semibold text-admin-sidebar-active-text disabled:opacity-60"
        >
          {busy ? t('editor.agent.running') : t('editor.agent.suggestSeo')}
        </button>
      </div>
      <p className="text-xs text-admin-muted">{t('editor.agent.hint')}</p>
      {run ? (
        <p className="text-xs text-admin-muted">
          {t('editor.agent.runStatus', { status: run.status, provider: run.provider || 'none' })}
        </p>
      ) : null}
      {proposal ? (
        <div className="space-y-2 text-sm">
          <p className="font-medium text-admin-text">{t('editor.agent.proposal')}</p>
          {fields.seoTitle ? (
            <p>
              <span className="text-admin-muted">{t('editor.agent.seoTitle')}: </span>
              {fields.seoTitle}
            </p>
          ) : null}
          {fields.seoDescription ? (
            <p>
              <span className="text-admin-muted">{t('editor.agent.seoDescription')}: </span>
              {fields.seoDescription}
            </p>
          ) : null}
          {fields.keywords ? (
            <p>
              <span className="text-admin-muted">{t('editor.agent.keywords')}: </span>
              {fields.keywords}
            </p>
          ) : null}
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              disabled={busy}
              onClick={() => void applySelected()}
              className="rounded-md border border-admin-border px-3 py-1.5 text-sm font-semibold"
            >
              {t('editor.agent.apply')}
            </button>
            <button
              type="button"
              disabled={busy}
              onClick={() => void discard()}
              className="rounded-md px-3 py-1.5 text-sm text-admin-muted"
            >
              {t('editor.agent.discard')}
            </button>
          </div>
        </div>
      ) : null}
    </div>
  );
};
