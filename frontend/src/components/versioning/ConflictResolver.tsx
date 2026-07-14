// frontend/src/components/versioning/ConflictResolver.tsx
// === Komponent: ConflictResolver (Iterácia 3) ===
// Interaktívne riešenie 3-way merge konfliktov na klientovi.
//  - vstup: base (pôvodne načítané), mine (moje úpravy), theirs (serverová verzia z 409),
//  - beží merge3(); stabilné bloky zobrazí ako kontext, konfliktné ako voľbu,
//  - používateľ pre každý konflikt zvolí Moja / Serverová / Obe / Ručná úprava,
//  - výsledok poskladá a odovzdá cez onResolve().
import React, { useMemo, useState } from 'react';
import { AlertTriangle, Check, X } from 'lucide-react';
import { merge3, assembleMerged, type ConflictChoice, type MergeChunk } from '../../utils/merge3';

interface ConflictResolverProps {
  base: string;
  mine: string;
  theirs: string;
  onResolve: (merged: string) => void;
  onCancel: () => void;
}

const CHOICE_LABELS: Array<{ value: ConflictChoice; label: string }> = [
  { value: 'mine', label: 'Moja verzia' },
  { value: 'theirs', label: 'Serverová' },
  { value: 'both-mt', label: 'Obe (moja → server)' },
  { value: 'both-tm', label: 'Obe (server → moja)' },
];

export const ConflictResolver: React.FC<ConflictResolverProps> = ({ base, mine, theirs, onResolve, onCancel }) => {
  const result = useMemo(() => merge3(mine, base, theirs), [mine, base, theirs]);

  const [choices, setChoices] = useState<Record<number, ConflictChoice>>({});
  const [manual, setManual] = useState<Record<number, string>>({});

  const setChoice = (i: number, choice: ConflictChoice): void => {
    setChoices((prev) => ({ ...prev, [i]: choice }));
    // Voľba prepisuje ručnú úpravu.
    setManual((prev) => {
      const next = { ...prev };
      delete next[i];
      return next;
    });
  };

  const setManualText = (i: number, text: string): void => {
    setManual((prev) => ({ ...prev, [i]: text }));
  };

  const preview = useMemo(() => assembleMerged(result, choices, manual), [result, choices, manual]);

  let conflictIndex = -1;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div className="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-800">
        {/* Hlavička */}
        <div className="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-gray-700">
          <div className="flex items-center gap-2">
            <AlertTriangle className="h-5 w-5 text-amber-500" />
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Riešenie konfliktu obsahu</h3>
          </div>
          <span className="text-sm text-gray-500 dark:text-gray-400">
            {result.conflictCount} {result.conflictCount === 1 ? 'konflikt' : 'konfliktov'}
          </span>
        </div>

        {/* Telo – bloky */}
        <div className="flex-1 space-y-3 overflow-y-auto px-5 py-4">
          {result.chunks.map((chunk, idx) => {
            if (chunk.type === 'stable') {
              return <StableBlock key={idx} chunk={chunk} />;
            }
            conflictIndex++;
            const i = conflictIndex;
            const isManual = Object.prototype.hasOwnProperty.call(manual, i);
            const activeChoice = choices[i] ?? 'mine';

            return (
              <div key={idx} className="rounded-md border border-amber-300 dark:border-amber-700">
                <div className="flex flex-wrap items-center gap-2 border-b border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-800 dark:bg-amber-900/30">
                  <span className="text-xs font-semibold text-amber-700 dark:text-amber-300">Konflikt #{i + 1}</span>
                  {CHOICE_LABELS.map((opt) => (
                    <button
                      key={opt.value}
                      onClick={() => setChoice(i, opt.value)}
                      className={`rounded px-2 py-0.5 text-xs ${
                        !isManual && activeChoice === opt.value
                          ? 'bg-indigo-600 text-white'
                          : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
                      }`}
                    >
                      {opt.label}
                    </button>
                  ))}
                  <button
                    onClick={() => setManualText(i, [...chunk.mine, ...chunk.theirs].join('\n'))}
                    className={`rounded px-2 py-0.5 text-xs ${
                      isManual ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
                    }`}
                  >
                    Ručne
                  </button>
                </div>

                <div className="grid grid-cols-1 gap-2 p-3 md:grid-cols-2">
                  <ConflictSide title="Moja verzia" lines={chunk.mine} tone="mine" />
                  <ConflictSide title="Serverová verzia" lines={chunk.theirs} tone="theirs" />
                </div>

                {isManual && (
                  <div className="px-3 pb-3">
                    <label className="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Ručná úprava</label>
                    <textarea
                      value={manual[i]}
                      onChange={(e) => setManualText(i, e.target.value)}
                      className="w-full rounded border border-gray-300 bg-white p-2 font-mono text-xs dark:border-gray-600 dark:bg-gray-900"
                      rows={Math.max(3, chunk.mine.length + chunk.theirs.length)}
                    />
                  </div>
                )}
              </div>
            );
          })}
        </div>

        {/* Náhľad výsledku */}
        <div className="border-t border-gray-200 px-5 py-3 dark:border-gray-700">
          <details>
            <summary className="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
              Náhľad výsledného obsahu
            </summary>
            <pre className="mt-2 max-h-40 overflow-auto whitespace-pre-wrap rounded bg-gray-50 p-2 font-mono text-xs dark:bg-gray-900">
              {preview}
            </pre>
          </details>
        </div>

        {/* Pätička */}
        <div className="flex justify-end gap-2 border-t border-gray-200 px-5 py-3 dark:border-gray-700">
          <button
            onClick={onCancel}
            className="inline-flex items-center gap-1 rounded border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
          >
            <X className="h-4 w-4" /> Zrušiť
          </button>
          <button
            onClick={() => onResolve(preview)}
            className="inline-flex items-center gap-1 rounded bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-700"
          >
            <Check className="h-4 w-4" /> Použiť riešenie a uložiť
          </button>
        </div>
      </div>
    </div>
  );
};

const StableBlock: React.FC<{ chunk: Extract<MergeChunk, { type: 'stable' }> }> = ({ chunk }) => {
  if (chunk.lines.length === 0) {
    return null;
  }
  return (
    <pre className="whitespace-pre-wrap rounded bg-gray-50 px-3 py-1 font-mono text-xs text-gray-500 dark:bg-gray-900 dark:text-gray-400">
      {chunk.lines.join('\n')}
    </pre>
  );
};

const ConflictSide: React.FC<{ title: string; lines: string[]; tone: 'mine' | 'theirs' }> = ({ title, lines, tone }) => (
  <div>
    <div
      className={`mb-1 text-xs font-semibold ${
        tone === 'mine' ? 'text-emerald-600 dark:text-emerald-400' : 'text-blue-600 dark:text-blue-400'
      }`}
    >
      {title}
    </div>
    <pre
      className={`min-h-[2rem] whitespace-pre-wrap rounded p-2 font-mono text-xs ${
        tone === 'mine'
          ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200'
          : 'bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200'
      }`}
    >
      {lines.length > 0 ? lines.join('\n') : '(prázdne)'}
    </pre>
  </div>
);

export default ConflictResolver;
