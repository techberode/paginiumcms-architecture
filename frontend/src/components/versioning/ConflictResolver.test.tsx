// frontend/src/components/versioning/ConflictResolver.test.tsx
// Komponentové testy ConflictResolver (Iterácia 3).
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { ConflictResolver } from './ConflictResolver';

const base = 'a\nb\nc';
const mine = 'a\nMINE\nc';
const theirs = 'a\nTHEIRS\nc';

describe('ConflictResolver', () => {
  it('zobrazí počet konfliktov a obe verzie', () => {
    render(<ConflictResolver base={base} mine={mine} theirs={theirs} onResolve={vi.fn()} onCancel={vi.fn()} />);

    expect(screen.getByText('1 konflikt')).toBeInTheDocument();
    expect(screen.getByText('MINE')).toBeInTheDocument();
    expect(screen.getByText('THEIRS')).toBeInTheDocument();
  });

  it('predvolene vyrieši voľbou "moja verzia"', () => {
    const onResolve = vi.fn();
    render(<ConflictResolver base={base} mine={mine} theirs={theirs} onResolve={onResolve} onCancel={vi.fn()} />);

    fireEvent.click(screen.getByText('Použiť riešenie a uložiť'));

    expect(onResolve).toHaveBeenCalledWith('a\nMINE\nc');
  });

  it('po voľbe "serverová" vráti serverový obsah', () => {
    const onResolve = vi.fn();
    render(<ConflictResolver base={base} mine={mine} theirs={theirs} onResolve={onResolve} onCancel={vi.fn()} />);

    fireEvent.click(screen.getByText('Serverová'));
    fireEvent.click(screen.getByText('Použiť riešenie a uložiť'));

    expect(onResolve).toHaveBeenCalledWith('a\nTHEIRS\nc');
  });

  it('zrušenie zavolá onCancel', () => {
    const onCancel = vi.fn();
    render(<ConflictResolver base={base} mine={mine} theirs={theirs} onResolve={vi.fn()} onCancel={onCancel} />);

    fireEvent.click(screen.getByText('Zrušiť'));

    expect(onCancel).toHaveBeenCalled();
  });
});
