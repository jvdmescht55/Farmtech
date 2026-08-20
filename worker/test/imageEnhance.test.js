import { test } from 'node:test';
import assert from 'node:assert/strict';
import { enhanceProductImage } from '../src/lib/imageEnhance.js';

test('returns null immediately with no API key (no network call attempted)', async () => {
    const result = await enhanceProductImage(Buffer.from('fake'), 'image/jpeg', { apiKey: undefined });
    assert.equal(result, null);
});

test('returns null instead of throwing when the API call fails (e.g. no quota, bad model)', async () => {
    // A key-shaped string that isn't a real credential — exercises the
    // network path without needing real quota, and confirms failures never
    // propagate up and break the pipeline.
    const result = await enhanceProductImage(Buffer.from('fake-image-bytes'), 'image/jpeg', {
        apiKey: 'not-a-real-key',
        model: 'gemini-2.5-flash-image',
    });
    assert.equal(result, null);
});
