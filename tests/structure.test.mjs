import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

test('plugin exposes the full shortcode surface', async () => {
  const text = await readFile('includes/class-ddys-shortcodes.php', 'utf8');
  const shortcodes = [...text.matchAll(/'ddys_[a-z_]+'.*=>/g)].map((match) => match[0].split("'")[1]);

  assert.equal(shortcodes.length, 21);
  assert.ok(shortcodes.includes('ddys_request_form'));
  assert.ok(shortcodes.includes('ddys_sources'));
  assert.ok(shortcodes.includes('ddys_collection'));
});

test('renderer covers nested DDYS API shapes', async () => {
  const text = await readFile('includes/class-ddys-renderer.php', 'utf8');

  assert.match(text, /normalize_source_groups/);
  assert.match(text, /collection_detail/);
  assert.match(text, /share_detail/);
  assert.match(text, /resource_links/);
});

test('readme uses language-specific official website anchor text', async () => {
  const en = await readFile('README.md', 'utf8');
  const zh = await readFile('README.zh-CN.md', 'utf8');

  assert.match(en, /\[DDYS\]\(https:\/\/ddys\.io\/\)/);
  assert.match(zh, /\[低端影视\]\(https:\/\/ddys\.io\/\)/);
});
