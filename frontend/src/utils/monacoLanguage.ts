const MONACO_LANGUAGES = new Set([
  'php',
  'javascript',
  'typescript',
  'html',
  'css',
  'json',
  'yaml',
  'markdown',
  'plaintext',
]);

/** Maps backend / toolbar language ids to Monaco editor language. */
export function toMonacoLanguage(language: string): string {
  const normalized = language.toLowerCase().trim();
  if (normalized === 'text') {
    return 'plaintext';
  }

  return MONACO_LANGUAGES.has(normalized) ? normalized : 'plaintext';
}
