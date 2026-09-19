import type { Comment } from '../api/comments';

export function isRootComment(comment: Comment): boolean {
  return !comment.parentId;
}

export function commentThreadRootId(items: Comment[], id: string): string {
  const match = items.find((item) => item.id === id);
  return match?.parentId ? match.parentId : id;
}

export function commentThreadReplies(items: Comment[], rootId: string): Comment[] {
  return items
    .filter((item) => item.parentId === rootId)
    .sort((left, right) => left.createdAt.localeCompare(right.createdAt));
}

export function rootComments(items: Comment[]): Comment[] {
  return items.filter(isRootComment);
}
