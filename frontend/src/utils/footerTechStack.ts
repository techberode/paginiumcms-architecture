export type FooterTechStackItem = {
  id: string;
  label: string;
  url: string;
  icon: string;
  enabled: boolean;
};

export function defaultFooterTechStack(): FooterTechStackItem[] {
  return [
    { id: 'php', label: 'PHP 8.5', url: 'https://www.php.net/', icon: 'php', enabled: true },
    { id: 'react', label: 'React', url: 'https://react.dev/', icon: 'react', enabled: true },
    { id: 'vite', label: 'Vite', url: 'https://vite.dev/', icon: 'vite', enabled: true },
  ];
}

export function parseFooterTechStackJson(raw: string): FooterTechStackItem[] {
  const trimmed = raw.trim();
  if (trimmed === '' || trimmed === '[]') {
    return [];
  }
  try {
    const decoded = JSON.parse(trimmed) as unknown;
    if (!Array.isArray(decoded)) {
      return [];
    }
    return decoded
      .filter((item): item is Record<string, unknown> => item !== null && typeof item === 'object')
      .map((item) => {
        const id = String(item.id ?? '').trim();
        const iconRaw = String(item.icon ?? id).trim().toLowerCase();
        return {
          id,
          label: String(item.label ?? '').trim(),
          url: String(item.url ?? '').trim(),
          icon: iconRaw !== '' ? iconRaw : id,
          enabled: item.enabled !== false,
        };
      })
      .filter((item) => item.id !== '' && item.label !== '');
  } catch {
    return [];
  }
}

export function serializeFooterTechStackJson(items: FooterTechStackItem[]): string {
  return JSON.stringify(items);
}
