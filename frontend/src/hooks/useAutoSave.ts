// frontend/src/hooks/useAutoSave.ts
// === Hook: useAutoSave (Iterácia 2) ===
// Automaticky ukladá rozpracovaný obsah do konceptu (draft flat-file) na pozadí.
//  - ukladá periodicky podľa content.autoSaveInterval (default 60 s), ak sa obsah zmenil,
//  - pri odchode z editora (unmount / zmena slug) uloží koncept bez straty zmien,
//  - stavy: idle | saving | saved | error,
//  - nič neukladá, kým je obsah prázdny alebo je hook vypnutý (nový záznam).
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { saveDraft, type ContentType, type DraftPayload } from '../api/drafts';
import { useSettings } from './useSettings';

/** Predvolený interval auto-save (s) – ak nastavenia ešte nie sú načítané. */
const DEFAULT_AUTOSAVE_INTERVAL_SEC = 60;

export type AutoSaveStatus = 'idle' | 'saving' | 'saved' | 'error';

type PersistMode = 'ui' | 'silent-leave';

export interface UseAutoSaveOptions {
  type: ContentType;
  slug: string;
  /** Aktuálne rozpracované dáta editora. */
  data: DraftPayload;
  /** Ak false, auto-save je vypnutý (napr. nový neuložený záznam). */
  enabled?: boolean;
  /** Volané po úspešnom tichom uložení pri odchode z editora (toast/indikátor). */
  onLeaveSaved?: () => void;
}

export interface UseAutoSaveResult {
  status: AutoSaveStatus;
  lastSavedAt: number | null;
  /** True, ak sa obsah líši od posledného uloženého stavu na serveri (baseline). */
  isDirty: boolean;
  /** Nastaví baseline po načítaní obsahu alebo úspešnom manuálnom uložení. */
  syncBaseline: () => void;
  /** Vynúti okamžité uloženie konceptu. */
  saveNow: () => Promise<void>;
}

export function useAutoSave({
  type,
  slug,
  data,
  enabled = true,
  onLeaveSaved,
}: UseAutoSaveOptions): UseAutoSaveResult {
  const [status, setStatus] = useState<AutoSaveStatus>('idle');
  const [lastSavedAt, setLastSavedAt] = useState<number | null>(null);
  const [baselineSerialized, setBaselineSerialized] = useState('');
  const { get } = useSettings();

  const intervalSec = Number(get('content.autoSaveInterval', DEFAULT_AUTOSAVE_INTERVAL_SEC)) || DEFAULT_AUTOSAVE_INTERVAL_SEC;
  const intervalMs = Math.max(10, intervalSec) * 1000;

  const dataRef = useRef<DraftPayload>(data);
  const lastSerializedRef = useRef<string>('');
  const activeRef = useRef(true);
  const enabledRef = useRef(enabled);
  const onLeaveSavedRef = useRef(onLeaveSaved);
  const leaveNotifiedRef = useRef(false);

  useEffect(() => {
    dataRef.current = data;
  }, [data]);

  useEffect(() => {
    enabledRef.current = enabled;
  }, [enabled]);

  useEffect(() => {
    onLeaveSavedRef.current = onLeaveSaved;
  }, [onLeaveSaved]);

  useEffect(() => {
    leaveNotifiedRef.current = false;
  }, [slug, type, enabled]);

  const shouldPersist = useCallback((current: DraftPayload, serialized: string): boolean => {
    if (!slug || !enabledRef.current) {
      return false;
    }
    if (current.title.trim() === '' && current.content.trim() === '') {
      return false;
    }
    return serialized !== lastSerializedRef.current;
  }, [slug]);

  const attemptPersist = useCallback(
    async (mode: PersistMode): Promise<boolean> => {
      const current = dataRef.current;
      const serialized = JSON.stringify(current);

      if (!shouldPersist(current, serialized)) {
        return false;
      }

      if (mode === 'ui' && activeRef.current) {
        setStatus('saving');
      }

      const ok = await saveDraft(type, slug, current);

      if (ok) {
        lastSerializedRef.current = serialized;
        if (mode === 'ui' && activeRef.current) {
          setLastSavedAt(Date.now());
          setStatus('saved');
        } else if (mode === 'silent-leave' && !leaveNotifiedRef.current) {
          leaveNotifiedRef.current = true;
          onLeaveSavedRef.current?.();
        }
      } else if (mode === 'ui' && activeRef.current) {
        setStatus('error');
      }

      return ok;
    },
    [shouldPersist, slug, type]
  );

  const persist = useCallback(async () => {
    await attemptPersist('ui');
  }, [attemptPersist]);

  const syncBaseline = useCallback(() => {
    const serialized = JSON.stringify(dataRef.current);
    setBaselineSerialized(serialized);
    lastSerializedRef.current = serialized;
  }, []);

  const isDirty = useMemo(() => {
    if (!enabled || !slug) {
      return false;
    }
    if (data.title.trim() === '' && data.content.trim() === '') {
      return false;
    }
    if (baselineSerialized === '') {
      return false;
    }
    return JSON.stringify(data) !== baselineSerialized;
  }, [baselineSerialized, data, enabled, slug]);

  // === Blok: Periodická slučka ===
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

  // === Blok: Flush pri odchode (iná položka menu, zmena slug, zatvorenie karty) ===
  useEffect(() => {
    return () => {
      void attemptPersist('silent-leave');
    };
  }, [attemptPersist, slug, type]);

  useEffect(() => {
    if (!enabled) {
      return;
    }

    const onPageHide = () => {
      void attemptPersist('silent-leave');
    };

    window.addEventListener('pagehide', onPageHide);
    return () => window.removeEventListener('pagehide', onPageHide);
  }, [attemptPersist, enabled]);

  return { status, lastSavedAt, isDirty, syncBaseline, saveNow: persist };
}

export default useAutoSave;
