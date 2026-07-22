import { describe, expect, it, afterEach } from 'vitest';
import { commentsEn } from '../comments/en';
import { commentsSk } from '../comments/sk';
import { messagesEn } from '../messages/en';
import { messagesSk } from '../messages/sk';
import { backupsEn } from '../backups/en';
import { backupsSk } from '../backups/sk';
import { trashEn } from '../trash/en';
import { trashSk } from '../trash/sk';
import { logsEn } from '../logs/en';
import { logsSk } from '../logs/sk';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../../index';

describe('It.18f i18n modules', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  it('registers comments catalogs', () => {
    registerModuleMessages('sk', 'comments', commentsSk);
    registerModuleMessages('en', 'comments', commentsEn);
    expect(translate('sk', 'comments.page.title')).toBe('Komentáre');
    expect(translate('en', 'comments.filter.pending')).toBe('Pending');
  });

  it('registers messages catalogs', () => {
    registerModuleMessages('sk', 'messages', messagesSk);
    registerModuleMessages('en', 'messages', messagesEn);
    expect(translate('sk', 'messages.priority.urgent')).toBe('Urgentná');
    expect(translate('en', 'messages.page.title')).toBe('Messages');
  });

  it('registers backups catalogs', () => {
    registerModuleMessages('sk', 'backups', backupsSk);
    registerModuleMessages('en', 'backups', backupsEn);
    expect(translate('sk', 'backups.create.button')).toBe('Vytvoriť zálohu');
    expect(translate('en', 'backups.status.completed')).toBe('Completed');
  });

  it('registers trash catalogs', () => {
    registerModuleMessages('sk', 'trash', trashSk);
    registerModuleMessages('en', 'trash', trashEn);
    expect(translate('sk', 'trash.page.title')).toBe('Kôš');
    expect(translate('en', 'trash.actions.empty')).toBe('Empty trash');
  });

  it('registers logs catalogs', () => {
    registerModuleMessages('sk', 'logs', logsSk);
    registerModuleMessages('en', 'logs', logsEn);
    expect(translate('sk', 'logs.severity.error')).toBe('Chyba');
    expect(translate('en', 'logs.actions.purge')).toBe('Purge old logs');
  });
});
