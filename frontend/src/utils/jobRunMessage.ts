// frontend/src/utils/jobRunMessage.ts
import type { JobRunEntry } from '../api/jobs';

type TranslateFn = (key: string, params?: Record<string, string | number>) => string;

const EXACT_MESSAGE_KEYS: Record<string, string> = {
  'Backup created': 'platform.scheduler.runMessages.backupCreated',
  'Backup not due': 'platform.scheduler.runMessages.backupNotDue',
  'No scheduled content due': 'platform.scheduler.runMessages.noScheduledContent',
  'Scheduled items skipped': 'platform.scheduler.runMessages.scheduledItemsSkipped',
  'No webhook deliveries due': 'platform.scheduler.runMessages.noWebhookDeliveries',
  'Git publish completed': 'platform.scheduler.runMessages.gitPublishCompleted',
  'Git publish failed': 'platform.scheduler.runMessages.gitPublishFailed',
  'Deploy completed': 'platform.scheduler.runMessages.deployCompleted',
  'Deploy disabled on demo instance': 'platform.scheduler.runMessages.deployDemoDisabled',
  'System deploy is disabled in settings': 'platform.scheduler.runMessages.deployDisabled',
  'APP_ROOT not configured': 'platform.scheduler.runMessages.deployMissingAppRoot',
  'Deploy script missing': 'platform.scheduler.runMessages.deployMissingScript',
  'Newsletter sending disabled in settings': 'platform.scheduler.runMessages.newsletterSendDisabled',
  'Email channel not configured': 'platform.scheduler.runMessages.emailNotConfigured',
  'No new articles for weekly digest': 'platform.scheduler.runMessages.noDigestArticles',
  'No weekly digest subscribers': 'platform.scheduler.runMessages.noDigestSubscribers',
};

const REASON_KEYS: Record<string, string> = {
  not_due: 'platform.scheduler.runReasons.notDue',
  no_schedule: 'platform.scheduler.runReasons.noSchedule',
  disabled: 'platform.scheduler.runReasons.disabled',
  nothing_due: 'platform.scheduler.runReasons.nothingDue',
  some_items_skipped: 'platform.scheduler.runReasons.someItemsSkipped',
  demo_disabled: 'platform.scheduler.runReasons.demoDisabled',
  missing_app_root: 'platform.scheduler.runReasons.missingAppRoot',
  missing_script: 'platform.scheduler.runReasons.missingScript',
  script_failed: 'platform.scheduler.runReasons.scriptFailed',
  strategy_or_config: 'platform.scheduler.runReasons.strategyOrConfig',
  nothing_sent: 'platform.scheduler.runReasons.nothingSent',
};

export function translateJobRunMessage(
  run: Pick<JobRunEntry, 'message' | 'reason'>,
  t: TranslateFn
): string {
  const message = (run.message ?? '').trim();
  if (message === '') {
    const reason = run.reason ?? '';
    if (reason !== '' && REASON_KEYS[reason]) {
      return t(REASON_KEYS[reason]);
    }
    return '—';
  }

  const exactKey = EXACT_MESSAGE_KEYS[message];
  if (exactKey) {
    return t(exactKey);
  }

  let published = message.match(/^Published (\d+) scheduled item\(s\)$/);
  if (published) {
    return t('platform.scheduler.runMessages.publishedScheduled', { count: published[1] });
  }

  let report = message.match(/^Report: (sent|skipped) · Log notifications: (\d+)$/);
  if (report) {
    return t('platform.scheduler.runMessages.monitoringPipeline', {
      report: t(`platform.scheduler.runMessages.reportStatus.${report[1]}`),
      count: report[2],
    });
  }

  let webhooks = message.match(/^Processed (\d+) webhook delivery\(ies\): (\d+) ok, (\d+) failed$/);
  if (webhooks) {
    return t('platform.scheduler.runMessages.webhookProcessed', {
      processed: webhooks[1],
      ok: webhooks[2],
      failed: webhooks[3],
    });
  }

  let digest = message.match(/^Weekly digest: sent=(\d+) failed=(\d+) skipped=(\d+)$/);
  if (digest) {
    return t('platform.scheduler.runMessages.weeklyDigestStats', {
      sent: digest[1],
      failed: digest[2],
      skipped: digest[3],
    });
  }

  let deployFail = message.match(/^Deploy script failed \(exit (\d+)\)$/);
  if (deployFail) {
    return t('platform.scheduler.runMessages.deployScriptFailed', { code: deployFail[1] });
  }

  if (message.startsWith('Unknown handler: ')) {
    return t('platform.scheduler.runMessages.unknownHandler', {
      handler: message.slice('Unknown handler: '.length),
    });
  }

  return message;
}
