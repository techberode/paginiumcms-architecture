export function bodyLooksLikeMetadataLeak(body: string): boolean {
  if (body.trim() === '') {
    return false;
  }

  return (
    /\nseo:\n\s+title:/.test(body) ||
    body.includes('localeStatus:') ||
    body.includes('seoTitle:') ||
    /\nslug:\s+\S+\s+title:/.test(body) ||
    /\nschemaVersion:\s+[0-9]/.test(body)
  );
}

export function stripEmbeddedMetadataLeak(body: string): string {
  if (!bodyLooksLikeMetadataLeak(body)) {
    return body;
  }

  const patterns = [
    /\nseo:\n\s+title:/,
    /\nlocaleStatus:/,
    /\nslug:\s+\S+\s+title:/,
    /\nupdatedAt:\s+['"]/,
    /\nschemaVersion:\s+[0-9]/,
  ];

  let cutAt: number | null = null;
  for (const pattern of patterns) {
    const match = pattern.exec(body);
    if (match && match.index > 0 && (cutAt === null || match.index < cutAt)) {
      cutAt = match.index;
    }
  }

  if (cutAt === null) {
    return body;
  }

  return body.slice(0, cutAt).trimEnd();
}
