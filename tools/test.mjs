/**
 * نگاه مدیا — اجرای تست‌ها روی PHP واقعی (WASM)
 *
 * اجرا:  cd tools && npm install && node test.mjs
 *
 * کل پروژه را در یک محیط PHP ۸.۳ بارگذاری می‌کند، پایگاه داده تست را
 * از روی database/schema.sql و database/seed.sql می‌سازد و سپس
 * tests/run.php را اجرا می‌کند.
 */
import { readdirSync, readFileSync, statSync, mkdirSync } from 'node:fs';
import { join, relative, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime } from '@php-wasm/node';

const here = dirname(fileURLToPath(import.meta.url));
const root = join(here, '..');
const remote = '/app';

const skipDirs = new Set(['.git', 'node_modules', 'vendor', 'tools', '.github']);
const extensions = new Set(['.php', '.sql', '.css', '.js', '.htaccess', '.example', '.txt']);

function walk(dir, out = []) {
  for (const entry of readdirSync(dir)) {
    if (skipDirs.has(entry)) continue;
    const full = join(dir, entry);
    const stat = statSync(full);
    if (stat.isDirectory()) walk(full, out);
    else out.push(full);
  }
  return out;
}

const files = walk(root);
const php = new PHP(await loadNodeRuntime('8.3', { emscriptenOptions: { processId: 8811 } }));

for (const file of files) {
  const target = remote + '/' + relative(root, file).split('\\').join('/');
  php.mkdirTree(dirname(target));
  php.writeFile(target, readFileSync(file));
}
for (const dir of ['storage/logs', 'storage/cache', 'public/uploads', 'public/uploads/brands', 'public/uploads/works', 'public/uploads/settings']) {
  php.mkdirTree(`${remote}/${dir}`);
}

console.log(`${files.length} فایل در محیط PHP بارگذاری شد — اجرای تست‌ها…\n`);

const result = await php.run({
  code: `<?php chdir('${remote}'); require '${remote}/tests/run.php';`,
});

process.stdout.write(result.text);
if (result.errors) {
  process.stdout.write('\n--- خطاهای PHP ---\n' + result.errors);
}

process.exit(result.exitCode ?? 0);
