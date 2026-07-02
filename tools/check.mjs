import { readdir, readFile } from 'node:fs/promises';
import { join, relative } from 'node:path';

const root = process.cwd();
const requiredFiles = [
  'ddys-wordpress-plugin.php',
  'uninstall.php',
  'readme.txt',
  'README.md',
  'README.zh-CN.md',
  'LICENSE',
  'assets/css/frontend.css',
  'assets/css/admin.css',
  'assets/js/admin.js',
  'assets/js/blocks.js',
  'assets/images/icon-16.png',
  'assets/images/icon-32.png',
  'assets/images/icon-192.png',
  'assets/images/icon-512.png',
  'assets-wporg/icon-128x128.png',
  'assets-wporg/icon-256x256.png'
];

const requiredPhpFiles = [
  'includes/functions.php',
  'includes/class-ddys-plugin.php',
  'includes/class-ddys-settings.php',
  'includes/class-ddys-api-client.php',
  'includes/class-ddys-cache.php',
  'includes/class-ddys-shortcodes.php',
  'includes/class-ddys-renderer.php',
  'includes/class-ddys-blocks.php',
  'includes/class-ddys-admin.php'
];

const requiredShortcodes = [
  'ddys_movies',
  'ddys_latest',
  'ddys_hot',
  'ddys_search',
  'ddys_suggest',
  'ddys_calendar',
  'ddys_movie',
  'ddys_sources',
  'ddys_related',
  'ddys_comments',
  'ddys_collections',
  'ddys_collection',
  'ddys_shares',
  'ddys_share',
  'ddys_requests',
  'ddys_activities',
  'ddys_user',
  'ddys_types',
  'ddys_genres',
  'ddys_regions',
  'ddys_request_form'
];

const failures = [];

for (const file of [...requiredFiles, ...requiredPhpFiles]) {
  await mustExist(file);
}

await checkMainHeader();
await checkReadme();
await checkPhpFiles();
await checkShortcodes();
await checkRendererCoverage();
await checkForbiddenText();

if (failures.length) {
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log('DDYS WordPress Plugin checks passed.');

async function mustExist(path) {
  try {
    await readFile(join(root, path));
  } catch {
    failures.push(`Missing required file: ${path}`);
  }
}

async function checkMainHeader() {
  const text = await read('ddys-wordpress-plugin.php');
  for (const field of ['Plugin Name:', 'Version:', 'Requires at least:', 'Tested up to:', 'Requires PHP:', 'License:', 'Text Domain:']) {
    if (!text.includes(field)) {
      failures.push(`Plugin header missing ${field}`);
    }
  }
  if (!text.includes('GPL-2.0-or-later')) {
    failures.push('Plugin header must use GPL-2.0-or-later.');
  }
}

async function checkReadme() {
  const text = await read('readme.txt');
  for (const field of ['=== DDYS WordPress Plugin ===', 'Requires at least:', 'Tested up to:', 'Requires PHP:', 'Stable tag:', 'License: GPLv2 or later']) {
    if (!text.includes(field)) {
      failures.push(`readme.txt missing ${field}`);
    }
  }
}

async function checkPhpFiles() {
  const files = await listFiles(root);
  for (const file of files.filter((item) => item.endsWith('.php'))) {
    const rel = relative(root, file).replace(/\\/g, '/');
    const text = await read(rel);
    if (rel !== 'uninstall.php' && !text.includes("defined('ABSPATH') || exit;")) {
      failures.push(`${rel} must guard direct access.`);
    }
    if (rel === 'uninstall.php' && !text.includes("defined('WP_UNINSTALL_PLUGIN') || exit;")) {
      failures.push('uninstall.php must guard uninstall access.');
    }
    for (const forbidden of ['curl_', 'eval(', 'base64_decode(', 'file_get_contents(']) {
      if (text.includes(forbidden)) {
        failures.push(`${rel} contains forbidden pattern ${forbidden}`);
      }
    }
  }
}

async function checkShortcodes() {
  const text = await read('includes/class-ddys-shortcodes.php');
  for (const shortcode of requiredShortcodes) {
    if (!text.includes(`'${shortcode}'`)) {
      failures.push(`Missing shortcode ${shortcode}`);
    }
  }
}

async function checkRendererCoverage() {
  const renderer = await read('includes/class-ddys-renderer.php');
  const client = await read('includes/class-ddys-api-client.php');
  const helpers = await read('includes/functions.php');

  for (const fragment of ['online', 'download', 'collection_detail', 'share_detail', 'resource_links', 'normalize_list_items']) {
    if (!renderer.includes(fragment)) {
      failures.push(`Renderer missing coverage for ${fragment}`);
    }
  }

  if (!helpers.includes('ddys_wp_allowed_resource_protocols')) {
    failures.push('Resource links should use an explicit protocol allow-list.');
  }

  if (!client.includes('types|genres|regions|calendar')) {
    failures.push('Calendar should use dictionary cache TTL.');
  }
}

async function checkForbiddenText() {
  const files = await listFiles(root);
  const patterns = ['ghp' + '_', 'npm' + '_', '2026' + 'facai', 'x9k' + 'Nx', 'Do not ' + 'bundle', '不要' + '把', '浣庣', '褰辫', '涓嶈'];
  for (const file of files) {
    const rel = relative(root, file).replace(/\\/g, '/');
    if (rel === 'tools/check.mjs') {
      continue;
    }
    if (/\.(png|jpg|jpeg|webp|gif)$/i.test(rel)) {
      continue;
    }
    const text = await read(rel);
    for (const pattern of patterns) {
      if (text.includes(pattern)) {
        failures.push(`${rel} contains restricted text pattern ${pattern}`);
      }
    }
  }
}

async function read(path) {
  return readFile(join(root, path), 'utf8');
}

async function listFiles(dir) {
  const entries = await readdir(dir, { withFileTypes: true });
  const output = [];
  for (const entry of entries) {
    if (entry.name === 'node_modules' || entry.name === '.git') {
      continue;
    }
    const full = join(dir, entry.name);
    if (entry.isDirectory()) {
      output.push(...(await listFiles(full)));
    } else {
      output.push(full);
    }
  }
  return output;
}
