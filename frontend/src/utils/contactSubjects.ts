const DEFAULT_SUBJECTS = [
  'Všeobecný dotaz',
  'Technická podpora',
  'Obchodná spolupráca',
  'Informácie o produkte',
];

export function parseContactSubjects(raw: unknown): string[] {
  if (typeof raw !== 'string' || raw.trim() === '') {
    return DEFAULT_SUBJECTS;
  }

  const parsed = raw
    .split('\n')
    .map((line) => line.trim())
    .filter(Boolean);

  return parsed.length > 0 ? parsed : DEFAULT_SUBJECTS;
}
