export const inboxPriorityBadgeClass = (priority: string): string => {
  switch (priority) {
    case 'urgent':
      return 'badge bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
    case 'high':
      return 'badge bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300';
    case 'low':
      return 'badge bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
    default:
      return 'badge bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300';
  }
};

export const inboxPriorityLabel = (priority: string): string => {
  switch (priority) {
    case 'urgent':
      return 'Urgentná';
    case 'high':
      return 'Vysoká';
    case 'low':
      return 'Nízka';
    default:
      return 'Normálna';
  }
};
