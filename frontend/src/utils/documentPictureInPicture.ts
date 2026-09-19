const PIP_PREF_KEY = 'paginium.desk.alwaysOnTop';

type DocumentPipApi = {
  requestWindow: (options?: { width?: number; height?: number }) => Promise<Window>;
};

function documentPipApi(): DocumentPipApi | null {
  if (typeof window === 'undefined') {
    return null;
  }
  const api = (window as Window & { documentPictureInPicture?: DocumentPipApi }).documentPictureInPicture;
  return api ?? null;
}

export function canOpenDocumentPip(): boolean {
  return documentPipApi() !== null;
}

export function deskAlwaysOnTopPreferred(): boolean {
  if (typeof window === 'undefined') {
    return false;
  }
  return window.localStorage.getItem(PIP_PREF_KEY) === '1';
}

export function setDeskAlwaysOnTopPreferred(enabled: boolean): void {
  if (typeof window === 'undefined') {
    return;
  }
  window.localStorage.setItem(PIP_PREF_KEY, enabled ? '1' : '0');
}

function copyStylesTo(target: Document): void {
  for (const node of Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))) {
    target.head.appendChild(node.cloneNode(true));
  }
}

export async function openDocumentPipWindow(options?: { width?: number; height?: number }): Promise<Window | null> {
  const api = documentPipApi();
  if (!api) {
    return null;
  }
  try {
    const pip = await api.requestWindow({
      width: options?.width ?? 380,
      height: options?.height ?? 560,
    });
    copyStylesTo(pip.document);
    pip.document.documentElement.className = document.documentElement.className;
    pip.document.documentElement.style.colorScheme = document.documentElement.style.colorScheme;
    pip.document.body.className = document.body.className;
    pip.document.body.style.margin = '0';
    pip.document.body.style.minHeight = '100%';
    pip.document.body.style.background = getComputedStyle(document.body).backgroundColor || '#0f172a';
    return pip;
  } catch {
    return null;
  }
}
