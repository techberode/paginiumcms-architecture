export interface ContentDateLabels {
  primary: string;
  secondary?: string;
  primaryTitle: string;
  secondaryTitle?: string;
}

function parseContentDate(value: string | number | undefined): Date | null {
  if (value === undefined || value === null || value === '') {
    return null;
  }

  if (typeof value === 'number') {
    const fromNumber = new Date(value * 1000 > 1_000_000_000_000 ? value : value * 1000);
    return Number.isNaN(fromNumber.getTime()) ? null : fromNumber;
  }

  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function formatSkDate(date: Date): string {
  return date.toLocaleDateString('sk-SK', {
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
  });
}

/** Builds primary/secondary date labels for blog cards and article headers. */
export function formatContentDateLabels(input: {
  createdAt?: string | number;
  updatedAt?: string | number;
  frontMatterDate?: string | number;
}): ContentDateLabels {
  const created = parseContentDate(input.frontMatterDate ?? input.createdAt);
  const updated = parseContentDate(input.updatedAt);

  if (!created && !updated) {
    return {
      primary: '—',
      primaryTitle: 'Dátum',
    };
  }

  const primaryDate = created ?? updated!;
  const primary = formatSkDate(primaryDate);

  if (updated && created && updated.getTime() - created.getTime() > 60_000) {
    return {
      primary,
      secondary: formatSkDate(updated),
      primaryTitle: 'Vytvorené',
      secondaryTitle: 'Upravené',
    };
  }

  return {
    primary,
    primaryTitle: created ? 'Vytvorené' : 'Dátum',
  };
}
