import type { SortDirection } from '../hooks/useColumnSort';

export const MAX_CUSTOM_CONTENT_VIEWS = 5;

export const DEFAULT_CONTENT_VIEW_IDS = {
  drafts: 'default:drafts',
  scheduled: 'default:scheduled',
  published: 'default:published',
} as const;

export type DefaultContentViewId =
  (typeof DEFAULT_CONTENT_VIEW_IDS)[keyof typeof DEFAULT_CONTENT_VIEW_IDS];

export interface ContentFilterPreset {
  status: string;
  search: string;
  tag: string;
  seoIssuesOnly: boolean;
  staleOnly: boolean;
  sortField: string;
  sortDirection: SortDirection;
}

export interface ContentSavedView {
  id: string;
  name: string;
  preset: ContentFilterPreset;
  kind: 'default' | 'custom';
  labelKey?: string;
}

export interface ContentSavedViewsStorage {
  hiddenDefaultIds: string[];
  customViews: Array<{
    id: string;
    name: string;
    preset: ContentFilterPreset;
  }>;
}

export function contentSavedViewsStorageKey(userId: string, contentType: 'pages' | 'articles'): string {
  const scope = userId.trim() === '' ? 'anonymous' : userId.trim();
  return `paginium:content-views:${scope}:${contentType}`;
}

export function createDefaultContentViews(): ContentSavedView[] {
  return [
    {
      id: DEFAULT_CONTENT_VIEW_IDS.drafts,
      name: '',
      labelKey: 'content.savedViews.defaults.drafts',
      kind: 'default',
      preset: {
        status: 'draft',
        search: '',
        tag: '',
        seoIssuesOnly: false,
        staleOnly: false,
        sortField: 'updatedAt',
        sortDirection: 'desc',
      },
    },
    {
      id: DEFAULT_CONTENT_VIEW_IDS.scheduled,
      name: '',
      labelKey: 'content.savedViews.defaults.scheduled',
      kind: 'default',
      preset: {
        status: 'scheduled',
        search: '',
        tag: '',
        seoIssuesOnly: false,
        staleOnly: false,
        sortField: 'updatedAt',
        sortDirection: 'desc',
      },
    },
    {
      id: DEFAULT_CONTENT_VIEW_IDS.published,
      name: '',
      labelKey: 'content.savedViews.defaults.published',
      kind: 'default',
      preset: {
        status: 'published',
        search: '',
        tag: '',
        seoIssuesOnly: false,
        staleOnly: false,
        sortField: 'updatedAt',
        sortDirection: 'desc',
      },
    },
  ];
}

export function normalizeContentFilterPreset(input: Partial<ContentFilterPreset> | null | undefined): ContentFilterPreset {
  const sortDirection: SortDirection = input?.sortDirection === 'asc' ? 'asc' : 'desc';
  const sortField = typeof input?.sortField === 'string' && input.sortField.trim() !== ''
    ? input.sortField.trim()
    : 'updatedAt';

  return {
    status: typeof input?.status === 'string' && input.status.trim() !== '' ? input.status.trim() : 'all',
    search: typeof input?.search === 'string' ? input.search.trim() : '',
    tag: typeof input?.tag === 'string' ? input.tag.trim() : '',
    seoIssuesOnly: input?.seoIssuesOnly === true,
    staleOnly: input?.staleOnly === true,
    sortField,
    sortDirection,
  };
}

export function parseContentSavedViewsStorage(raw: string | null): ContentSavedViewsStorage {
  if (!raw) {
    return { hiddenDefaultIds: [], customViews: [] };
  }

  try {
    const parsed = JSON.parse(raw) as Partial<ContentSavedViewsStorage>;
    const hiddenDefaultIds = Array.isArray(parsed.hiddenDefaultIds)
      ? parsed.hiddenDefaultIds.filter((id): id is string => typeof id === 'string' && id.trim() !== '')
      : [];
    const customViews = Array.isArray(parsed.customViews)
      ? parsed.customViews
          .filter(
            (view): view is ContentSavedViewsStorage['customViews'][number] =>
              typeof view?.id === 'string'
              && typeof view?.name === 'string'
              && typeof view?.preset === 'object'
              && view.preset !== null
          )
          .map((view) => ({
            id: view.id,
            name: view.name.trim(),
            preset: normalizeContentFilterPreset(view.preset),
          }))
          .filter((view) => view.name !== '')
      : [];

    return {
      hiddenDefaultIds,
      customViews: customViews.slice(0, MAX_CUSTOM_CONTENT_VIEWS),
    };
  } catch {
    return { hiddenDefaultIds: [], customViews: [] };
  }
}

export function serializeContentSavedViewsStorage(state: ContentSavedViewsStorage): string {
  return JSON.stringify({
    hiddenDefaultIds: state.hiddenDefaultIds,
    customViews: state.customViews.map((view) => ({
      id: view.id,
      name: view.name,
      preset: normalizeContentFilterPreset(view.preset),
    })),
  });
}

export function mergeVisibleContentViews(state: ContentSavedViewsStorage): ContentSavedView[] {
  const defaults = createDefaultContentViews().filter(
    (view) => !state.hiddenDefaultIds.includes(view.id)
  );
  const customs: ContentSavedView[] = state.customViews.map((view) => ({
    id: view.id,
    name: view.name,
    preset: normalizeContentFilterPreset(view.preset),
    kind: 'custom',
  }));

  return [...defaults, ...customs];
}

export function contentFilterPresetsEqual(a: ContentFilterPreset, b: ContentFilterPreset): boolean {
  const left = normalizeContentFilterPreset(a);
  const right = normalizeContentFilterPreset(b);

  return (
    left.status === right.status
    && left.search === right.search
    && left.tag === right.tag
    && left.seoIssuesOnly === right.seoIssuesOnly
    && left.sortField === right.sortField
    && left.sortDirection === right.sortDirection
  );
}

export function addCustomContentView(
  state: ContentSavedViewsStorage,
  name: string,
  preset: ContentFilterPreset
): { state: ContentSavedViewsStorage; view: ContentSavedView | null; error?: 'limit' | 'name' } {
  const trimmedName = name.trim();
  if (trimmedName === '') {
    return { state, view: null, error: 'name' };
  }

  if (state.customViews.length >= MAX_CUSTOM_CONTENT_VIEWS) {
    return { state, view: null, error: 'limit' };
  }

  const id = `custom:${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
  const view: ContentSavedView = {
    id,
    name: trimmedName,
    preset: normalizeContentFilterPreset(preset),
    kind: 'custom',
  };

  return {
    state: {
      ...state,
      customViews: [
        ...state.customViews,
        {
          id: view.id,
          name: view.name,
          preset: view.preset,
        },
      ],
    },
    view,
  };
}

export function removeCustomContentView(state: ContentSavedViewsStorage, viewId: string): ContentSavedViewsStorage {
  return {
    ...state,
    customViews: state.customViews.filter((view) => view.id !== viewId),
  };
}

export function renameCustomContentView(
  state: ContentSavedViewsStorage,
  viewId: string,
  name: string
): { state: ContentSavedViewsStorage; error?: 'name' } {
  const trimmedName = name.trim();
  if (trimmedName === '') {
    return { state, error: 'name' };
  }

  return {
    state: {
      ...state,
      customViews: state.customViews.map((view) =>
        view.id === viewId ? { ...view, name: trimmedName } : view
      ),
    },
  };
}

export function hideDefaultContentView(state: ContentSavedViewsStorage, viewId: string): ContentSavedViewsStorage {
  if (state.hiddenDefaultIds.includes(viewId)) {
    return state;
  }

  return {
    ...state,
    hiddenDefaultIds: [...state.hiddenDefaultIds, viewId],
  };
}

export function loadContentSavedViews(userId: string, contentType: 'pages' | 'articles'): ContentSavedViewsStorage {
  if (typeof window === 'undefined') {
    return { hiddenDefaultIds: [], customViews: [] };
  }

  const raw = window.localStorage.getItem(contentSavedViewsStorageKey(userId, contentType));
  return parseContentSavedViewsStorage(raw);
}

export function saveContentSavedViews(
  userId: string,
  contentType: 'pages' | 'articles',
  state: ContentSavedViewsStorage
): void {
  if (typeof window === 'undefined') {
    return;
  }

  window.localStorage.setItem(
    contentSavedViewsStorageKey(userId, contentType),
    serializeContentSavedViewsStorage(state)
  );
}
