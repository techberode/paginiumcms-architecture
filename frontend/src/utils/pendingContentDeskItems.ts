import type { DeskItem } from '../api/auth';
import type { PendingContentDraft } from '../api/drafts';
import { editorPathForDraft } from './contentUnsavedDraft';

export function pendingDraftsToDeskItems(
  drafts: PendingContentDraft[],
  labels: { page: string; article: string }
): DeskItem[] {
  return drafts.map((draft) => ({
    kind: 'content_draft',
    id: `${draft.type}:${draft.slug}`,
    title: draft.type === 'article' ? labels.article : labels.page,
    preview: draft.title?.trim() || draft.slug,
    href: editorPathForDraft(draft.type, draft.slug),
    createdAt: new Date((draft.savedAt ?? 0) * 1000).toISOString(),
  }));
}
