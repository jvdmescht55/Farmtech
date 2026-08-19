import { test } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtemp, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import sharp from 'sharp';
import { downloadAndOptimizeImages } from '../src/lib/imagePipeline.js';

test('downloads a real image, validates it, and re-encodes to webp under the size cap', async (t) => {
    const dir = await mkdtemp(path.join(tmpdir(), 'farmtech-img-'));
    t.after(() => rm(dir, { recursive: true, force: true }));

    const results = await downloadAndOptimizeImages(
        ['https://picsum.photos/seed/farmtech-test/2000/2000'],
        { uploadDir: dir, sku: 'TEST-SKU' }
    );

    assert.equal(results.length, 1);
    assert.equal(results[0].local_path, 'TEST-SKU-1.webp');

    const written = await readFile(path.join(dir, 'TEST-SKU-1.webp'));
    const meta = await sharp(written).metadata();

    assert.equal(meta.format, 'webp');
    assert.ok(meta.width <= 1600, `expected width <= 1600, got ${meta.width}`);
    assert.ok(meta.height <= 1600, `expected height <= 1600, got ${meta.height}`);
});

test('skips a URL that returns a non-image MIME type instead of throwing', async (t) => {
    const dir = await mkdtemp(path.join(tmpdir(), 'farmtech-img-'));
    t.after(() => rm(dir, { recursive: true, force: true }));

    const fakeFetch = async () => ({
        ok: true,
        headers: { get: () => 'text/html' },
        arrayBuffer: async () => new TextEncoder().encode('<html>not an image</html>').buffer,
    });

    const results = await downloadAndOptimizeImages(
        ['https://example.invalid/not-an-image'],
        { uploadDir: dir, sku: 'TEST-SKU', fetchImpl: fakeFetch }
    );

    assert.equal(results.length, 0);
});

test('skips a URL that 404s instead of throwing', async (t) => {
    const dir = await mkdtemp(path.join(tmpdir(), 'farmtech-img-'));
    t.after(() => rm(dir, { recursive: true, force: true }));

    const fakeFetch = async () => ({ ok: false, status: 404 });

    const results = await downloadAndOptimizeImages(
        ['https://example.invalid/missing.jpg'],
        { uploadDir: dir, sku: 'TEST-SKU', fetchImpl: fakeFetch }
    );

    assert.equal(results.length, 0);
});
