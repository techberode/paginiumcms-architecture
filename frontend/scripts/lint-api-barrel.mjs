#!/usr/bin/env node
/**
 * Wave 5e / It.17 MVP — ensures every frontend API module is exported from api/index.ts
 * and every *Api object is registered on the `api` barrel namespace.
 */
import { readFileSync, readdirSync } from 'node:fs';
import { join } from 'node:path';

const apiDir = join(process.cwd(), 'src', 'api');
const indexPath = join(apiDir, 'index.ts');
const indexSource = readFileSync(indexPath, 'utf8');

const EXCLUDED = new Set([
  'client.ts',
  'types.ts',
  'index.ts',
  'queryKeys.ts',
]);

const modules = readdirSync(apiDir)
  .filter((name) => name.endsWith('.ts') && !name.endsWith('.test.ts') && !EXCLUDED.has(name))
  .map((name) => name.replace(/\.ts$/, ''))
  .sort();

const missingExports = modules.filter((mod) => !indexSource.includes(`'./${mod}'`));

const apiObjectMatch = indexSource.match(/export const api = \{([\s\S]*?)\};/);
if (!apiObjectMatch) {
  console.error('lint-api-barrel: export const api = { ... } not found in index.ts');
  process.exit(1);
}

const apiObjectBody = apiObjectMatch[1];
const registeredKeys = [...apiObjectBody.matchAll(/^\s+(\w+):/gm)].map((m) => m[1]);

const missingApiObjects = [];
for (const mod of modules) {
  const filePath = join(apiDir, `${mod}.ts`);
  const source = readFileSync(filePath, 'utf8');
  const apiExport = source.match(/export const (\w+Api)\s*=/);
  if (!apiExport) {
    continue;
  }

  const exportName = apiExport[1];
  const key = exportName.replace(/Api$/, '');
  const camelKey = key.charAt(0).toLowerCase() + key.slice(1);

  if (!registeredKeys.includes(camelKey)) {
    missingApiObjects.push({ module: mod, exportName, expectedKey: camelKey });
  }
}

let failed = false;

if (missingExports.length > 0) {
  failed = true;
  console.error('lint-api-barrel: missing export * from in index.ts:');
  for (const mod of missingExports) {
    console.error(`  - ${mod}.ts → add: export * from './${mod}';`);
  }
}

if (missingApiObjects.length > 0) {
  failed = true;
  console.error('lint-api-barrel: missing keys on export const api = { ... }:');
  for (const item of missingApiObjects) {
    console.error(`  - ${item.module}.ts (${item.exportName}) → api.${item.expectedKey}`);
  }
}

if (failed) {
  process.exit(1);
}

console.log(`lint-api-barrel: OK — ${modules.length} modules, ${registeredKeys.length} api.* clients`);
