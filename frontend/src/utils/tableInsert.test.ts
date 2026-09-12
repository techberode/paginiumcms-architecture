import { describe, expect, it } from 'vitest';
import { buildGfmTable } from './tableInsert';

describe('buildGfmTable', () => {
  it('builds table with header and separator', () => {
    const table = buildGfmTable({ columns: 2, rows: 1, includeHeader: true });

    expect(table).toContain('| Header 1 | Header 2 |');
    expect(table).toContain('| --- | --- |');
    expect(table).toContain('| Cell | Cell |');
  });

  it('builds table without header row', () => {
    const table = buildGfmTable({ columns: 3, rows: 2, includeHeader: false });

    expect(table).not.toContain('Header 1');
    expect(table.match(/\|/g)?.length).toBeGreaterThan(6);
  });
});
