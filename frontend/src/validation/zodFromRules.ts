// frontend/src/validation/zodFromRules.ts
// Builds a Zod schema from backend ValidationRules (Iteration 21).
import { z } from 'zod';
import type { RuleMap } from '../utils/validation';
import { isValidTimezone } from '../utils/timezones';

function isEmpty(value: unknown): boolean {
  return value === null || value === undefined || value === '';
}

function parseInRule(param: string | undefined): [string, ...string[]] | null {
  const options = (param ?? '').split(',').map((v) => v.trim()).filter(Boolean);
  if (options.length === 0) {
    return null;
  }

  return [options[0], ...options.slice(1)];
}

function wrapOptional(schema: z.ZodTypeAny, required: boolean): z.ZodTypeAny {
  if (required) {
    return schema.refine((v) => !isEmpty(v), { message: 'Pole je povinné.' });
  }

  return z.union([z.literal(''), schema]).optional();
}

function applyStringRules(rules: string[]): z.ZodTypeAny {
  const required = rules.includes('required');

  const inRule = rules.find((r) => r.startsWith('in:'));
  if (inRule) {
    const enumValues = parseInRule(inRule.split(':', 2)[1]);
    if (enumValues) {
      return wrapOptional(
        z.enum(enumValues, { message: 'Neprípustná hodnota.' }),
        required
      );
    }
  }

  let schema = z.string();

  for (const rule of rules) {
    const [name, param] = rule.split(':', 2);

    switch (name) {
      case 'string':
      case 'required':
        break;
      case 'timezone':
        schema = schema.refine((value) => isValidTimezone(String(value)), {
          message: 'Neplatné časové pásmo.',
        });
        break;
      case 'email':
        schema = schema.email('Neplatný e-mail.');
        break;
      case 'url':
        schema = schema.url('Neplatná URL.');
        break;
      case 'min':
        schema = schema.min(Number(param), `Minimálne ${param} znakov.`);
        break;
      case 'max':
        schema = schema.max(Number(param), `Maximálne ${param} znakov.`);
        break;
      default:
        break;
    }
  }

  return wrapOptional(schema, required);
}

function applyIntRules(rules: string[]): z.ZodTypeAny {
  const required = rules.includes('required');
  let schema = z.coerce.number().int('Musí byť celé číslo.');

  for (const rule of rules) {
    if (rule.startsWith('min:')) {
      schema = schema.min(Number(rule.split(':')[1]));
    }
    if (rule.startsWith('max:')) {
      schema = schema.max(Number(rule.split(':')[1]));
    }
  }

  if (required) {
    return schema.refine((v) => v !== null && v !== undefined && !Number.isNaN(v), {
      message: 'Pole je povinné.',
    });
  }

  return schema.optional();
}

function fieldSchema(rules: string[]): z.ZodTypeAny {
  if (rules.includes('bool')) {
    return z.coerce.boolean();
  }

  if (rules.includes('int')) {
    return applyIntRules(rules);
  }

  return applyStringRules(rules);
}

/** Build Zod object schema from backend rule map (same keys as validate()). */
export function zodFromRules(rules: RuleMap): z.ZodObject<Record<string, z.ZodTypeAny>> {
  const shape: Record<string, z.ZodTypeAny> = {};

  for (const [field, fieldRules] of Object.entries(rules)) {
    shape[field] = fieldSchema(fieldRules);
  }

  return z.object(shape);
}
