import { useConfirmContext } from '../context/ConfirmContext';

export type { ConfirmOptions, ConfirmVariant } from '../context/ConfirmContext';

export function useConfirm() {
  return useConfirmContext().confirm;
}
