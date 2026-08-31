import { cp, mkdir } from 'node:fs/promises';
import { resolve } from 'node:path';

const projectRoot = resolve(new URL('..', import.meta.url).pathname);
const outputDirectory = resolve(projectRoot, 'dist');

await mkdir(outputDirectory, { recursive: true });

// PHP wird unverändert in das fertige Paket übernommen. Der Browser-Code unter
// /src bleibt zusätzlich erhalten, weil serverseitig gerenderte PHP-Seiten ihn
// direkt referenzieren; die statischen HTML-Seiten werden weiterhin von Vite gebaut.
await Promise.all([
  cp(resolve(projectRoot, 'server/public'), outputDirectory, { recursive: true, force: true }),
  cp(resolve(projectRoot, 'src'), resolve(outputDirectory, 'src'), { recursive: true, force: true }),
]);

console.log('PHP-Backend und Browser-Quellen wurden nach dist/ übernommen.');
