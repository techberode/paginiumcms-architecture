import { afterEach, describe, expect, it } from 'vitest';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../../index';
import type { MessageTree } from '../../types';
import { projectPlannerEn } from './en';
import { projectPlannerSk } from './sk';

function leafKeys(tree: MessageTree, prefix = ''): string[] {
  const keys: string[] = [];
  for (const [key, value] of Object.entries(tree)) {
    const path = prefix === '' ? key : `${prefix}.${key}`;
    if (typeof value === 'string') {
      keys.push(path);
    } else {
      keys.push(...leafKeys(value, path));
    }
  }
  return keys.sort();
}

describe('projectPlanner i18n', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  it('keeps SK and EN catalogs in key parity', () => {
    expect(leafKeys(projectPlannerSk)).toEqual(leafKeys(projectPlannerEn));
  });

  it('resolves nested summary keys in Slovak', () => {
    registerModuleMessages('sk', 'projectPlanner', projectPlannerSk);
    registerModuleMessages('en', 'projectPlanner', projectPlannerEn);

    expect(translate('sk', 'projectPlanner.summary.overall')).toBe('Celkový progres');
    expect(translate('sk', 'projectPlanner.summary.dueSoon')).toBe('Čoskoro');
    expect(translate('sk', 'projectPlanner.sections.summary')).toBe('Progres');
  });
});
