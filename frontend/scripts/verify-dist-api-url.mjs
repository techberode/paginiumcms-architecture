#!/usr/bin/env node
/**
 * Fails the build if VITE_API_URL was baked into dist (e.g. http://localhost:8080).
 * Same-origin deploys must use window.location.origin at runtime.
 */
import { readFileSync, readdirSync } from 'node:fs';
import { join } from 'node:path';

const distAssets = join(process.cwd(), 'dist', 'assets');
const jsFiles = readdirSync(distAssets).filter((f) => f.endsWith('.js'));

if (jsFiles.length === 0) {
  console.error('verify-dist-api-url: no JS files in dist/assets');
  process.exit(1);
}

const badPattern = /return`http:\/\/localhost:8080`\.replace\(\/\\\/\$\/,``\)/;

for (const file of jsFiles) {
  const content = readFileSync(join(distAssets, file), 'utf8');
  if (badPattern.test(content)) {
    console.error(
      `verify-dist-api-url: ${file} bakes VITE_API_URL=http://localhost:8080 into the bundle.`,
    );
    console.error('Rebuild with: npm run build:prod  (or unset VITE_API_URL before vite build)');
    process.exit(1);
  }
}

console.log('verify-dist-api-url: OK — API will use window.location.origin at runtime');
