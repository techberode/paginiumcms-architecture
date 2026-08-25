/** Public GitHub docs base — admin external help links (not served by SPA). */
export const REPOSITORY_DOCS_BASE =
  'https://github.com/techberode/paginiumcms-architecture/blob/main';

export function repositoryDoc(path: string): string {
  const normalized = path.replace(/^\//, '');
  return `${REPOSITORY_DOCS_BASE}/${normalized}`;
}
