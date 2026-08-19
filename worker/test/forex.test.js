import { test } from 'node:test';
import assert from 'node:assert/strict';
import { fetchLiveRate, getRateWithFallback } from '../src/lib/forex.js';

test('fetchLiveRate returns null immediately when no API key is set (no network call attempted)', async () => {
    let called = false;
    const fakeFetch = async () => { called = true; };

    const rate = await fetchLiveRate(undefined, 'https://example.invalid', fakeFetch);

    assert.equal(rate, null);
    assert.equal(called, false);
});

test('fetchLiveRate parses a successful provider response', async () => {
    const fakeFetch = async (url) => {
        assert.match(url, /\/pair\/USD\/ZAR$/);
        return {
            ok: true,
            json: async () => ({ conversion_rate: 18.73 }),
        };
    };

    const rate = await fetchLiveRate('fake-key', 'https://example.invalid', fakeFetch);
    assert.equal(rate, 18.73);
});

test('fetchLiveRate returns null on a non-OK response instead of throwing', async () => {
    const fakeFetch = async () => ({ ok: false });
    const rate = await fetchLiveRate('fake-key', 'https://example.invalid', fakeFetch);
    assert.equal(rate, null);
});

test('fetchLiveRate returns null on network failure instead of throwing', async () => {
    const fakeFetch = async () => { throw new Error('ECONNRESET'); };
    const rate = await fetchLiveRate('fake-key', 'https://example.invalid', fakeFetch);
    assert.equal(rate, null);
});

test('getRateWithFallback uses the live rate and persists it when the API succeeds', async () => {
    let written = null;
    const result = await getRateWithFallback({
        apiKey: 'fake-key',
        apiUrl: 'https://example.invalid',
        readFallback: async () => { throw new Error('should not be called'); },
        writeRate: async (rate) => { written = rate; },
        fetchImpl: async () => ({ ok: true, json: async () => ({ conversion_rate: 19.01 }) }),
    });

    assert.deepEqual(result, { rate: 19.01, source: 'live' });
    assert.equal(written, 19.01);
});

test('getRateWithFallback falls back to the stored DB rate when the API is unreachable', async () => {
    const result = await getRateWithFallback({
        apiKey: undefined, // simulates USD_ZAR_API_KEY unset / offline
        apiUrl: 'https://example.invalid',
        readFallback: async () => 18.5,
        writeRate: async () => { throw new Error('should not be called'); },
    });

    assert.deepEqual(result, { rate: 18.5, source: 'fallback' });
});

test('getRateWithFallback throws a clear error when both live and fallback are unavailable', async () => {
    await assert.rejects(
        () => getRateWithFallback({
            apiKey: undefined,
            apiUrl: 'https://example.invalid',
            readFallback: async () => null,
            writeRate: async () => {},
        }),
        /No live USDZAR rate available/
    );
});
