// frontend/src/utils/merge3.test.ts
// Testy 3-way merge algoritmu (Iterácia 3).
import { describe, it, expect } from 'vitest';
import { merge3, assembleMerged } from './merge3';

describe('merge3 – automatické zlúčenie', () => {
  it('zlúči nezávislé zmeny v rôznych riadkoch bez konfliktu', () => {
    const base = 'a\nb\nc\nd\ne';
    const mine = 'a\nB2\nc\nd\ne';
    const theirs = 'a\nb\nc\nD2\ne';

    const r = merge3(mine, base, theirs);

    expect(r.clean).toBe(true);
    expect(assembleMerged(r, {})).toBe('a\nB2\nc\nD2\ne');
  });

  it('identickú zmenu z oboch strán nepovažuje za konflikt', () => {
    const base = 'x\ny\nz';
    const same = 'x\nYY\nz';

    const r = merge3(same, base, same);

    expect(r.clean).toBe(true);
    expect(assembleMerged(r, {})).toBe('x\nYY\nz');
  });

  it('pridanie riadku len na jednej strane sa premietne', () => {
    const base = 'l1\nl2';
    const mine = 'l1\nl2';
    const theirs = 'l1\nl2\nl3';

    const r = merge3(mine, base, theirs);

    expect(r.clean).toBe(true);
    expect(assembleMerged(r, {})).toBe('l1\nl2\nl3');
  });

  it('identický vstup nič nemení', () => {
    const base = 'same\ntext';
    const r = merge3(base, base, base);

    expect(r.clean).toBe(true);
    expect(assembleMerged(r, {})).toBe('same\ntext');
  });
});

describe('merge3 – konflikty', () => {
  it('deteguje konflikt keď obaja menia ten istý riadok inak', () => {
    const base = 'a\nb\nc';
    const mine = 'a\nMINE\nc';
    const theirs = 'a\nTHEIRS\nc';

    const r = merge3(mine, base, theirs);

    expect(r.clean).toBe(false);
    expect(r.conflictCount).toBe(1);
  });

  it('prázdny base + rôzny obsah = konflikt', () => {
    const r = merge3('moje', '', 'ich');
    expect(r.conflictCount).toBe(1);
  });
});

describe('assembleMerged – voľby riešenia', () => {
  const base = 'a\nb\nc';
  const mine = 'a\nMINE\nc';
  const theirs = 'a\nTHEIRS\nc';

  it('voľba mine', () => {
    const r = merge3(mine, base, theirs);
    expect(assembleMerged(r, { 0: 'mine' })).toBe('a\nMINE\nc');
  });

  it('voľba theirs', () => {
    const r = merge3(mine, base, theirs);
    expect(assembleMerged(r, { 0: 'theirs' })).toBe('a\nTHEIRS\nc');
  });

  it('voľba both (moja → server)', () => {
    const r = merge3(mine, base, theirs);
    expect(assembleMerged(r, { 0: 'both-mt' })).toBe('a\nMINE\nTHEIRS\nc');
  });

  it('voľba both (server → moja)', () => {
    const r = merge3(mine, base, theirs);
    expect(assembleMerged(r, { 0: 'both-tm' })).toBe('a\nTHEIRS\nMINE\nc');
  });

  it('ručná úprava má prednosť pred voľbou', () => {
    const r = merge3(mine, base, theirs);
    expect(assembleMerged(r, { 0: 'mine' }, { 0: 'HAND' })).toBe('a\nHAND\nc');
  });
});
