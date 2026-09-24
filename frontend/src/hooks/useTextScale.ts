import { useCallback, useEffect, useState } from 'react';
import {
  applyDocumentTextScale,
  clampTextScalePercent,
  readStoredTextScale,
  TEXT_SCALE_MAX,
  TEXT_SCALE_MIN,
  TEXT_SCALE_STEP,
  writeStoredTextScale,
  type TextScaleScope,
} from '../utils/textScale';

export function useTextScale(scope: TextScaleScope, controlEnabled: boolean): {
  active: boolean;
  percent: number;
  increase: () => void;
  decrease: () => void;
  reset: () => void;
  toggle: () => void;
} {
  const [state, setState] = useState(() => readStoredTextScale(scope));

  const sync = useCallback(
    (active: boolean, percent: number) => {
      const clamped = clampTextScalePercent(percent);
      const next = { active: active && controlEnabled, percent: clamped };
      setState(next);
      writeStoredTextScale(scope, next.active, clamped);
      applyDocumentTextScale(scope, next.active, clamped);
    },
    [controlEnabled, scope]
  );

  useEffect(() => {
    const stored = readStoredTextScale(scope);
    sync(stored.active, stored.percent);
  }, [scope, sync]);

  useEffect(() => {
    if (!controlEnabled) {
      applyDocumentTextScale(scope, false, 100);
      return;
    }
    applyDocumentTextScale(scope, state.active, state.percent);
  }, [controlEnabled, scope, state.active, state.percent]);

  const increase = useCallback(() => {
    const base = state.active ? state.percent : 100;
    sync(true, Math.min(TEXT_SCALE_MAX, base + TEXT_SCALE_STEP));
  }, [state.active, state.percent, sync]);

  const decrease = useCallback(() => {
    const base = state.active ? state.percent : 100;
    const next = Math.max(TEXT_SCALE_MIN, base - TEXT_SCALE_STEP);
    if (next <= TEXT_SCALE_MIN) {
      sync(false, 100);
      return;
    }
    sync(true, next);
  }, [state.active, state.percent, sync]);

  const reset = useCallback(() => {
    sync(false, 100);
  }, [sync]);

  const toggle = useCallback(() => {
    if (state.active) {
      sync(false, 100);
      return;
    }
    sync(true, state.percent === 100 ? 110 : state.percent);
  }, [state.active, state.percent, sync]);

  return {
    active: state.active && controlEnabled,
    percent: state.active ? state.percent : 100,
    increase,
    decrease,
    reset,
    toggle,
  };
}
