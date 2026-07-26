// frontend/src/components/auth/TotpCodeInput.tsx
import React, { useCallback, useRef } from 'react';
import { useI18n } from '../../context/I18nContext';

interface TotpCodeInputProps {
  value: string;
  onChange: (value: string) => void;
  disabled?: boolean;
  autoFocus?: boolean;
}

const DIGIT_COUNT = 6;

export const TotpCodeInput: React.FC<TotpCodeInputProps> = ({
  value,
  onChange,
  disabled = false,
  autoFocus = true,
}) => {
  const { t } = useI18n();
  const inputsRef = useRef<Array<HTMLInputElement | null>>([]);
  const digits = value.padEnd(DIGIT_COUNT, ' ').slice(0, DIGIT_COUNT).split('');

  const emit = useCallback(
    (nextDigits: string[]) => {
      onChange(nextDigits.join('').replace(/\s/g, '').slice(0, DIGIT_COUNT));
    },
    [onChange]
  );

  const focusIndex = (index: number) => {
    const input = inputsRef.current[index];
    input?.focus();
    input?.select();
  };

  const handleChange = (index: number, raw: string) => {
    const cleaned = raw.replace(/\D/g, '');
    if (cleaned.length > 1) {
      const pasted = cleaned.slice(0, DIGIT_COUNT).split('');
      const next = [...digits.map((d) => (d === ' ' ? '' : d))];
      pasted.forEach((char, offset) => {
        if (index + offset < DIGIT_COUNT) {
          next[index + offset] = char;
        }
      });
      emit(next);
      focusIndex(Math.min(index + pasted.length, DIGIT_COUNT - 1));
      return;
    }

    const next = [...digits.map((d) => (d === ' ' ? '' : d))];
    next[index] = cleaned;
    emit(next);
    if (cleaned && index < DIGIT_COUNT - 1) {
      focusIndex(index + 1);
    }
  };

  const handleKeyDown = (index: number, event: React.KeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'Backspace' && !digits[index]?.trim() && index > 0) {
      focusIndex(index - 1);
    }
    if (event.key === 'ArrowLeft' && index > 0) {
      focusIndex(index - 1);
    }
    if (event.key === 'ArrowRight' && index < DIGIT_COUNT - 1) {
      focusIndex(index + 1);
    }
  };

  return (
    <div className="flex justify-center gap-2 sm:gap-3">
      {digits.map((digit, index) => (
        <input
          key={index}
          ref={(el) => {
            inputsRef.current[index] = el;
          }}
          type="text"
          inputMode="numeric"
          pattern="[0-9]*"
          maxLength={1}
          disabled={disabled}
          autoFocus={autoFocus && index === 0}
          value={digit.trim()}
          onChange={(event) => handleChange(index, event.target.value)}
          onKeyDown={(event) => handleKeyDown(index, event)}
          className={`w-11 h-14 sm:w-12 sm:h-16 rounded-xl border-2 text-center text-2xl font-black font-mono transition-all duration-200
            bg-theme-surface-elevated text-theme-text
            focus:outline-none focus:border-theme-primary focus:ring-4 focus:ring-theme-primary/20 focus:scale-105
            ${digit.trim() ? 'border-theme-primary shadow-md shadow-theme-primary/15' : 'border-theme-border'}
          `}
          aria-label={t('public.auth.totp.digitAria', { index: index + 1 })}
        />
      ))}
    </div>
  );
};

export default TotpCodeInput;
