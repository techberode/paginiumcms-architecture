import React, { useEffect } from 'react';
import { useSettingsContext } from '../../context/SettingsContext';

type ThemeScript = {
  src: string;
  integrity: string;
  load: string;
};

export const ThemeScriptLoader: React.FC = () => {
  const { settings } = useSettingsContext();
  const enabled = settings.appearance?.themeScriptsEnabled === true;
  const scripts = enabled ? (settings.appearance?.themeScripts ?? []) : [];

  const scriptKey = scripts.map((script) => `${script.src}|${script.integrity}|${script.load}`).join(';');

  useEffect(() => {
    if (!enabled || scriptKey === '') {
      return;
    }

    const parsed = scriptKey.split(';').map((entry) => {
      const [src, integrity, load] = entry.split('|');
      return { src: src ?? '', integrity: integrity ?? '', load: load ?? 'defer' };
    });

    const nodes: HTMLScriptElement[] = [];
    for (const script of parsed) {
      if (!isSafeThemeScript(script)) {
        continue;
      }
      if (document.querySelector(`script[src="${script.src.replace(/"/g, '')}"]`)) {
        continue;
      }
      const node = document.createElement('script');
      node.src = script.src;
      node.integrity = script.integrity;
      node.crossOrigin = 'anonymous';
      if (script.load === 'async') {
        node.async = true;
      } else if (script.load !== 'blocking') {
        node.defer = true;
      }
      document.body.appendChild(node);
      nodes.push(node);
    }

    return () => {
      for (const node of nodes) {
        node.remove();
      }
    };
  }, [enabled, scriptKey]);

  return null;
};

function isSafeThemeScript(script: ThemeScript): boolean {
  if (!script.src.startsWith('/theme-assets/')) {
    return false;
  }
  if (script.src.includes('..') || script.src.includes('://')) {
    return false;
  }
  return script.integrity.startsWith('sha384-');
}

export default ThemeScriptLoader;
