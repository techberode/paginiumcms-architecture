// frontend/src/components/CodeEditor/VersionHistory.tsx
import React, { useState, useEffect, useCallback } from 'react';
import { useApi } from '../../hooks/useApi';
import { formatDistanceToNow } from 'date-fns';
import { DiffViewer } from '../versioning/DiffViewer';

interface VersionHistoryProps {
  contentId: string;
  onRestore?: (version: number) => void;
}

export const VersionHistory: React.FC<VersionHistoryProps> = ({ contentId, onRestore }) => {
  const [versions, setVersions] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedVersions, setSelectedVersions] = useState<[number, number] | null>(null);
  const [diffData, setDiffData] = useState<any>(null);
  const [showDiff, setShowDiff] = useState(false);
  const { get, post, delete: del } = useApi();

  const loadHistory = useCallback(async () => {
    setLoading(true);
    try {
      const response = await get(`/api/admin/versions/${contentId}`);
      setVersions(response.data.versions || []);
    } catch (error) {
      console.error('Failed to load version history:', error);
    } finally {
      setLoading(false);
    }
  }, [contentId, get]);

  useEffect(() => {
    void loadHistory();
  }, [loadHistory]);

  const handleRestore = async (version: number) => {
    if (!confirm(`Are you sure you want to restore version ${version}?`)) {
      return;
    }

    try {
      await post('/api/admin/versions/restore', {
        content_id: contentId,
        version: version
      });
      
      onRestore?.(version);
      await loadHistory();
    } catch (error) {
      console.error('Failed to restore version:', error);
      alert('Failed to restore version');
    }
  };

  const handleCompare = async () => {
    if (!selectedVersions) return;
    
    const [v1, v2] = selectedVersions;
    try {
      const response = await get(`/api/admin/versions/compare`, {
        params: {
          content_id: contentId,
          version1: v1,
          version2: v2
        }
      });
      setDiffData(response.data);
      setShowDiff(true);
    } catch (error) {
      console.error('Failed to compare versions:', error);
    }
  };

  const handleCleanup = async () => {
    if (!confirm(`Delete all but last 10 versions of this file?`)) {
      return;
    }

    try {
      await del(`/api/admin/versions/${contentId}?keep=10`);
      await loadHistory();
    } catch (error) {
      console.error('Failed to cleanup versions:', error);
    }
  };

  if (loading) {
    return <div className="flex justify-center p-8">Loading version history...</div>;
  }

  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 className="text-lg font-semibold">Version History</h3>
        <div className="flex gap-2">
          <button
            onClick={handleCleanup}
            className="px-3 py-1 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
          >
            Cleanup Old
          </button>
          <button
            onClick={() => setShowDiff(false)}
            className="px-3 py-1 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
          >
            Close
          </button>
        </div>
      </div>

      <div className="p-4">
        {versions.length === 0 ? (
          <p className="text-gray-500 dark:text-gray-400 text-center py-8">
            No versions found for this file
          </p>
        ) : (
          <>
            {/* Version list */}
            <div className="space-y-2 max-h-96 overflow-y-auto">
              {versions.map((version) => (
                <div
                  key={version.version}
                  className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                >
                  <div className="flex items-center gap-3">
                    <span className="font-mono text-sm font-medium">
                      v{version.version}
                    </span>
                    <span className="text-sm text-gray-600 dark:text-gray-400">
                      {formatDistanceToNow(new Date(version.created_at), { addSuffix: true })}
                    </span>
                    <span className="text-sm text-gray-500 dark:text-gray-400">
                      by {version.created_by || 'unknown'}
                    </span>
                    {version.message && (
                      <span className="text-sm text-gray-600 dark:text-gray-400 italic">
                        "{version.message}"
                      </span>
                    )}
                  </div>
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => {
                        if (selectedVersions) {
                          const [v1, v2] = selectedVersions;
                          if (v1 === version.version) {
                            setSelectedVersions([v2, version.version]);
                          } else {
                            setSelectedVersions([v1, version.version]);
                          }
                        } else {
                          setSelectedVersions([version.version, version.version]);
                        }
                      }}
                      className={`px-2 py-1 text-xs rounded ${
                        selectedVersions?.includes(version.version)
                          ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400'
                          : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600'
                      }`}
                    >
                      Compare
                    </button>
                    <button
                      onClick={() => handleRestore(version.version)}
                      className="px-2 py-1 text-xs bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded hover:bg-green-200 dark:hover:bg-green-800"
                    >
                      Restore
                    </button>
                  </div>
                </div>
              ))}
            </div>

            {/* Compare button */}
            {selectedVersions && selectedVersions[0] !== selectedVersions[1] && (
              <div className="mt-4 flex justify-end">
                <button
                  onClick={handleCompare}
                  className="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"
                >
                  Compare Selected Versions
                </button>
              </div>
            )}

            {/* Diff viewer */}
            {showDiff && diffData && (
              <div className="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                <h4 className="text-sm font-medium mb-2">
                  Comparing v{diffData.version1.number} vs v{diffData.version2.number}
                </h4>
                <DiffViewer diff={diffData.diff} />
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};
