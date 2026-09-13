#!/usr/bin/env node

/**
 * سرور پیش‌نمایش نگاه مدیا — اجرای واقعی کد PHP سایت داخل PHP-WASM
 *
 * چرا این فایل؟ سایت با PHP + MySQL نوشته شده، ولی در این محیط نه PHP بومی
 * در دسترس است و نه سرور MySQL. به‌جای شبیه‌سازی یا بازنویسی، همان کد بدون
 * هیچ تغییری داخل PHP-WASM اجرا می‌شود و پایگاه داده از روی schema.sql و
 * seed.sql واقعیِ پروژه روی SQLite ساخته می‌شود (دقیقاً همان کاری که
 * tests/helpers/sqlite.php برای تست‌ها می‌کند).
 *
 * این ابزار فقط برای پیش‌نمایش است و بخشی از محصول نهایی نیست.
 *
 * اجرا:  node tools/serve.mjs        (پورت: PORT=8080)
 */

import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime } from '@php-wasm/node';
import { createServer } from 'node:http';
import { randomBytes } from 'node:crypto';
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { dirname, extname, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(HERE, '..');
const REMOTE = '/app';
const HOST = '0.0.0.0';
const PORT = Number(process.env.PORT || 8080);

const SKIP_DIRS = new Set(['.git', 'node_modules', 'vendor', 'tools', '.github', '.cache']);
const SKIP_EXT = new Set(['.sqlite', '.map', '.log']);

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.xml': 'application/xml; charset=utf-8',
  '.txt': 'text/plain; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.webp': 'image/webp',
  '.gif': 'image/gif',
  '.ico': 'image/x-icon',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
  '.pdf': 'application/pdf',
};

// --------------------------------------------------------------- ابزارها

function walk(dir, out = []) {
  for (const entry of readdirSync(dir)) {
    if (SKIP_DIRS.has(entry)) continue;
    const full = join(dir, entry);
    const stat = statSync(full);
    if (stat.isDirectory()) walk(full, out);
    else if (!SKIP_EXT.has(extname(entry))) out.push(full);
  }
  return out;
}

/** تبدیل پارامترهای تکراری و name[] به آرایه، مثل رفتار PHP */
function collect(target, name, value) {
  if (name.endsWith('[]')) {
    const key = name.slice(0, -2);
    (target[key] ||= []).push(value);
    return;
  }
  if (target[name] === undefined) {
    target[name] = value;
  } else if (Array.isArray(target[name])) {
    target[name].push(value);
  } else {
    target[name] = [target[name], value];
  }
}

function parseQuery(searchParams) {
  const out = {};
  for (const [key, value] of searchParams) collect(out, key, value);
  return out;
}

function parseCookies(header) {
  const out = {};
  for (const part of String(header || '').split(';')) {
    const i = part.indexOf('=');
    if (i > 0) out[part.slice(0, i).trim()] = decodeURIComponent(part.slice(i + 1).trim());
  }
  return out;
}

/** پارسر minimal برای multipart/form-data — فرم‌های آپلود پنل به آن نیاز دارند */
function parseMultipart(body, boundary) {
  const fields = {};
  const files = {};
  const marker = Buffer.from('--' + boundary);
  let pos = body.indexOf(marker);
  if (pos === -1) return { fields, files };
  pos += marker.length;

  while (pos < body.length) {
    if (body.subarray(pos, pos + 2).toString() === '--') break;
    pos += 2; // CRLF پس از مرز
    const headEnd = body.indexOf('\r\n\r\n', pos);
    if (headEnd === -1) break;
    const head = body.subarray(pos, headEnd).toString('utf8');
    const from = headEnd + 4;
    const next = body.indexOf(marker, from);
    if (next === -1) break;
    const content = body.subarray(from, next - 2); // CRLF قبل از مرز
    pos = next + marker.length;

    const name = /name="([^"]*)"/.exec(head)?.[1];
    if (!name) continue;
    const filename = /filename="([^"]*)"/.exec(head)?.[1];

    if (filename === undefined) {
      collect(fields, name, content.toString('utf8'));
    } else {
      const type = /content-type:\s*(.+)/i.exec(head)?.[1]?.trim() || 'application/octet-stream';
      collect(files, name, { filename, type, data: Buffer.from(content) });
    }
  }
  return { fields, files };
}

// ---------------------------------------------------------- راه‌اندازی PHP

async function bootPhp() {
  const started = Date.now();
  const php = new PHP(await loadNodeRuntime('8.3', { emscriptenOptions: { processId: 9100 } }));
  console.log(`[boot] PHP-WASM آماده (${Date.now() - started}ms)`);

  const files = walk(ROOT);
  const t0 = Date.now();
  for (const file of files) {
    const target = `${REMOTE}/${relative(ROOT, file).split('\\').join('/')}`;
    php.mkdirTree(dirname(target));
    php.writeFile(target, readFileSync(file));
  }
  for (const dir of [
    'storage/logs',
    'storage/cache',
    'public/uploads',
    'public/uploads/brands',
    'public/uploads/works',
    'public/uploads/settings',
    'public/uploads/posts',
    'public/uploads/pages',
    'public/uploads/general',
    'public/uploads/fonts',
    'public/uploads/team',
  ]) {
    php.mkdirTree(`${REMOTE}/${dir}`);
  }
  console.log(`[boot] ${files.length} فایل کپی شد (${Date.now() - t0}ms)`);

  php.writeFile('/entry.php', readFileSync(join(HERE, 'php-entry.php')));

  // پایگاه داده از روی schema.sql و seed.sql واقعی پروژه ساخته می‌شود
  const env = [
    'APP_NAME="نگاه مدیا"',
    'APP_ENV=local',
    'APP_DEBUG=true',
    // عمداً خالی: تا آدرس‌های مطلق (canonical/og:url) به دامین اصلی اشاره نکنند
    'APP_URL=',
    'APP_TIMEZONE=Asia/Tehran',
    'APP_LOCALE=fa',
    'APP_BASE_PATH=',
    'DB_DRIVER=sqlite',
    `DB_DATABASE=${REMOTE}/storage/negahm.sqlite`,
    `APP_KEY=${randomBytes(32).toString('hex')}`,
    'UPLOAD_MAX_SIZE=8388608',
    'ALLOWED_IMAGE_TYPES=jpg,jpeg,png,webp,gif,svg',
    'ALLOWED_FILE_TYPES=pdf,zip,mp4,webm,woff2,woff,ttf,otf',
    'MAIL_FROM=info@negahm.ir',
    'MAIL_FROM_NAME="نگاه مدیا"',
    'MAIL_NOTIFY=',
  ].join('\n');
  php.writeFile(`${REMOTE}/.env`, env);

  const built = await php.run({
    code: `<?php
require '${REMOTE}/tests/helpers/sqlite.php';
$pdo = build_test_database('${REMOTE}', '${REMOTE}/storage/negahm.sqlite');
foreach (['users','brands','works','services','posts','settings'] as $t) {
  echo $t . '=' . $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn() . ' ';
}`,
  });
  console.log('[boot] پایگاه داده:', built.text.trim() || built.errors || '(خروجی ندارد)');
  if (built.exitCode !== 0) throw new Error('ساخت پایگاه داده ناموفق بود');

  return php;
}

// ------------------------------------------------------------ اجرای درخواست

async function execute(php, payload) {
  php.writeFile('/tmp/req.json', JSON.stringify(payload));
  let response;
  try {
    response = await php.run({ code: "<?php require '/entry.php';" });
  } catch (error) {
    // خطای کشنده‌ی PHP باعث throw می‌شود؛ پاسخ واقعی داخل error.response است
    if (!error.response) throw error;
    response = error.response;
  }
  const bytes = response.bytes ?? new TextEncoder().encode(response.text ?? '');
  return {
    status: response.httpStatusCode || 200,
    headers: response.headers || {},
    body: Buffer.from(bytes),
    error: String(response.errors || ''),
  };
}

function buildPayload(php, req, body) {
  const url = new URL(req.url, 'http://localhost');
  const host = req.headers.host || 'localhost';
  const contentType = String(req.headers['content-type'] || '');

  const server = {
    REQUEST_METHOD: req.method,
    REQUEST_URI: req.url,
    QUERY_STRING: url.search ? url.search.slice(1) : '',
    SCRIPT_NAME: '/index.php',
    SCRIPT_FILENAME: `${REMOTE}/public/index.php`,
    DOCUMENT_ROOT: `${REMOTE}/public`,
    SERVER_NAME: host.split(':')[0],
    SERVER_PORT: String(url.port || 80),
    SERVER_PROTOCOL: 'HTTP/1.1',
    SERVER_SOFTWARE: 'negahm-preview',
    REMOTE_ADDR: req.socket?.remoteAddress || '127.0.0.1',
    HTTP_HOST: host,
    REQUEST_TIME: Math.floor(Date.now() / 1000),
    REQUEST_TIME_FLOAT: Date.now() / 1000,
  };
  for (const [key, value] of Object.entries(req.headers)) {
    if (key === 'content-type' || key === 'content-length') continue;
    server['HTTP_' + key.toUpperCase().replace(/-/g, '_')] = Array.isArray(value)
      ? value.join(', ')
      : String(value);
  }

  const files = {};
  let post = {};

  if (req.method === 'POST' || req.method === 'PUT' || req.method === 'PATCH') {
    if (contentType.includes('multipart/form-data')) {
      const match = /boundary=(?:"([^"]+)"|([^;]+))/i.exec(contentType);
      const parsed = parseMultipart(body, String(match?.[1] || match?.[2] || '').trim());
      post = parsed.fields;
      // فایل‌ها در فایل‌سیستم مجازی نوشته می‌شوند تا $_FILES مسیر واقعی داشته باشد
      let n = 0;
      for (const [field, value] of Object.entries(parsed.files)) {
        for (const item of Array.isArray(value) ? value : [value]) {
          const tmp = `/tmp/upload-${Date.now()}-${n++}`;
          php.writeFile(tmp, item.data);
          collect(files, field, {
            name: item.filename,
            type: item.type,
            tmp_name: tmp,
            error: 0,
            size: item.data.length,
          });
        }
      }
    } else if (contentType.includes('application/json')) {
      try {
        post = JSON.parse(body.toString('utf8')) || {};
      } catch {
        post = {};
      }
    } else {
      post = parseQuery(new URLSearchParams(body.toString('utf8')));
    }
  }

  return {
    server,
    get: parseQuery(url.searchParams),
    post,
    cookie: parseCookies(req.headers.cookie),
    files,
  };
}

// ------------------------------------------------------------- سرو استاتیک

function serveHostFile(res, absPath) {
  try {
    const data = readFileSync(absPath);
    res.writeHead(200, {
      'Content-Type': MIME[extname(absPath).toLowerCase()] || 'application/octet-stream',
      'Content-Length': data.length,
      'Cache-Control': 'no-cache',
    });
    res.end(data);
    return true;
  } catch {
    return false;
  }
}

// ------------------------------------------------------------------ سرور

async function main() {
  const php = await bootPhp();

  // یک نمونه‌ی PHP نمی‌تواند هم‌زمان چند درخواست را اجرا کند → صف متوالی
  let chain = Promise.resolve();
  const enqueue = (fn) => (chain = chain.then(fn, fn));

  const server = createServer(async (req, res) => {
    const url = new URL(req.url, 'http://localhost');
    const path = decodeURIComponent(url.pathname);
    const started = Date.now();

    // فایل‌های استاتیک: assets از دیسک، uploads از فایل‌سیستم PHP
    if (path.startsWith('/assets/')) {
      const abs = resolve(join(ROOT, 'public'), '.' + path);
      if (!abs.startsWith(resolve(ROOT, 'public')) || !serveHostFile(res, abs)) {
        res.writeHead(404).end('not found');
      }
      return;
    }
    if (path === '/favicon.ico') {
      res.writeHead(204).end();
      return;
    }
    if (path.startsWith('/uploads/')) {
      const remote = REMOTE + '/public' + path;
      await enqueue(async () => {
        try {
          if (!php.fileExists(remote)) throw new Error('missing');
          const data = Buffer.from(php.readFileAsBuffer(remote));
          res.writeHead(200, {
            'Content-Type': MIME[extname(path).toLowerCase()] || 'application/octet-stream',
            'Content-Length': data.length,
            'Cache-Control': 'public, max-age=3600',
          });
          res.end(data);
        } catch {
          res.writeHead(404).end('not found');
        }
      });
      return;
    }

    const chunks = [];
    for await (const chunk of req) chunks.push(chunk);
    const body = Buffer.concat(chunks);

    await enqueue(async () => {
      try {
        const payload = buildPayload(php, req, body);
        const out = await execute(php, payload);
        // خطای کشنده‌ی PHP با وضعیت ۲۰۰ برمی‌گردد؛ وضعیت واقعی ۵۰۰ است.
        // (هشدارهای معمولی هم در stderr می‌آیند، پس فقط fatal/uncaught را می‌شماریم)
        const fatal = /Fatal error|Uncaught|Parse error/.test(out.error);
        const status = fatal && out.status === 200 ? 500 : out.status;

        const headers = {};
        for (const [name, value] of Object.entries(out.headers)) {
          if (name.startsWith(':')) continue;
          headers[name] = value;
        }
        res.writeHead(status, headers);
        res.end(out.body);
        console.log(
          `[web] ${req.method} ${req.url} → ${status} ${out.body.length}B (${Date.now() - started}ms)` +
            (out.error ? `\n      php: ${out.error}` : ''),
        );
      } catch (error) {
        console.error('[web] خطا:', error.message || error);
        if (!res.headersSent) res.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8' });
        res.end('خطای سرور پیش‌نمایش: ' + error.message);
      }
    });
  });

  server.listen(PORT, HOST, () => {
    console.log(`[web] سرور پیش‌نمایش روی http://${HOST}:${PORT} آماده است`);
    console.log('[web] ورود به پنل: /admin/login  —  Mohusyn / Smosh1387');
  });
}

main().catch((error) => {
  console.error('[fatal]', error);
  process.exit(1);
});
