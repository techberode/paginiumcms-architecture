import { describe, expect, it } from 'vitest';
import type { WidgetTypeDefinition } from '../api/widgets';
import { buildWidgetMarkup, escapeWidgetAttr, widgetTypeIcon, widgetTypeLabel } from './widgetMarkup';

const kpi: WidgetTypeDefinition = {
  id: 'kpi',
  selfClosing: true,
  fields: [
    { key: 'title', kind: 'string' },
    { key: 'value', kind: 'string' },
  ],
  defaults: { title: 'Visitors', value: '12k' },
};

describe('widgetMarkup', () => {
  it('builds a self-closing widget ticket', () => {
    expect(buildWidgetMarkup(kpi, { title: 'Visitors', value: '12k' })).toBe(
      '[widget type="kpi" title="Visitors" value="12k" /]'
    );
  });

  it('strips quotes from attributes', () => {
    expect(escapeWidgetAttr('Say "hi"\nnow')).toBe('Say  hi  now');
  });

  it('wraps kpi-row with sample inner widgets', () => {
    const row: WidgetTypeDefinition = { id: 'kpi-row', selfClosing: false, fields: [], defaults: {} };
    const markup = buildWidgetMarkup(row, {});
    expect(markup).toContain('[widget type="kpi-row"]');
    expect(markup).toContain('type="kpi"');
    expect(markup).toContain('[/widget]');
  });

  it('uses custom labels for operator widgets', () => {
    expect(
      widgetTypeLabel({ id: 'promo', selfClosing: true, fields: [], defaults: {}, source: 'custom', label: 'Promo' }, () => 'x')
    ).toBe('Promo');
  });

  it('picks a catalog icon for built-in and custom types', () => {
    expect(widgetTypeIcon('kpi').displayName || widgetTypeIcon('kpi').name).toBeTruthy();
    expect(widgetTypeIcon('promo', 'custom')).not.toBe(widgetTypeIcon('kpi'));
  });
});
