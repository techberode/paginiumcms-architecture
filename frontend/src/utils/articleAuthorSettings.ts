export interface ArticleAuthorSettings {
  authorId: string;
  author: string;
  authorBio: string;
  authorAvatarUrl: string;
}

export const DEFAULT_ARTICLE_AUTHOR: ArticleAuthorSettings = {
  authorId: '',
  author: '',
  authorBio: '',
  authorAvatarUrl: '',
};

export function articleAuthorFromFrontMatter(
  frontMatter: Record<string, unknown> | undefined,
  extras?: {
    authorId?: string;
    authorBioStored?: string;
    authorAvatarUrlStored?: string;
  }
): ArticleAuthorSettings {
  const fm = frontMatter ?? {};

  return {
    authorId: String(extras?.authorId ?? fm.authorId ?? '').trim(),
    author: String(fm.author ?? '').trim(),
    authorBio: String(extras?.authorBioStored ?? fm.authorBio ?? '').trim(),
    authorAvatarUrl: String(extras?.authorAvatarUrlStored ?? fm.authorAvatarUrl ?? '').trim(),
  };
}

export function articleAuthorToPayload(settings: ArticleAuthorSettings): Record<string, string | null> {
  return {
    authorId: settings.authorId.trim() !== '' ? settings.authorId.trim() : null,
    author: settings.author.trim(),
    authorBio: settings.authorBio.trim(),
    authorAvatarUrl: settings.authorAvatarUrl.trim(),
  };
}

export function resolvePreviewAuthorName(
  settings: ArticleAuthorSettings,
  fallbackName: string
): string {
  if (settings.author.trim() !== '') {
    return settings.author.trim();
  }

  return fallbackName;
}
