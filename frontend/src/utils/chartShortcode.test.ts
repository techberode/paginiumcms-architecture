import { describe, expect, it } from 'vitest';
import { buildChartShortcode, deferChartShortcodes, parseChartSpec, restoreDeferredCharts } from './chartShortcode';

describe('chartShortcode', () => {
  it('builds a valid chart block', () => {
    const block = buildChartShortcode({
      type: 'bar',
      labels: ['A', 'B'],
      values: [10, 20],
    });
    expect(block).toContain(':::chart');
    expect(block).toContain('"labels"');
  });

  it('defers and restores preview placeholders', () => {
    const block = buildChartShortcode({
      type: 'line',
      title: 'Sales',
      labels: ['Q1', 'Q2'],
      values: [1, 2],
    });
    const { markdown, renders } = deferChartShortcodes(block);
    expect(markdown).toContain('<!-- paginium-chart:0 -->');
    expect(renders['paginium-chart:0']).toContain('paginium-chart--preview');
    const html = restoreDeferredCharts('<!-- paginium-chart:0 -->', renders);
    expect(html).toContain('Sales');
  });

  it('rejects invalid specs', () => {
    expect(parseChartSpec({ type: 'pie', labels: ['A'], values: [1] })).toBeNull();
    expect(parseChartSpec({ type: 'bar', labels: ['A'], values: ['x'] })).toBeNull();
  });
});
