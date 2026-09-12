/** Normalize deploy ref for backend (semver tags require a `v` prefix). */
export function normalizeDeployRef(ref: string): string {
  const trimmed = ref.trim();
  if (/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?$/.test(trimmed)) {
    return `v${trimmed}`;
  }

  return trimmed;
}
