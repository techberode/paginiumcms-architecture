// frontend/src/test/userEvent.ts
import userEvent from '@testing-library/user-event';

/** user-event bez simulovaného oneskorenia medzi stlačeniami – rýchlejšie testy. */
export const fastUser = userEvent.setup({ delay: null });
