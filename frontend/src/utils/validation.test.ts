// frontend/src/utils/validation.test.ts
// === Testy zdieľanej validácie (Iterácia 4) ===
// Musia zodpovedať backendovému ValidatorTest.php – rovnaké pravidlá, rovnaká sémantika.
import { describe, it, expect } from 'vitest';
import { validate, firstError, validatePasswordPolicy } from './validation';

describe('validate', () => {
  it('prejde pri platných dátach', () => {
    const result = validate(
      { name: 'Ahoj', count: '5', active: 'true' },
      { name: ['required', 'string', 'min:2', 'max:10'], count: ['required', 'int', 'min:1', 'max:10'], active: ['bool'] }
    );
    expect(result.valid).toBe(true);
    expect(result.errors).toEqual({});
  });

  it('hlási povinné pole', () => {
    const result = validate({ name: '' }, { name: ['required', 'string'] });
    expect(result.valid).toBe(false);
    expect(result.errors.name).toBeDefined();
  });

  it('preskočí nepovinné prázdne pole', () => {
    const result = validate({ note: '' }, { note: ['string', 'max:10'] });
    expect(result.valid).toBe(true);
  });

  it('kontroluje dĺžku reťazca', () => {
    expect(validate({ x: 'a' }, { x: ['string', 'min:2'] }).valid).toBe(false);
    expect(validate({ x: 'abcdef' }, { x: ['string', 'max:3'] }).valid).toBe(false);
  });

  it('kontroluje číselné hranice', () => {
    expect(validate({ n: '0' }, { n: ['int', 'min:1'] }).valid).toBe(false);
    expect(validate({ n: '101' }, { n: ['int', 'max:100'] }).valid).toBe(false);
    expect(validate({ n: '50' }, { n: ['int', 'min:1', 'max:100'] }).valid).toBe(true);
  });

  it('validuje email a url', () => {
    expect(validate({ e: 'zle' }, { e: ['email'] }).valid).toBe(false);
    expect(validate({ e: 'a@b.sk' }, { e: ['email'] }).valid).toBe(true);
    expect(validate({ u: 'zle' }, { u: ['url'] }).valid).toBe(false);
    expect(validate({ u: 'https://example.com' }, { u: ['url'] }).valid).toBe(true);
  });

  it('validuje pravidlo in', () => {
    expect(validate({ lang: 'de' }, { lang: ['in:sk,en'] }).valid).toBe(false);
    expect(validate({ lang: 'sk' }, { lang: ['in:sk,en'] }).valid).toBe(true);
  });

  it('zozbiera chyby z viacerých polí', () => {
    const result = validate({ a: '', b: 'x' }, { a: ['required'], b: ['int'] });
    expect(Object.keys(result.errors)).toHaveLength(2);
  });
});

describe('firstError', () => {
  it('vráti prvú správu poľa alebo null', () => {
    const { errors } = validate({ name: '' }, { name: ['required'] });
    expect(firstError(errors, 'name')).toContain('povinné');
    expect(firstError(errors, 'other')).toBeNull();
  });
});

describe('validatePasswordPolicy', () => {
  it('akceptuje silné heslo', () => {
    expect(validatePasswordPolicy('Abcdef1!')).toEqual([]);
  });

  it('odmietne slabé heslo', () => {
    expect(validatePasswordPolicy('weak').length).toBeGreaterThan(0);
  });
});
