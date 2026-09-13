/** Mirrors backend :::chart shortcode for admin Markdown preview (It.90e). */

export type ChartType = 'bar' | 'line';

export interface ChartSpec {
  type: ChartType;
  title?: string;
  labels: string[];
  values: number[];
}

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

export function parseChartSpec(raw: unknown): ChartSpec | null {
  if (!raw || typeof raw !== 'object') {
    return null;
  }

  const spec = raw as {
    type?: unknown;
    title?: unknown;
    labels?: unknown;
    values?: unknown;
  };

  const type = typeof spec.type === 'string' ? spec.type.trim().toLowerCase() : 'bar';
  if (type !== 'bar' && type !== 'line') {
    return null;
  }

  if (!Array.isArray(spec.labels) || !Array.isArray(spec.values)) {
    return null;
  }

  if (spec.labels.length === 0 || spec.labels.length > 12 || spec.labels.length !== spec.values.length) {
    return null;
  }

  const labels: string[] = [];
  const values: number[] = [];

  for (let index = 0; index < spec.labels.length; index += 1) {
    const label = String(spec.labels[index] ?? '').trim();
    if (label === '' || label.length > 40) {
      return null;
    }

    const value = spec.values[index];
    if (typeof value !== 'number' || Number.isNaN(value) || value < 0 || value > 100000) {
      return null;
    }

    labels.push(label);
    values.push(value);
  }

  const title = typeof spec.title === 'string' ? spec.title.trim() : '';
  if (title.length > 120) {
    return null;
  }

  return {
    type: type as ChartType,
    title: title || undefined,
    labels,
    values,
  };
}

export function buildChartShortcode(spec: ChartSpec): string {
  const parsed = parseChartSpec(spec);
  if (!parsed) {
    return '';
  }

  const json = JSON.stringify(parsed, null, 2);

  return `\n\n:::chart\n${json}\n:::\n`;
}

export function deferChartShortcodes(markdown: string): {
  markdown: string;
  renders: Record<string, string>;
} {
  const renders: Record<string, string> = {};
  let index = 0;

  const next = markdown.replace(
    /:::chart\s*\n([\s\S]*?)\n\s*:::/g,
    (_match, body: string) => {
      try {
        const decoded = JSON.parse(body.trim()) as unknown;
        const spec = parseChartSpec(decoded);
        if (!spec) {
          return '';
        }

        const key = `paginium-chart:${index}`;
        index += 1;
        renders[key] =
          `<figure class="paginium-chart paginium-chart--preview" role="img" aria-label="Chart preview">` +
          `<pre class="paginium-chart__source">${escapeHtml(JSON.stringify(spec, null, 2))}</pre></figure>`;

        return `\n\n<!-- ${key} -->\n\n`;
      } catch {
        return '';
      }
    }
  );

  return { markdown: next, renders };
}

export function restoreDeferredCharts(html: string, renders: Record<string, string>): string {
  let result = html;
  for (const [key, fragment] of Object.entries(renders)) {
    const comment = `<!-- ${key} -->`;
    result = result.replace(comment, fragment);
    result = result.replace(`<p>${comment}</p>`, fragment);
  }

  return result;
}
