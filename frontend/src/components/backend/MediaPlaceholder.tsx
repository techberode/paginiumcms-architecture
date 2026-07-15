// frontend/src/components/backend/MediaPlaceholder.tsx
import React from 'react';
import { Image as ImageIcon } from 'lucide-react';

export const MediaPlaceholder: React.FC = () => (
  <div className="card">
    <div className="card-body text-center py-16 space-y-4">
      <div className="mx-auto w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center">
        <ImageIcon className="w-8 h-8 text-indigo-500" />
      </div>
      <h2 className="text-2xl font-black text-slate-900 dark:text-white">Media Library</h2>
      <p className="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
        Backend API <code className="text-indigo-500">/api/media</code> is ready. Frontend manager arrives in Iteration 8.
      </p>
    </div>
  </div>
);

export default MediaPlaceholder;
