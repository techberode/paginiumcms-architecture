import React from 'react';
import type { DeskItem } from '../../api/auth';
import { useI18n } from '../../context/I18nContext';
import { inboxPriorityBadgeClass } from '../../utils/adminInboxPriority';

export const DeskQueueItemMeta: React.FC<{ item: DeskItem }> = ({ item }) => {
  const { t } = useI18n();
  const isComment = item.kind === 'comment';
  const kindLabel = isComment
    ? t('platform.account.desk.kind.comment')
    : t('platform.account.desk.kind.message');
  const kindClass = isComment
    ? 'badge bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300'
    : 'badge bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300';
  const priorityKey = item.priority ? `messages.priority.${item.priority}` : '';
  const priorityLabel = priorityKey !== '' ? t(priorityKey) : '';
  const showPriority = !isComment && priorityLabel !== '' && priorityLabel !== priorityKey;

  return (
    <span className="mt-1 flex flex-wrap items-center gap-1" data-testid={`desk-item-meta-${item.kind}-${item.id}`}>
      <span className={`text-[10px] uppercase font-bold ${kindClass}`}>{kindLabel}</span>
      {showPriority ? (
        <span className={`text-[10px] font-bold ${inboxPriorityBadgeClass(item.priority ?? 'normal')}`}>
          {priorityLabel}
        </span>
      ) : null}
      {isComment ? (
        <span className="text-[10px] uppercase font-bold badge bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
          {t('platform.account.desk.kind.articleReply')}
        </span>
      ) : null}
      {item.mine ? (
        <span className="text-[10px] uppercase font-bold badge bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
          {t('messages.desk.mine')}
        </span>
      ) : null}
      {item.handleStatus === 'in_progress' ? (
        <span className="text-[10px] uppercase font-bold text-indigo-600">{t('messages.desk.inProgress')}</span>
      ) : null}
    </span>
  );
};
