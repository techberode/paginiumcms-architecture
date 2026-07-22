import { describe, expect, it } from 'vitest';
import { translate } from '../../index';
import { registerModuleMessages } from '../../index';
import { auditEn } from './en';
import { auditSk } from './sk';

describe('audit i18n module', () => {
  registerModuleMessages('sk', 'audit', auditSk);
  registerModuleMessages('en', 'audit', auditEn);

  it('has SK system labels', () => {
    expect(translate('sk', 'audit.system')).toBe('Systém');
    expect(translate('sk', 'audit.system_event')).toBe('Systémová udalosť');
  });

  it('has EN system labels', () => {
    expect(translate('en', 'audit.system')).toBe('System');
    expect(translate('en', 'audit.system_event')).toBe('System event');
  });
});
