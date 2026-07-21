export interface AuditEventLike {
  display_message?: string;
  message?: string;
  timestamp?: string | number;
  context?: {
    summary?: string;
    action?: string;
    target?: string;
    category?: string;
    timestamp?: string;
    user?: { name?: string; email?: string };
    metadata?: {
      message?: string;
      content_type?: string;
      content_title?: string;
      content_slug?: string;
      content_status?: string;
      version?: number | string;
      change_summary?: string;
    };
  };
  log?: {
    message?: string;
    context?: AuditEventLike['context'];
  };
}

const ACTION_VERBS: Record<string, string> = {
  create: 'vytvoril',
  update: 'upravil',
  delete: 'zmazal',
  restore: 'obnovil',
  status: 'zmenil stav',
  read: 'zobrazil',
};

const CONTENT_TYPES: Record<string, string> = {
  page: 'stránku',
  article: 'článok',
  pages: 'stránku',
  articles: 'článok',
};

function actorName(context?: AuditEventLike['context']): string {
  const name = context?.user?.name?.trim();
  if (name) return name;
  const email = context?.user?.email?.trim();
  if (email) return email;
  return 'Systém';
}

function quote(value: string): string {
  const trimmed = value.trim();
  return trimmed === '' ? '„(prázdne)“' : `„${trimmed}“`;
}

function translateChangeSummary(summary: string): string {
  return summary
    .replace(/ added/g, ' pridaných')
    .replace(/ removed/g, ' odstránených')
    .replace(/ modified/g, ' upravených')
    .replace('No changes', 'Bez zmien')
    .replace('No significant changes', 'Bez významných zmien');
}

function resolveDetail(metadata: NonNullable<AuditEventLike['context']>['metadata']): string {
  const changeSummary = metadata?.change_summary?.trim();
  if (changeSummary && changeSummary !== 'No changes' && changeSummary !== 'No significant changes') {
    return translateChangeSummary(changeSummary);
  }

  const message = metadata?.message?.trim();
  if (!message) return '';
  if (/^(Create|Update|Delete|Restore|Status)\s+(page|article):\s+/i.test(message)) {
    return '';
  }
  return message;
}

function resolveContentLabel(target: string, metadata?: NonNullable<AuditEventLike['context']>['metadata']): string {
  const title = metadata?.content_title?.trim() ?? '';
  const slug = metadata?.content_slug?.trim() || target;

  if (title !== '') {
    if (slug !== '' && title.localeCompare(slug, undefined, { sensitivity: 'accent' }) !== 0) {
      return `„${title}“ (${slug})`;
    }
    return quote(title);
  }

  return quote(slug);
}

function translateStatus(status: string): string {
  switch (status.toLowerCase()) {
    case 'draft':
      return 'koncept';
    case 'published':
      return 'publikovaný';
    case 'archived':
      return 'archivovaný';
    default:
      return status;
  }
}

function formatContentChange(
  actor: string,
  action: string,
  target: string,
  metadata: NonNullable<AuditEventLike['context']>['metadata']
): string {
  const contentType = (metadata?.content_type ?? 'page').toLowerCase();
  const typeLabel = CONTENT_TYPES[contentType] ?? 'obsah';
  const verb = ACTION_VERBS[action] ?? action;
  const parts = [`${actor} ${verb} ${typeLabel} ${resolveContentLabel(target, metadata)}`];

  if (metadata?.version !== undefined && metadata.version !== '') {
    parts.push(`(verzia ${metadata.version})`);
  }

  if (action === 'status' && metadata?.content_status) {
    parts.push(`→ ${translateStatus(metadata.content_status)}`);
  }

  const detail = resolveDetail(metadata);
  if (detail) {
    parts.push(`· ${detail}`);
  }

  return parts.join(' ');
}

function formatFromContext(context: AuditEventLike['context']): string | null {
  if (!context?.category || !context.action || !context.target) {
    return null;
  }

  const actor = actorName(context);
  const action = context.action.toLowerCase();
  const metadata = context.metadata;

  switch (context.category) {
    case 'content_change':
      return formatContentChange(actor, action, context.target, metadata);
    case 'content_access': {
      const typeLabel = CONTENT_TYPES[(metadata?.content_type ?? 'obsah').toLowerCase()] ?? metadata?.content_type ?? 'obsah';
      const verb = ACTION_VERBS[action] ?? 'pristúpil k';
      return `${actor} ${verb} ${typeLabel} ${resolveContentLabel(context.target, metadata)}`;
    }
    case 'admin_action':
      return `${actor} vykonal administrátorskú akciu „${action}“ na „${context.target}“`;
    case 'security':
      return `Bezpečnostná udalosť „${action}“ na „${context.target}“ (${actor})`;
    default:
      return `${actor} — ${action.toUpperCase()}: ${quote(context.target)}`;
  }
}

export function formatAuditEventMessage(event: AuditEventLike): string {
  if (typeof event.display_message === 'string' && event.display_message.trim() !== '') {
    return event.display_message;
  }

  const fromContext = formatFromContext(event.context ?? event.log?.context);
  if (fromContext) {
    return fromContext;
  }

  const summary = event.context?.summary ?? event.log?.context?.summary;
  if (typeof summary === 'string' && summary.trim() !== '') {
    return summary;
  }

  if (typeof event.message === 'string' && event.message.trim() !== '') {
    return event.message;
  }

  if (typeof event.log?.message === 'string' && event.log.message.trim() !== '') {
    return event.log.message;
  }

  return event.context?.action ?? event.log?.context?.action ?? 'Systémová udalosť';
}

export function formatAuditEventActor(event: AuditEventLike): string {
  return actorName(event.context ?? event.log?.context);
}

export function formatAuditEventTimestamp(event: AuditEventLike): string {
  const raw = event.timestamp ?? event.context?.timestamp ?? event.log?.context?.timestamp;

  if (raw === undefined || raw === null || raw === '') {
    return '';
  }

  const date = typeof raw === 'number' ? new Date(raw * 1000) : new Date(String(raw));
  if (Number.isNaN(date.getTime())) {
    return '';
  }

  return date.toLocaleString('sk-SK');
}
