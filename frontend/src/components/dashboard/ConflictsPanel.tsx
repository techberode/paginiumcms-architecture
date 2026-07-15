// frontend/src/components/dashboard/ConflictsPanel.tsx
import React from 'react';
import { Link } from 'react-router-dom';
import { ConflictRecord } from '../../api/conflicts';

interface ConflictsPanelProps {
  conflicts: ConflictRecord[];
  totalCount: number;
  loading?: boolean;
}

export const ConflictsPanel: React.FC<ConflictsPanelProps> = ({ conflicts, totalCount, loading }) => {
  return (
    <div className="card">
      <div className="card-body">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Recent conflicts</h2>
          <span className="text-sm text-gray-500">{totalCount} logged</span>
        </div>

        {loading ? (
          <div className="flex justify-center py-6">
            <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600" />
          </div>
        ) : conflicts.length === 0 ? (
          <p className="text-sm text-gray-500 dark:text-gray-400">No content conflicts recorded.</p>
        ) : (
          <ul className="space-y-2">
            {conflicts.map((conflict, index) => (
              <li
                key={`${conflict.resourceId}-${conflict.occurredAt}-${index}`}
                className="p-3 rounded-lg bg-gray-50 dark:bg-gray-900/40"
              >
                <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                  {conflict.resourceId}
                </p>
                <p className="text-xs text-gray-500">
                  {conflict.userName} · {new Date(conflict.occurredAt * 1000).toLocaleString()}
                </p>
              </li>
            ))}
          </ul>
        )}

        <Link to="/audit" className="inline-block mt-3 text-sm text-indigo-600 hover:underline">
          Open audit trail
        </Link>
      </div>
    </div>
  );
};

export default ConflictsPanel;
