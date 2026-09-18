import React from 'react';

interface FieldErrorProps {
  message?: string | null;
  id?: string;
}

/** Inline field validation message (react-hook-form / server 422). */
export const FieldError: React.FC<FieldErrorProps> = ({ message, id }) => {
  if (!message) {
    return null;
  }

  return (
    <p id={id} role="alert" className="mt-1 text-xs text-red-600 dark:text-red-400">
      {message}
    </p>
  );
};
