import React from 'react';
import { CheckCircle2, AlertTriangle, Loader2, ShieldCheck } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import type { SystemUpdateCredentialsVerify } from '../../api/systemUpdate';
import { resolveCredentialStatus } from '../../utils/systemUpdateCredentials';

type Props = {
  report: SystemUpdateCredentialsVerify | null;
  loading: boolean;
  onVerify: () => void;
};

const statusTone = (
  status: string
): 'ok' | 'warn' | 'neutral' => {
  if (status === 'ok') return 'ok';
  if (status === 'not_required') return 'neutral';
  return 'warn';
};

const Row: React.FC<{
  label: string;
  status: string;
  detail: string | null | undefined;
  statusKeyPrefix: string;
}> = ({ label, status, detail, statusKeyPrefix }) => {
  const { t } = useI18n();
  const tone = statusTone(status);
  const statusLabel = t(`${statusKeyPrefix}.${status}` as 'platform.systemUpdate.credentials.tokenStatus.ok');

  return (
    <div
      className={`rounded-lg border px-3 py-2 text-sm ${
        tone === 'ok'
          ? 'border-emerald-200 bg-emerald-50/80'
          : tone === 'warn'
            ? 'border-amber-200 bg-amber-50/80'
            : 'border-slate-200 bg-slate-50/80'
      }`}
    >
      <div className="flex flex-wrap items-center gap-2">
        {tone === 'ok' ? (
          <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" aria-hidden />
        ) : tone === 'warn' ? (
          <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0" aria-hidden />
        ) : null}
        <span className="font-medium text-slate-800">{label}</span>
        <span className="text-xs font-mono uppercase tracking-wide text-slate-600">{statusLabel}</span>
      </div>
      {detail ? <p className="mt-1 text-xs text-slate-700 break-words">{detail}</p> : null}
    </div>
  );
};

export const SystemUpdateCredentialsPanel: React.FC<Props> = ({ report, loading, onVerify }) => {
  const { t } = useI18n();

  const overallOk = report?.overall_ok === true;

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 className="font-semibold text-slate-800 flex items-center gap-2">
            <ShieldCheck className="w-5 h-5 text-indigo-600" />
            {t('platform.systemUpdate.credentials.title')}
          </h2>
          <p className="text-sm text-slate-600 mt-1">{t('platform.systemUpdate.credentials.subtitle')}</p>
        </div>
        <button
          type="button"
          disabled={loading}
          onClick={onVerify}
          className="btn-secondary inline-flex items-center gap-2 shrink-0"
        >
          {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <ShieldCheck className="w-4 h-4" />}
          {loading
            ? t('platform.systemUpdate.credentials.verifying')
            : t('platform.systemUpdate.credentials.verifyButton')}
        </button>
      </div>

      {report ? (
        <>
          <div
            className={`rounded-lg border px-4 py-3 text-sm font-medium ${
              overallOk
                ? 'border-emerald-300 bg-emerald-50 text-emerald-900'
                : 'border-amber-300 bg-amber-50 text-amber-900'
            }`}
            role="status"
          >
            {overallOk
              ? t('platform.systemUpdate.credentials.overallOk')
              : t('platform.systemUpdate.credentials.overallFail')}
          </div>
          {report.checked_at ? (
            <p className="text-xs text-slate-500">
              {t('platform.systemUpdate.credentials.checkedAt', {
                time: new Date(report.checked_at).toLocaleString(),
              })}
            </p>
          ) : null}
          <div className="space-y-2">
            {report.deploy_ssh_key ? (
              <Row
                label={t('platform.systemUpdate.credentials.deployKeyLabel')}
                status={resolveCredentialStatus(
                  report.deploy_ssh_key.status,
                  report.deploy_ssh_key.configured
                )}
                detail={report.deploy_ssh_key.detail}
                statusKeyPrefix="platform.systemUpdate.credentials.tokenStatus"
              />
            ) : null}
            {report.ssh ? (
              <Row
                label={t('platform.systemUpdate.credentials.sshLabel')}
                status={resolveCredentialStatus(report.ssh.status)}
                detail={report.ssh.detail}
                statusKeyPrefix="platform.systemUpdate.credentials.tokenStatus"
              />
            ) : null}
            <Row
              label={t('platform.systemUpdate.credentials.tokenLabel')}
              status={resolveCredentialStatus(report.github.token.status)}
              detail={report.github.token.detail}
              statusKeyPrefix="platform.systemUpdate.credentials.tokenStatus"
            />
            <Row
              label={t('platform.systemUpdate.credentials.gitFetchLabel')}
              status={resolveCredentialStatus(report.git_fetch.status)}
              detail={report.git_fetch.detail}
              statusKeyPrefix="platform.systemUpdate.credentials.gitStatus"
            />
            <Row
              label={t('platform.systemUpdate.credentials.webhookSecretLabel')}
              status={resolveCredentialStatus(report.webhook.secret.status)}
              detail={report.webhook.secret.detail}
              statusKeyPrefix="platform.systemUpdate.credentials.webhookStatus"
            />
          </div>
          <p className="text-xs text-slate-500">{t('platform.systemUpdate.credentials.pathHint')}</p>
        </>
      ) : (
        <p className="text-sm text-slate-500">{t('platform.systemUpdate.credentials.notRunYet')}</p>
      )}
    </div>
  );
};

export function firstCredentialsFailureDetail(
  report: SystemUpdateCredentialsVerify
): string | undefined {
  if (report.deploy_ssh_key) {
    const keyStatus = resolveCredentialStatus(
      report.deploy_ssh_key.status,
      report.deploy_ssh_key.configured
    );
    if (keyStatus !== 'ok' && report.deploy_ssh_key.detail) {
      return report.deploy_ssh_key.detail;
    }
  }
  if (report.ssh && report.ssh.status !== 'ok' && report.ssh.detail) {
    return report.ssh.detail;
  }
  const token = report.github.token;
  if (token.status !== 'ok' && token.status !== 'not_required' && token.detail) {
    return token.detail;
  }
  if (report.git_fetch.status === 'failed' && report.git_fetch.detail) {
    return report.git_fetch.detail;
  }
  if (
    report.webhook.auto_deploy_enabled &&
    report.webhook.secret.status !== 'ok' &&
    report.webhook.secret.detail
  ) {
    return report.webhook.secret.detail;
  }
  return undefined;
}
