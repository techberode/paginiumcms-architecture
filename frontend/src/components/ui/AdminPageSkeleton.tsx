import React from 'react';

export const AdminPageSkeleton: React.FC = () => (
  <div className="space-y-6 animate-pulse" aria-hidden="true">
    <div className="h-32 rounded-3xl bg-slate-200 dark:bg-slate-800" />
    <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      {Array.from({ length: 4 }).map((_, index) => (
        <div key={index} className="h-28 rounded-2xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800" />
      ))}
    </div>
    <div className="h-64 rounded-2xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800" />
  </div>
);

export default AdminPageSkeleton;
