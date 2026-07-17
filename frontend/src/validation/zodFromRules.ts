// frontend/src/validation/zodFromRules.ts
// Builds a Zod schema from backend ValidationRules (Iteration 21).
import { z } from 'zod';
import type { RuleMap } from '../utils/validation';

function isEmpty(value: unknown): boolean {
  return value === null || value === undefined || value === '';
}

function applyStringRules(base: z.ZodTypeAny, rules: string[]): z.ZodTypeAny {
  let schema = base;

  for (const rule of rules) {
    const [name, param] = rule.split(':', 2);

    switch (name) {
      case 'required':
        schema = schema.refine((v) => !isEmpty(v), { message: 'Pole je povinné.' });
        break;
      case 'email':
        schema = z.string().email('Neplatný e-mail.');
        break;
      case 'url':
        schema = z.string().url('Neplatná URL.');
        break;
      case 'min':
        schema = (schema as z.ZodString).min(Number(param), `Minimálne ${param} znakov.`);
        break;
      case 'max':
        schema = (schema as z.ZodString).max(Number(param), `Maximálne ${param} znakov.`);
        break;
      case 'in': {
        const options = (param ?? '').split(',').filter(Boolean);
        if (options.length > 0) {
          schema = z.enum([options[0], ...options.slice(1)] as [string, ...string[]], {
            message: 'Neprípustná hodnota.',
          });
        }
        break;
      }
      default:
        break;
    }
  }

  return schema;
}

function fieldSchema(rules: string[]): z.ZodTypeAny {
  if (rules.includes('bool')) {
    return z.coerce.boolean();
  }

  if (rules.includes('int')) {
    let schema: z.ZodTypeAny = z.coerce.number().int('Musí byť celé číslo.');
    for (const rule of rules) {
      if (rule.startsWith('min:')) {
        schema = (schema as z.ZodNumber).min(Number(rule.split(':')[1]));
      }
      if (rule.startsWith('max:')) {
        schema = (schema as z.ZodNumber).max(Number(rule.split(':')[1]));
      }
    }
    if (rules.includes('required')) {
      schema = schema.refine((v) => v !== null && v !== undefined && v !== '', {
        message: 'Pole je povinné.',
      });
    }
    return schema;
  }

  let schema: z.ZodTypeAny = z.union([z.string(), z.number(), z.boolean()]).optional();

  if (rules.includes('required')) {
    schema = applyStringRules(z.string(), rules);
  } else {
    schema = applyStringRules(z.string().optional(), rules.filter((r) => r !== 'required'));
  }

  return schema;
}

/** Build Zod object schema from backend rule map (same keys as validate()). */
export function zodFromRules(rules: RuleMap): z.ZodObject<Record<string, z.ZodTypeAny>> {
  const shape: Record<string, z.ZodTypeAny> = {};

  for (const [field, fieldRules] of Object.entries(rules)) {
    shape[field] = fieldSchema(fieldRules);
  }

  return z.object(shape);
}
