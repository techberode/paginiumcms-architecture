const extensionModules = import.meta.glob('./*/index.ts');

export async function loadExtensionModule(id: string): Promise<unknown | null> {
  const key = `./${id}/index.ts`;
  const loader = extensionModules[key];
  if (!loader) {
    return null;
  }

  return loader();
}

export function listBundledExtensionIds(): string[] {
  return Object.keys(extensionModules).map((key) => key.replace('./', '').replace('/index.ts', ''));
}
