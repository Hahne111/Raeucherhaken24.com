import { readdirSync } from 'node:fs';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';

const projectRoot = new URL('.', import.meta.url).pathname;
const htmlEntries = Object.fromEntries(
  readdirSync(projectRoot)
    .filter((file) => file.endsWith('.html'))
    .map((file) => [file.replace(/\.html$/, ''), resolve(projectRoot, file)]),
);

const phpProxy = {
  target: 'http://127.0.0.1:8000',
  changeOrigin: false,
};

export default defineConfig({
  appType: 'mpa',
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    proxy: {
      '^/orgaboard(?:/|$)': phpProxy,
      '^/(?:uploads|prototype-uploads)(?:/|$)': phpProxy,
      '^/.*\\.php(?:\\?.*)?$': phpProxy,
    },
  },
  preview: {
    host: '0.0.0.0',
    port: 4173,
    strictPort: true,
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      input: htmlEntries,
    },
  },
});
