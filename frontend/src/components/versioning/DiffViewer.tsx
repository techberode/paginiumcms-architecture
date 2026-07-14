// frontend/src/components/versioning/DiffViewer.tsx
// === Komponent: DiffViewer (Iterácia 2) ===
// Zobrazuje porovnanie dvoch verzií obsahu vedľa seba (side-by-side).
// Dáta načítava z compare API (/api/admin/versions/compare) alebo ich prijme priamo.
import React, { useEffect, useMemo, useState } from 'react';
import { Loader2, AlertTriangle, Plus, Minus, Pencil } from 'lucide-react';
import { compareVersions, type DiffLine, type DiffResult } from '../../api/versions';

interface DiffViewerProps {
  contentId?: string;
  version1?: number;
  version2?: number;
  /** Priamo dodaný diff (napr. z konfliktu) namiesto načítania z API. */
  diff?: DiffResult;
  /** Popisky pre ľavý/pravý stĺpec. */
  leftLabel?: string;
  rightLabel?: string;
}

type Side = 'left' | 'right';

/**
 * Rozloží riadky diffu do dvoch stĺpcov (staré vľavo, nové vpravo).
 */
function toSide(line: DiffLine, side: Side): { text: string; kind: DiffLine['type'] | 'empty'; lineNo: number | null } {
  switch (line.type) {
    case 'unchanged':
      return { text: line.content ?? '', kind: 'unchanged', lineNo: side === 'left' ? line.old_line : line.new_line };
    case 'added':
      return side === 'left'
        ? { text: '', kind: 'empty', lineNo: null }
        : { text: line.content ?? '', kind: 'added', lineNo: line.new_line };
    case 'removed':
      return side === 'left'
        ? { text: line.content ?? '', kind: 'removed', lineNo: line.old_line }
        : { text: '', kind: 'empty', lineNo: null };
    case 'modified':
      return side === 'left'
        ? { text: line.old_content ?? '', kind: 'removed', lineNo: line.old_line }
        : { text: line.new_content ?? '', kind: 'added', lineNo: line.new_line };
    default:
      return { text: '', kind: 'empty', lineNo: null };
  }
}

const KIND_CLASSES: Record<string, string> = {
  added: 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200',
  removed: 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200',
  unchanged: 'text-slate-700 dark:text-slate-300',
  empty: 'bg-slate-50 dark:bg-slate-800/40',
};

export const DiffViewer: React.FC<DiffViewerProps> = ({
  contentId,
  version1,
  version2,
  diff: providedDiff,
  leftLabel,
  rightLabel,
}) => {
  const [diff, setDiff] = useState<DiffResult | null>(providedDiff ?? null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (providedDiff) {
      setDiff(providedDiff);
      return;
    }
    if (!contentId || version1 == null || version2 == null) {
      return;
    }

    let active = true;
    setLoading(true);
    setError(null);
    compareVersions(contentId, version1, version2)
      .then((result) => {
        if (!active) return;
        if (result) {
          setDiff(result.diff);
        } else {
          setError('Porovnanie sa nepodarilo načítať.');
        }
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, [contentId, version1, version2, providedDiff]);

  const stats = useMemo(
    () =>
      diff
        ? { additions: diff.additions, deletions: diff.deletions, modifications: diff.modifications }
        : { additions: 0, deletions: 0, modifications: 0 },
    [diff]
  );

  // === Blok: Stavy Loading / Error / Empty ===
  if (loading) {
    return (
      <div className="flex items-center justify-center gap-2 py-8 text-slate-500">
        <Loader2 className="h-5 w-5 animate-spin" /> Načítavam porovnanie…
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center gap-2 rounded-md bg-red-50 p-4 text-red-700 dark:bg-red-900/30 dark:text-red-300">
        <AlertTriangle className="h-5 w-5" /> {error}
      </div>
    );
  }

  if (!diff || diff.lines.length === 0) {
    return <div className="py-8 text-center text-slate-500">Žiadne rozdiely na zobrazenie.</div>;
  }

  // === Blok: Side-by-side tabuľka ===
  return (
    <div className="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
      <div className="flex items-center gap-4 border-b border-slate-200 bg-slate-50 px-4 py-2 text-xs dark:border-slate-700 dark:bg-slate-800">
        <span className="inline-flex items-center gap-1 text-emerald-600"><Plus className="h-3 w-3" />{stats.additions}</span>
        <span className="inline-flex items-center gap-1 text-red-600"><Minus className="h-3 w-3" />{stats.deletions}</span>
        <span className="inline-flex items-center gap-1 text-amber-600"><Pencil className="h-3 w-3" />{stats.modifications}</span>
      </div>

      <div className="grid grid-cols-2 font-mono text-xs">
        <div className="border-r border-slate-200 dark:border-slate-700">
          <div className="sticky top-0 bg-slate-100 px-3 py-1 font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
            {leftLabel ?? 'Pôvodná verzia'}
          </div>
          {diff.lines.map((line, idx) => {
            const cell = toSide(line, 'left');
            return (
              <div key={`l-${idx}`} className={`flex gap-2 px-3 py-0.5 ${KIND_CLASSES[cell.kind]}`}>
                <span className="w-8 shrink-0 select-none text-right text-slate-400">{cell.lineNo ?? ''}</span>
                <span className="whitespace-pre-wrap break-all">{cell.text}</span>
              </div>
            );
          })}
        </div>

        <div>
          <div className="sticky top-0 bg-slate-100 px-3 py-1 font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
            {rightLabel ?? 'Nová verzia'}
          </div>
          {diff.lines.map((line, idx) => {
            const cell = toSide(line, 'right');
            return (
              <div key={`r-${idx}`} className={`flex gap-2 px-3 py-0.5 ${KIND_CLASSES[cell.kind]}`}>
                <span className="w-8 shrink-0 select-none text-right text-slate-400">{cell.lineNo ?? ''}</span>
                <span className="whitespace-pre-wrap break-all">{cell.text}</span>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

export default DiffViewer;
