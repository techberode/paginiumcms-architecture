import type { NewsletterPreferenceKey } from '../../api/newsletter';

export type { NewsletterPreferenceKey };

export const ALL_NEWSLETTER_PREFERENCES: NewsletterPreferenceKey[] = [
  'weekly_digest',
  'new_article',
  'cms_release',
  'general_news',
];
