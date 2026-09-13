/**
 * نگاه مدیا — بررسی نحوی (lint) همه فایل‌های PHP با موتور واقعی PHP 8.3 (WASM)
 *
 * اجرا:  cd tools && npm install && node lint.mjs
 *
 * این ابزار فقط برای توسعه است و روی سرور اجرا نمی‌شود.
 */
import { readdirSync, statSync, readFileSync } from 'node:fs';
import { join, relative, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime } from '@php-wasm/node';

const here = dirname(fileURLToPath(import.meta.url));
const root = join(here, '..');

const skipDirs = new Set(['.git', 'node_modules', 'vendor', 'storage', 'tools']);

function walk(dir, out = []) {
  for (const entry of readdirSync(dir)) {
    if (skipDirs.has(entry)) {
      continue;
    }
    const full = join(dir, entry);
    if (statSync(full).isDirectory()) {
      walk(full, out);
    } else if (entry.endsWith('.php')) {
      out.push(full);
    }
  }
  return out;
}

const files = walk(root).sort();
const php = new PHP(await loadNodeRuntime('8.3', { emscriptenOptions: { processId: 7711 } }));

const version = (await php.run({ code: '<?php echo PHP_VERSION;' })).text;
console.log(`PHP ${version} — بررسی ${files.length} فایل\n`);

let failed = 0;
const failures = [];

for (const file of files) {
  const rel = relative(root, file);
  php.writeFile('/tmp/check.php', readFileSync(file, 'utf8'));

  const result = await php.run({
    code: `<?php
$src = file_get_contents('/tmp/check.php');
try {
    token_get_all($src, TOKEN_PARSE);
    echo 'OK';
} catch (ParseError $e) {
    echo 'PARSE_ERROR: ' . $e->getMessage() . ' (line ' . $e->getLine() . ')';
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage();
}
`,
  });

  const out = result.text.trim();
  if (out === 'OK') {
    console.log(`  ✓ ${rel}`);
  } else {
    failed++;
    failures.push(`${rel} → ${out}`);
    console.log(`  ✗ ${rel} → ${out}`);
  }
}

console.log(`\n${files.length - failed}/${files.length} فایل بدون خطای نحوی`);
if (failed > 0) {
  console.log('\nفایل‌های دارای خطا:\n' + failures.join('\n'));
  process.exit(1);
}

// php-wasm حلقه‌ی رویداد node را باز نگه می‌دارد؛ بدون exit صریح، فرآیند
// برای همیشه آویزان می‌ماند و حافظه را رها نمی‌کند.
process.exit(0);
