import { describe, expect, it, afterEach } from 'vitest';
import { platformEn } from './en';
import { platformSk } from './sk';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../../index';

describe('platform i18n module', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  it('registers platform catalogs', () => {
    registerModuleMessages('sk', 'platform', platformSk);
    registerModuleMessages('en', 'platform', platformEn);

    expect(translate('sk', 'platform.firewall.title')).toBe('Firewall (WAF)');
    expect(translate('en', 'platform.scheduler.title')).toBe('Scheduler');
    expect(translate('sk', 'platform.commandPalette.types.article')).toBe('Článok');
    expect(translate('en', 'platform.themes.studio.edit')).toBe('Edit');
    expect(translate('en', 'platform.themes.studio.policyOk')).toBe('Policy OK');
    expect(translate('sk', 'platform.themes.studio.policyOk')).toBe('Politika OK');
    expect(translate('en', 'platform.themes.studio.previewBlocked')).toBe(
      'Preview blocked — fix policy markers in Monaco first.',
    );
    expect(translate('en', 'platform.themes.studio.normalize')).toBe('Normalize');
    expect(translate('en', 'platform.themes.studio.saved')).toBe('Theme package saved');
    expect(translate('sk', 'platform.themes.studio.slots')).toBe('Sloty');
    expect(translate('sk', 'platform.widgets.title')).toBe('Widgety');
    expect(translate('en', 'platform.widgets.copy')).toBe('Copy markdown');
    expect(translate('sk', 'platform.widgets.custom.title')).toBe('Vlastné widgety');
    expect(translate('sk', 'platform.teams.title')).toBe('Tímy');
    expect(translate('en', 'platform.teams.types.support')).toBe('Support');
    expect(translate('sk', 'platform.teams.types.external')).toBe('Externý tím');
    expect(translate('sk', 'platform.teams.externalBadge')).toBe('Externý');
    expect(translate('en', 'platform.teamChat.title')).toBe('Team chat');
    expect(translate('sk', 'platform.registrationOptions.title')).toBe('Typy registrácie');
    expect(translate('sk', 'platform.registrationInvites.title')).toBe('Jednorazová registrácia');
    expect(translate('sk', 'platform.teams.namePlaceholder')).toBe('napr. Marketing');
    expect(translate('sk', 'platform.events.title')).toBe('Udalosti');
    expect(translate('en', 'platform.events.status.published')).toBe('Published');
    expect(translate('sk', 'platform.timeTracker.title')).toBe('Časovač');
    expect(translate('en', 'platform.timeTracker.start')).toBe('Start');
    expect(translate('sk', 'platform.account.title')).toBe('Účet');
    expect(translate('en', 'platform.account.tabs.profile')).toBe('Profile');
    expect(translate('sk', 'platform.account.tabs.public')).toBe('Verejná karta');
    expect(translate('en', 'platform.account.sections.details')).toBe('Profile details');
    expect(translate('sk', 'platform.account.desk.queueTitle')).toBe('Tvoja fronta');
    expect(translate('en', 'platform.account.desk.open')).toBe('Open on the page');
    expect(translate('sk', 'platform.account.desk.popOut')).toBe('Vždy navrchu');
    expect(translate('en', 'platform.account.desk.enabled')).toBe('Show the desk chat bubble');
    expect(translate('sk', 'platform.account.desk.kind.comment')).toBe('Komentár');
    expect(translate('en', 'platform.account.desk.kind.message')).toBe('Message');
    expect(translate('sk', 'platform.account.desk.beacon')).toBe('Stôl');
    expect(translate('sk', 'platform.account.desk.anchors.right')).toBe('Vpravo');
    expect(translate('en', 'platform.account.chat.deskMailEnabled')).toBe(
      'Send desk replies from my site mailbox',
    );
    expect(translate('sk', 'platform.teams.replyMailEnabled')).toBe(
      'Odosielať odpovede stola z tímovej schránky',
    );
    expect(translate('sk', 'platform.preview.openFull')).toBe('Náhľad stránky');
    expect(translate('sk', 'platform.systemUpdate.credentials.tokenStatus.unknown')).toBe(
      'Neznámy',
    );
    expect(translate('en', 'platform.systemUpdate.blockers.github_deploy_ssh_key_unreadable')).toContain(
      'ensure-php-deploy-key-mount.sh',
    );
  });
});
