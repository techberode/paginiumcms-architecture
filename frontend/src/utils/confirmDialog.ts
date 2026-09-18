import type { ConfirmOptions } from '../context/ConfirmContext';

type ConfirmImpl = (options: ConfirmOptions) => Promise<boolean>;

let impl: ConfirmImpl | null = null;

export function setConfirmDialogImplementation(fn: ConfirmImpl | null): void {
  impl = fn;
}

/** Callable outside React (e.g. hooks) once ConfirmProvider mounted. */
export async function confirmDialog(options: ConfirmOptions): Promise<boolean> {
  if (impl) {
    return impl(options);
  }
  return window.confirm(options.message);
}

export async function confirmDialogDestructive(
  message: string,
  title: string
): Promise<boolean> {
  return confirmDialog({ title, message, variant: 'destructive' });
}
