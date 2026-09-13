/** Mirrors backend :::mermaid shortcode for admin Markdown preview (It.90c). */

export function buildMermaidShortcode(source: string): string {
  const trimmed = source.trim();
  if (trimmed === '') {
    return '';
  }

  return `\n\n:::mermaid\n${trimmed}\n:::\n`;
}

export function deferMermaidShortcodes(markdown: string): {
  markdown: string;
  renders: Record<string, string>;
} {
  const renders: Record<string, string> = {};
  let index = 0;

  const result = markdown.replace(
    /:::mermaid\s*\n([\s\S]*?)\n\s*:::/g,
    (_match, body: string) => {
      const trimmed = body.trim();
      if (trimmed === '') {
        return '';
      }

      const key = `paginium-mermaid:${index}`;
      index += 1;
      renders[key] =
        `<figure class="paginium-mermaid paginium-mermaid--preview" role="img" aria-label="Diagram preview">` +
        `<pre class="paginium-mermaid__source">${escapeHtml(trimmed)}</pre></figure>`;

      return `\n\n<!-- ${key} -->\n\n`;
    }
  );

  return { markdown: result, renders };
}

export function restoreDeferredMermaid(html: string, renders: Record<string, string>): string {
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
