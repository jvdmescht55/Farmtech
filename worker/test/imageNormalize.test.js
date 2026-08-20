import { test } from 'node:test';
import assert from 'node:assert/strict';
import sharp from 'sharp';
import { normalizeToSquareCanvas } from '../src/lib/imageNormalize.js';

test('pads a non-square image onto a 1000x1000 white canvas without distorting it', async () => {
    const wide = await sharp({
        create: { width: 800, height: 400, channels: 3, background: { r: 10, g: 20, b: 30 } },
    }).jpeg().toBuffer();

    const result = await normalizeToSquareCanvas(wide);
    const meta = await sharp(result).metadata();

    assert.equal(meta.format, 'webp');
    assert.equal(meta.width, 1000);
    assert.equal(meta.height, 1000);
});

test('does not throw on a perfectly uniform-color image (trim would remove everything)', async () => {
    const flat = await sharp({
        create: { width: 500, height: 500, channels: 3, background: { r: 255, g: 255, b: 255 } },
    }).jpeg().toBuffer();

    const result = await normalizeToSquareCanvas(flat);
    const meta = await sharp(result).metadata();

    assert.equal(meta.width, 1000);
    assert.equal(meta.height, 1000);
});

test('respects a custom canvas size', async () => {
    const square = await sharp({
        create: { width: 300, height: 300, channels: 3, background: { r: 5, g: 5, b: 5 } },
    }).jpeg().toBuffer();

    const result = await normalizeToSquareCanvas(square, 500);
    const meta = await sharp(result).metadata();

    assert.equal(meta.width, 500);
    assert.equal(meta.height, 500);
});
