/** Mirrors backend CalloutShortcode for admin Markdown preview (It.90b). */

export type CalloutType = 'note' | 'tip' | 'warning';

export const CALLOUT_TYPES: CalloutType[] = ['note', 'tip', 'warning'];

export function buildCalloutShortcode(type: CalloutType, body: string): string {
  const trimmed = body.trim();
  if (trimmed === '' || !CALLOUT_TYPES.includes(type)) {
    return '';
  }

  return `\n\n:::${type}\n${trimmed}\n:::\n`;
}

export function deferCalloutShortcodes(markdown: string): {
  markdown: string;
  renders: Record<string, string>;
} {
  const renders: Record<string, string> = {};
  let index = 0;
  let result = markdown;

  for (const type of CALLOUT_TYPES) {
    const pattern = new RegExp(`:::${type}\\s*\\n([\\s\\S]*?)\\n\\s*:::`, 'g');
    result = result.replace(pattern, (_match, body: string) => {
      const trimmed = body.trim();
      if (trimmed === '') {
        return '';
      }
      const key = `paginium-callout:${type}:${index}`;
      index += 1;
      renders[key] = `<div class="paginium-callout paginium-callout--${type}"><p>${escapeHtml(trimmed)}</p></div>`;

      return `\n\n<!-- ${key} -->\n\n`;
    });
  }

  return { markdown: result, renders };
}

export function restoreDeferredCallouts(html: string, renders: Record<string, string>): string {
  let output = html;
  for (const [key, fragment] of Object.entries(renders)) {
    const comment = `<!-- ${key} -->`;
    output = output.split(comment).join(fragment);
    output = output.split(`<p>${comment}</p>`).join(fragment);
  }

  return output;
}

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}
