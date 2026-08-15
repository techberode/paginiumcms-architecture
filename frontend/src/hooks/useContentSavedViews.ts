import { useCallback, useEffect, useMemo, useState } from 'react';
import type { ContentType } from '../api/content';
import type { ContentFilterPreset, ContentSavedView, ContentSavedViewsStorage } from '../utils/contentSavedViews';
import {
  addCustomContentView,
  contentFilterPresetsEqual,
  hideDefaultContentView,
  loadContentSavedViews,
  mergeVisibleContentViews,
  normalizeContentFilterPreset,
  removeCustomContentView,
  renameCustomContentView,
  saveContentSavedViews,
} from '../utils/contentSavedViews';

export function useContentSavedViews(userId: string | undefined, contentType: ContentType) {
  const scope = contentType;
  const storageUserId = userId ?? '';

  const [storage, setStorage] = useState<ContentSavedViewsStorage>(() =>
    loadContentSavedViews(storageUserId, scope)
  );

  useEffect(() => {
    setStorage(loadContentSavedViews(storageUserId, scope));
  }, [scope, storageUserId]);

  const persist = useCallback(
    (next: ContentSavedViewsStorage) => {
      setStorage(next);
      saveContentSavedViews(storageUserId, scope, next);
    },
    [scope, storageUserId]
  );

  const views = useMemo(() => mergeVisibleContentViews(storage), [storage]);

  const saveCurrentView = useCallback(
    (name: string, preset: ContentFilterPreset) => {
      const result = addCustomContentView(storage, name, preset);
      if (result.state !== storage) {
        persist(result.state);
      }
      return result;
    },
    [persist, storage]
  );

  const deleteView = useCallback(
    (viewId: string) => {
      persist(removeCustomContentView(storage, viewId));
    },
    [persist, storage]
  );

  const renameView = useCallback(
    (viewId: string, name: string) => {
      const result = renameCustomContentView(storage, viewId, name);
      if (result.state !== storage) {
        persist(result.state);
      }
      return result;
    },
    [persist, storage]
  );

  const hideDefaultView = useCallback(
    (viewId: string) => {
      persist(hideDefaultContentView(storage, viewId));
    },
    [persist, storage]
  );

  const isViewActive = useCallback((view: ContentSavedView, current: ContentFilterPreset) => {
    return contentFilterPresetsEqual(view.preset, normalizeContentFilterPreset(current));
  }, []);

  return {
    views,
    saveCurrentView,
    deleteView,
    renameView,
    hideDefaultView,
    isViewActive,
  };
}
