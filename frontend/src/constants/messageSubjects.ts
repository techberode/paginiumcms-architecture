// frontend/src/constants/messageSubjects.ts
export const MESSAGE_SUBJECT_PRESETS = [
  'Všeobecný dotaz',
  'Technická podpora',
  'Obchodné informácie',
  'Spolupráca',
] as const;

export type MessageSubjectPreset = (typeof MESSAGE_SUBJECT_PRESETS)[number];

export const MESSAGE_PRIORITIES = ['low', 'normal', 'high', 'urgent'] as const;

export type MessagePriority = (typeof MESSAGE_PRIORITIES)[number];

export const MESSAGE_PRIORITY_LABELS: Record<MessagePriority, string> = {
  low: 'Nízka',
  normal: 'Normálna',
  high: 'Vysoká',
  urgent: 'Urgentná',
};

export const messagePriorityWeight = (priority: string): number => {
  switch (priority) {
    case 'urgent':
      return 4;
    case 'high':
      return 3;
    case 'low':
      return 1;
    default:
      return 2;
  }
};
