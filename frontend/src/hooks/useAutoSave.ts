// frontend/src/hooks/useAutoSave.ts
// === Hook: useAutoSave (Iterácia 2) ===
// Automaticky ukladá rozpracovaný obsah do konceptu (draft flat-file) na pozadí.
//  - ukladá periodicky každých 60 s, AK sa obsah od posledného uloženia zmenil,
//  - stavy: idle | saving | saved | error,
//  - nič neukladá, kým je obsah prázdny alebo je hook vypnutý.
import { useCallback, useEffect, useRef, useState } from 'react';
import { saveDraft, type ContentType, type DraftPayload } from '../api/drafts';
import { useSettings } from './useSettings';

/** Predvolený interval auto-save (s) – ak nastavenia ešte nie sú načítané. */
const DEFAULT_AUTOSAVE_INTERVAL_SEC = 60;

export type AutoSaveStatus = 'idle' | 'saving' | 'saved' | 'error';

export interface UseAutoSaveOptions {
  type: ContentType;
  slug: string;
  /** Aktuálne rozpracované dáta editora. */
  data: DraftPayload;
  /** Ak false, auto-save je vypnutý (napr. počas počiatočného načítania). */
  enabled?: boolean;
}

export interface UseAutoSaveResult {
  status: AutoSaveStatus;
  lastSavedAt: number | null;
  /** Vynúti okamžité uloženie konceptu (napr. tlačidlo "Uložiť koncept"). */
  saveNow: () => Promise<void>;
}

export function useAutoSave({ type, slug, data, enabled = true }: UseAutoSaveOptions): UseAutoSaveResult {
  const [status, setStatus] = useState<AutoSaveStatus>('idle');
  const [lastSavedAt, setLastSavedAt] = useState<number | null>(null);
  const { get } = useSettings();

  const intervalSec = Number(get('content.autoSaveInterval', DEFAULT_AUTOSAVE_INTERVAL_SEC)) || DEFAULT_AUTOSAVE_INTERVAL_SEC;
  const intervalMs = Math.max(10, intervalSec) * 1000;

  // Najčerstvejšie dáta držíme v ref, aby interval callback nepracoval so zastaraným stavom.
  const dataRef = useRef<DraftPayload>(data);
  const lastSerializedRef = useRef<string>('');
  const activeRef = useRef(true);

  useEffect(() => {
    dataRef.current = data;
  }, [data]);

  // === Blok: Uloženie konceptu ===
  const persist = useCallback(async () => {
    const current = dataRef.current;
    const serialized = JSON.stringify(current);

    // Nič neukladáme, ak je obsah prázdny alebo sa od posledného uloženia nezmenil.
    if (!slug || (current.title.trim() === '' && current.content.trim() === '')) {
      return;
    }
    if (serialized === lastSerializedRef.current) {
      return;
    }

    setStatus('saving');
    const ok = await saveDraft(type, slug, current);
    if (!activeRef.current) {
      return;
    }

    if (ok) {
      lastSerializedRef.current = serialized;
      setLastSavedAt(Date.now());
      setStatus('saved');
    } else {
      setStatus('error');
    }
  }, [type, slug]);

  // === Blok: Periodická slučka (60 s) ===
  useEffect(() => {
    activeRef.current = true;
    if (!enabled) {
      return;
    }

    const timer = setInterval(() => {
      void persist();
    }, intervalMs);

    return () => {
      activeRef.current = false;
      clearInterval(timer);
    };
  }, [enabled, persist, intervalMs]);

  return { status, lastSavedAt, saveNow: persist };
}

export default useAutoSave;
