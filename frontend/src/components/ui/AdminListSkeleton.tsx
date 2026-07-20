import React from 'react';

interface AdminListSkeletonProps {
  rows?: number;
}

export const AdminListSkeleton: React.FC<AdminListSkeletonProps> = ({ rows = 6 }) => (
  <div className="space-y-3 animate-pulse" aria-hidden="true">
    <div className="h-10 rounded-xl bg-slate-200 dark:bg-slate-800" />
    {Array.from({ length: rows }).map((_, index) => (
      <div key={index} className="h-16 rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800" />
    ))}
  </div>
);

export default AdminListSkeleton;
