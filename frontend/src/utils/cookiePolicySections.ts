export interface CookiePolicySection {
  id: string;
  title: string;
  body: string;
}

export const MAX_COOKIE_POLICY_SECTIONS = 20;

export function parseCookiePolicySectionsJson(raw: string | undefined | null): CookiePolicySection[] {
  if (!raw || raw.trim() === '') {
    return [];
  }

  try {
    const decoded = JSON.parse(raw) as unknown;
    if (!Array.isArray(decoded)) {
      return [];
    }

    const sections: CookiePolicySection[] = [];
    for (const item of decoded) {
      if (!item || typeof item !== 'object') {
        continue;
      }

      const record = item as Record<string, unknown>;
      const title = typeof record.title === 'string' ? record.title.trim() : '';
      const body = typeof record.body === 'string' ? record.body.trim() : '';
      if (title === '' && body === '') {
        continue;
      }

      const id =
        typeof record.id === 'string' && record.id.trim() !== ''
          ? record.id.trim()
          : `section-${sections.length + 1}`;

      sections.push({
        id,
        title: title.slice(0, 200),
        body: body.slice(0, 5000),
      });

      if (sections.length >= MAX_COOKIE_POLICY_SECTIONS) {
        break;
      }
    }

    return sections;
  } catch {
    return [];
  }
}

export function serializeCookiePolicySectionsJson(sections: CookiePolicySection[]): string {
  const normalized = sections
    .map((section, index) => ({
      id: section.id.trim() || `section-${index + 1}`,
      title: section.title.trim().slice(0, 200),
      body: section.body.trim().slice(0, 5000),
    }))
    .filter((section) => section.title !== '' || section.body !== '')
    .slice(0, MAX_COOKIE_POLICY_SECTIONS);

  return JSON.stringify(normalized);
}

export function createEmptyCookiePolicySection(): CookiePolicySection {
  return {
    id: `section-${Date.now()}`,
    title: '',
    body: '',
  };
}
