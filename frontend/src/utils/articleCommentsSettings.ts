export type TriStateSetting = 'inherit' | 'yes' | 'no';

export interface ArticleCommentsSettings {
  commentsEnabled: boolean;
  commentsRequireApproval: TriStateSetting;
  commentsAllowGuests: TriStateSetting;
}

export const DEFAULT_ARTICLE_COMMENTS_SETTINGS: ArticleCommentsSettings = {
  commentsEnabled: true,
  commentsRequireApproval: 'inherit',
  commentsAllowGuests: 'inherit',
};

export function triStateFromApi(value: boolean | null | undefined): TriStateSetting {
  if (value === true) {
    return 'yes';
  }
  if (value === false) {
    return 'no';
  }
  return 'inherit';
}

export function triStateToApi(value: TriStateSetting): boolean | null {
  if (value === 'yes') {
    return true;
  }
  if (value === 'no') {
    return false;
  }
  return null;
}
