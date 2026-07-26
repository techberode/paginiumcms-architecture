import type { Extension } from '@tiptap/core';
import { loadExtensionModule } from '../extensions/loader';
import { getEditorComponentRegistration } from '../extensions/hello-widget';
import type { EditorProfileDefinition } from './editorProfiles';

export interface EditorComponentRegistration {
  id: string;
  label: string;
  markdownInsert: () => string;
  tiptapNodeName: string;
  loadTiptapExtension: () => Promise<Extension>;
}

export interface EditorComponentDefinition {
  id: string;
  label: string;
  pluginId: string;
  markdownDirective: string;
  tiptapNodeType: string;
}

export function parseProfileCustomComponents(raw: unknown): Record<string, string[]> {
  if (typeof raw === 'string') {
    try {
      const decoded = JSON.parse(raw) as unknown;
      return parseProfileCustomComponents(decoded);
    } catch {
      return {};
    }
  }

  if (!raw || typeof raw !== 'object') {
    return {};
  }

  const map: Record<string, string[]> = {};
  for (const [profileId, componentIds] of Object.entries(raw as Record<string, unknown>)) {
    if (!Array.isArray(componentIds)) {
      continue;
    }
    map[profileId] = componentIds.filter((id): id is string => typeof id === 'string' && id.trim() !== '');
  }

  return map;
}

export function profileAllowsCustomComponent(
  profile: EditorProfileDefinition,
  componentId: string,
  settings?: Record<string, unknown>
): boolean {
  if (settings?.customComponentsEnabled !== true) {
    return false;
  }

  if (profile.customComponents?.includes(componentId)) {
    return true;
  }

  const map = parseProfileCustomComponents(settings.profileCustomComponents);
  return (map[profile.id] ?? []).includes(componentId);
}

export function allowedCustomComponentsForProfile(
  profile: EditorProfileDefinition,
  settings?: Record<string, unknown>
): string[] {
  if (settings?.customComponentsEnabled !== true) {
    return [];
  }

  if (Array.isArray(profile.customComponents) && profile.customComponents.length > 0) {
    return profile.customComponents;
  }

  const map = parseProfileCustomComponents(settings?.profileCustomComponents);
  return map[profile.id] ?? [];
}

export async function loadEditorComponentRegistration(
  componentId: string
): Promise<EditorComponentRegistration | null> {
  const direct = getEditorComponentRegistration(componentId);
  if (direct) {
    return direct;
  }

  const module = await loadExtensionModule(componentId);
  if (!module || typeof module !== 'object') {
    return null;
  }

  const registration = (module as { editorComponent?: EditorComponentRegistration }).editorComponent;
  return registration ?? null;
}

export async function loadAllowedEditorComponents(
  profile: EditorProfileDefinition,
  settings?: Record<string, unknown>
): Promise<EditorComponentRegistration[]> {
  const allowed = allowedCustomComponentsForProfile(profile, settings);
  const loaded: EditorComponentRegistration[] = [];

  for (const componentId of allowed) {
    const registration = await loadEditorComponentRegistration(componentId);
    if (registration) {
      loaded.push(registration);
    }
  }

  return loaded;
}
