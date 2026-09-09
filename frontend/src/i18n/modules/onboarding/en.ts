import type { MessageTree } from '../../types';

export const onboardingEn: MessageTree = {
  badge: 'Getting started :current / :total',
  next: 'Next',
  back: 'Back',
  skip: 'Skip',
  finish: 'Done',
  open: 'Open',
  dontShow: 'Don’t show again',
  steps: {
    dashboard: {
      title: 'Control center',
      body: 'The dashboard shows health, stale content, and project-planner KPIs. Start here after each login.',
    },
    pages: {
      title: 'Pages',
      body: 'Create the public site structure — home, contact, landing pages — then publish when ready.',
    },
    articles: {
      title: 'Articles',
      body: 'Write blog posts as drafts, schedule them, or link them to a project-plan milestone.',
    },
    media: {
      title: 'Media library',
      body: 'Upload images once; they appear in the editor, OG tags, and public heroes with responsive thumbnails.',
    },
    planner: {
      title: 'Project planner',
      body: 'Plan relaunch deadlines before content exists. Link an item to a page or article — publishing marks it done.',
    },
    settings: {
      title: 'Settings',
      body: 'Site name, appearance, and permissions live here. This tour is not the first-run setup wizard.',
    },
  },
};
