import type { LogEntry } from '../api/logs';

export function formatApplicationLogMessage(entry: LogEntry): string {
  if (typeof entry.display_message === 'string' && entry.display_message.trim() !== '') {
    return entry.display_message;
  }

  const message = entry.message?.trim();
  if (message) {
    return message;
  }

  return 'Systémová udalosť';
}

export function shouldShowLogContext(entry: LogEntry): boolean {
  const category = entry.category ?? '';
  if (category === 'http_access') {
    return false;
  }

  const context = entry.context;
  if (!context || Object.keys(context).length === 0) {
    return false;
  }

  return category !== 'audit_content_change' && !category.startsWith('audit_');
}
