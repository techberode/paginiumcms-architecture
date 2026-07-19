// frontend/src/components/backend/DemoManager.tsx
import React, { useEffect, useState } from 'react';
import { FlaskConical, RefreshCw } from 'lucide-react';
import { demoApi, type DemoStatus } from '../../api/demo';
import { useToast } from '../../hooks/useToast';
import { useAuth } from '../../hooks/useAuth';

export const DemoManager: React.FC = () => {
  const [status, setStatus] = useState<DemoStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [resetting, setResetting] = useState(false);
  const toast = useToast();
  const { user } = useAuth();
  const isSuperAdmin = user?.roles?.includes('SUPER_ADMIN') ?? false;

  const load = async () => {
    setLoading(true);
    try {
      setStatus(await demoApi.status());
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, []);

  const handleReset = async () => {
    setResetting(true);
    try {
      const result = await demoApi.reset();
      if (result) {
        toast.success(`Demo reset: ${result.written} súborov`);
        await load();
      } else {
        toast.error('Reset zlyhal (vyžaduje SUPER_ADMIN + DEMO_MODE=true)');
      }
    } finally {
      setResetting(false);
    }
  };

  if (loading) {
    return <div className="p-8 text-slate-500">Načítavam demo stav…</div>;
  }

  return (
    <div className="p-6 space-y-6 max-w-3xl">
      <div className="flex items-center gap-3">
        <FlaskConical className="text-amber-500" />
        <div>
          <h1 className="text-2xl font-black">Demo modul</h1>
          <p className="text-sm text-slate-500">Izolované úložisko pre školenia a sandbox</p>
        </div>
      </div>

      <div className="rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3 text-sm">
        <p>
          <span className="font-bold">DEMO_MODE:</span>{' '}
          {status?.enabled ? 'zapnutý' : 'vypnutý'}
        </p>
        <p>
          <span className="font-bold">Úložisko:</span> {status?.storage_path}
        </p>
        <p>
          <span className="font-bold">Produkčný obsah:</span> {status?.content_path}
        </p>
        <p>
          <span className="font-bold">Súbory v demo:</span> {status?.file_count ?? 0}
        </p>
      </div>

      <button
        type="button"
        disabled={!status?.enabled || !isSuperAdmin || resetting}
        onClick={() => void handleReset()}
        className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500 text-amber-950 font-bold disabled:opacity-40"
      >
        <RefreshCw size={16} /> Reset demo seed
      </button>

      {!status?.enabled && (
        <p className="text-xs text-slate-500">Nastav `DEMO_MODE=true` v prostredí servera.</p>
      )}
    </div>
  );
};

export default DemoManager;
