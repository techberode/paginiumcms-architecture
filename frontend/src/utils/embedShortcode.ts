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

export function normalizeEmbedVideoId(
  provider: ExternalEmbedProvider,
  raw: string
): string {
  const trimmed = raw.trim();
  if (ID_PATTERNS[provider]?.test(trimmed)) {
    return trimmed;
  }

  if (provider === 'youtube') {
    return parseYoutubeVideoId(trimmed) ?? '';
  }

  if (provider === 'vimeo') {
    return parseVimeoVideoId(trimmed) ?? '';
  }

  return '';
}

export function parseYoutubeVideoId(input: string): string | null {
  const trimmed = input.trim();
  if (ID_PATTERNS.youtube.test(trimmed)) {
    return trimmed;
  }

  try {
    const url = new URL(trimmed.includes('://') ? trimmed : `https://${trimmed}`);
    const host = url.hostname.replace(/^www\./, '').replace(/^m\./, '');

    if (host === 'youtu.be') {
      const id = url.pathname.replace(/^\//, '').split('/')[0] ?? '';
      return ID_PATTERNS.youtube.test(id) ? id : null;
    }

    if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
      const fromQuery = url.searchParams.get('v');
      if (fromQuery && ID_PATTERNS.youtube.test(fromQuery)) {
        return fromQuery;
      }

      const embed = url.pathname.match(/^\/embed\/([a-zA-Z0-9_-]{11})/);
      if (embed?.[1]) {
        return embed[1];
      }

      const shorts = url.pathname.match(/^\/shorts\/([a-zA-Z0-9_-]{11})/);
      if (shorts?.[1]) {
        return shorts[1];
      }
    }
  } catch {
    return null;
  }

  return null;
}

export function parseVimeoVideoId(input: string): string | null {
  const trimmed = input.trim();
  if (ID_PATTERNS.vimeo.test(trimmed)) {
    return trimmed;
  }

  try {
    const url = new URL(trimmed.includes('://') ? trimmed : `https://${trimmed}`);
    const host = url.hostname.replace(/^www\./, '');
    if (host !== 'vimeo.com' && host !== 'player.vimeo.com') {
      return null;
    }

    const fromPath = url.pathname.match(/\/(?:video\/)?(\d{1,20})/);
    const id = fromPath?.[1] ?? '';
    return ID_PATTERNS.vimeo.test(id) ? id : null;
  } catch {
    return null;
  }
}

/** Standalone line that is only a video URL → :::embed block (public + admin preview). */
export function promoteStandaloneVideoUrls(markdown: string): string {
  const toEmbed = (provider: ExternalEmbedProvider, id: string) =>
    `\n\n:::embed\nprovider: ${provider}\nid: ${id}\n:::\n`;

  const lineReplace = (source: string, re: RegExp, provider: ExternalEmbedProvider) =>
    source.replace(re, (_match, id: string) => toEmbed(provider, id));

  let result = markdown;
  result = lineReplace(
    result,
    /^(?:[ \t]*)https?:\/\/(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]{11})\/?(?:\?[^\s]*)?(?:[ \t]*)$/gmu,
    'youtube'
  );
  result = lineReplace(
    result,
    /^(?:[ \t]*)https?:\/\/(?:www\.|m\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]{11})[^\s]*(?:[ \t]*)$/gmu,
    'youtube'
  );
  result = lineReplace(
    result,
    /^(?:[ \t]*)https?:\/\/(?:www\.|m\.)?youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})[^\s]*(?:[ \t]*)$/gmu,
    'youtube'
  );
  result = lineReplace(
    result,
    /^(?:[ \t]*)https?:\/\/[^\s]*[?&]v=([a-zA-Z0-9_-]{11})(?:&[^\s]*)?(?:[ \t]*)$/gmu,
    'youtube'
  );
  result = lineReplace(
    result,
    /^(?:[ \t]*)https?:\/\/(?:www\.)?(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d{1,20})[^\s]*(?:[ \t]*)$/gmu,
    'vimeo'
  );

  return result;
}

export function buildEmbedShortcode(provider: ExternalEmbedProvider, id: string): string {
  const normalizedProvider = provider.trim().toLowerCase() as ExternalEmbedProvider;
  const normalizedId = normalizeEmbedVideoId(normalizedProvider, id);
  if (normalizedId === '') {
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
  const source = promoteStandaloneVideoUrls(markdown);
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

  let result = source.replace(
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
