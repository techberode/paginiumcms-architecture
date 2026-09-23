import { describe, it, expect } from 'vitest';
import { translate } from '../i18n';
import { translateJobRunMessage } from './jobRunMessage';

describe('translateJobRunMessage', () => {
  it('translates published message with slug list', () => {
    const text = translateJobRunMessage(
      { message: 'Published 1 scheduled item(s): article/demo-post', reason: null },
      (key, params) => translate('sk', key, params)
    );
    expect(text).toContain('demo-post');
    expect(text).toContain('1');
  });
});
