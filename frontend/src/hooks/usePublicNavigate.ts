import { useCallback } from 'react';
import { useNavigate, type NavigateFunction } from 'react-router-dom';
import { useSettingsContext } from '../context/SettingsContext';
import { navigateWithViewTransition } from '../navigation/viewTransitionNavigate';

/**
 * Public SPA navigation with optional View Transitions API (Experience B3).
 */
export function usePublicNavigate(): NavigateFunction {
  const navigate = useNavigate();
  const { settings } = useSettingsContext();
  const viewTransitionsEnabled = settings.layout?.viewTransitionsEnabled !== false;

  return useCallback(
    ((to, options) => {
      if (typeof to === 'number') {
        navigate(to);
        return;
      }

      if (typeof to !== 'string') {
        navigate(to, options);
        return;
      }

      navigateWithViewTransition(navigate, to, options, viewTransitionsEnabled);
    }) as NavigateFunction,
    [navigate, viewTransitionsEnabled]
  );
}
