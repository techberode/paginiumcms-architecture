/** Build a GFM table block for Markdown (It.90b). */

export function buildGfmTable(options: {
  columns: number;
  rows: number;
  includeHeader: boolean;
}): string {
  const columns = Math.max(1, Math.min(8, options.columns));
  const rows = Math.max(1, Math.min(20, options.rows));
  const includeHeader = options.includeHeader;

  const headerCells = Array.from({ length: columns }, (_, index) => `Header ${index + 1}`);
  const separatorCells = Array.from({ length: columns }, () => '---');
  const bodyRows = Array.from({ length: rows }, () =>
    Array.from({ length: columns }, () => 'Cell')
  );

  const lines: string[] = [];

  if (includeHeader) {
    lines.push(`| ${headerCells.join(' | ')} |`);
    lines.push(`| ${separatorCells.join(' | ')} |`);
  }

  for (const row of bodyRows) {
    lines.push(`| ${row.join(' | ')} |`);
  }

  return `\n\n${lines.join('\n')}\n`;
}
