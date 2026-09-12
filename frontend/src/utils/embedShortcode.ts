/** Mirrors backend ExternalEmbedShortcode for admin Markdown preview (It.91b). */

export type ExternalEmbedProvider = 'youtube' | 'vimeo';

const EMBED_URLS: Record<ExternalEmbedProvider, string> = {
  youtube: 'https://www.youtube-nocookie.com/embed/',
  vimeo: 'https://player.vimeo.com/video/',
};

const ID_PATTERNS: Record<ExternalEmbedProvider, RegExp> = {
  youtube: /^[a-zA-Z0-9_-]{11}$/,
  vimeo: /^\d{1,20}$/,
};

export function buildEmbedShortcode(provider: ExternalEmbedProvider, id: string): string {
  const normalizedProvider = provider.trim().toLowerCase() as ExternalEmbedProvider;
  const normalizedId = id.trim();
  if (!ID_PATTERNS[normalizedProvider]?.test(normalizedId)) {
    return '';
  }

  return `\n\n:::embed\nprovider: ${normalizedProvider}\nid: ${normalizedId}\n:::\n`;
}

export function expandEmbedShortcodes(markdown: string): string {
  const { markdown: deferred, renders } = deferEmbedShortcodes(markdown);
  return restoreDeferredEmbeds(deferred, renders);
}

export function deferEmbedShortcodes(markdown: string): {
  markdown: string;
  renders: Record<string, string>;
} {
  const renders: Record<string, string> = {};
  let index = 0;

  const replace = (_match: string, providerRaw: string, idRaw: string): string => {
    const html = renderEmbed(providerRaw, idRaw);
    if (html === '') {
      return '';
    }
    const key = `paginium-embed:${index}`;
    index += 1;
    renders[key] = html;

    return `\n\n<!-- ${key} -->\n\n`;
  };

  let result = markdown.replace(
    /:::embed\s*\n\s*provider:\s*(\S+)\s*\n\s*id:\s*(\S+)\s*\n\s*:::/g,
    replace
  );

  result = result.replace(
    /:::embed\s+provider="([^"]+)"\s+id="([^"]+)"\s*:::/g,
    replace
  );

  return { markdown: result, renders };
}

export function restoreDeferredEmbeds(html: string, renders: Record<string, string>): string {
  let output = html;
  for (const [key, fragment] of Object.entries(renders)) {
    const comment = `<!-- ${key} -->`;
    output = output.split(comment).join(fragment);
    output = output.split(`<p>${comment}</p>`).join(fragment);
  }

  return output;
}

function renderEmbed(providerRaw: string, idRaw: string): string {
  const provider = providerRaw.trim().toLowerCase() as ExternalEmbedProvider;
  const id = idRaw.trim();
  if (!EMBED_URLS[provider] || !ID_PATTERNS[provider]?.test(id)) {
    return '';
  }

  const src = `${EMBED_URLS[provider]}${encodeURIComponent(id)}`;

  return `<iframe class="paginium-external-embed" src="${src}" title="${provider} embed" width="560" height="315" loading="lazy" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" sandbox="allow-scripts allow-same-origin allow-presentation"></iframe>`;
}
