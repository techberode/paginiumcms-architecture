// frontend/src/validation/mapApiErrors.ts
import type { UseFormSetError, FieldValues, Path } from 'react-hook-form';
import type { ApiResponse } from '../api/client';

/** Map backend 422 `errors` onto react-hook-form field errors. */
export function applyApiValidationErrors<T extends FieldValues>(
  response: ApiResponse,
  setError: UseFormSetError<T>
): boolean {
  if (!response.errors) {
    return false;
  }

  for (const [field, messages] of Object.entries(response.errors)) {
    const message = Array.isArray(messages) ? messages[0] : String(messages);
    setError(field as Path<T>, { type: 'server', message });
  }

  return true;
}
