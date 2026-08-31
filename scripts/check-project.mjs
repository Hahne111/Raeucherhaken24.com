import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { dirname, extname, join, relative, resolve } from 'node:path';

const projectRoot = resolve(new URL('..', import.meta.url).pathname);
const ignoredDirectories = new Set(['.git', 'dist', 'docs', 'node_modules']);
const failures = [];

function walk(directory) {
  return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    if (entry.isDirectory() && ignoredDirectories.has(entry.name)) return [];
    const path = join(directory, entry.name);
    return entry.isDirectory() ? walk(path) : [path];
  });
}

function report(file, message) {
  failures.push(`${relative(projectRoot, file)}: ${message}`);
}

function localTargetExists(file, reference) {
  const cleanReference = reference.split('#')[0].split('?')[0];
  if (!cleanReference) return true;

  const candidates = cleanReference.startsWith('/')
    ? [
        resolve(projectRoot, `.${cleanReference}`),
        resolve(projectRoot, 'public', `.${cleanReference}`),
        resolve(projectRoot, 'server/public', `.${cleanReference}`),
      ]
    : [
        resolve(dirname(file), cleanReference),
        resolve(projectRoot, cleanReference),
        resolve(projectRoot, 'public', cleanReference),
        resolve(projectRoot, 'server/public', cleanReference),
      ];

  return candidates.some((candidate) => existsSync(candidate));
}

const files = walk(projectRoot);
const htmlFiles = files.filter((file) => extname(file) === '.html');

for (const file of htmlFiles) {
  const html = readFileSync(file, 'utf8');
  if (/<style(?:\s|>)/i.test(html)) report(file, 'enthält noch einen Inline-Styleblock');
  if (/<script(?![^>]*\bsrc=)(?![^>]*application\/ld\+json)/i.test(html)) report(file, 'enthält noch ein Inline-Skript');
  if (/\sstyle\s*=/i.test(html)) report(file, 'enthält noch ein style-Attribut');
  if (/\son(?:click|input|submit|keydown|keyup|change|load|error)\s*=/i.test(html)) report(file, 'enthält noch einen Inline-Eventhandler');

  for (const match of html.matchAll(/\b(?:href|src)\s*=\s*["']([^"']+)["']/gi)) {
    const reference = match[1];
    if (/^(?:https?:|mailto:|tel:|data:|javascript:|#|\/\/)/i.test(reference)) continue;
    if (!localTargetExists(file, reference)) report(file, `Verweis nicht gefunden: ${reference}`);
  }
}

const scriptFiles = files.filter((file) => extname(file) === '.js' && !file.endsWith('vite.config.js'));
for (const file of scriptFiles) {
  try {
    execFileSync(process.execPath, ['--check', file], { stdio: 'pipe' });
  } catch (error) {
    report(file, `JavaScript-Syntaxfehler: ${String(error.stderr || error.message).trim()}`);
  }
}

const requiredFiles = [
  'index.html',
  'package.json',
  'vite.config.js',
  '.devcontainer/devcontainer.json',
  'src/scripts/app-v12.js',
  'src/styles/style-v13.css',
  'server/public/shop-products.php',
];

for (const file of requiredFiles) {
  if (!existsSync(resolve(projectRoot, file))) failures.push(`${file}: erforderliche Datei fehlt`);
}

if (failures.length) {
  console.error(`Projektprüfung fehlgeschlagen (${failures.length}):`);
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log(`${htmlFiles.length} HTML-Dateien, ${scriptFiles.length} Skripte und lokale Verweise geprüft.`);
