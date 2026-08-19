/**
 * Live USD/ZAR forex fetch with DB-backed fallback.
 *
 * `fetchLiveRate` and `getRateWithFallback` are separated from DB access so
 * the fallback logic can be unit-tested without a live database connection
 * (see worker/test/forex.test.js) — pass in plain functions for
 * `readFallback`/`writeRate` rather than a live pool.
 */

const PAIR = 'USDZAR';

export async function fetchLiveRate(apiKey, apiUrl, fetchImpl = fetch) {
    if (!apiKey) return null;

    try {
        const res = await fetchImpl(`${apiUrl}/${apiKey}/pair/USD/ZAR`, {
            signal: AbortSignal.timeout(8000),
        });

        if (!res.ok) return null;

        const data = await res.json();
        const rate = data?.conversion_rate;

        return typeof rate === 'number' && rate > 0 ? rate : null;
    } catch {
        return null; // network/timeout — caller falls back to the last stored rate
    }
}

/**
 * @param {object} opts
 * @param {string|undefined} opts.apiKey
 * @param {string} opts.apiUrl
 * @param {() => Promise<number|null>} opts.readFallback - last known rate from `exchange_rates`
 * @param {(rate: number) => Promise<void>} opts.writeRate - persist a freshly fetched rate
 * @param {typeof fetch} [opts.fetchImpl]
 * @returns {Promise<{ rate: number, source: 'live' | 'fallback' }>}
 */
export async function getRateWithFallback({ apiKey, apiUrl, readFallback, writeRate, fetchImpl }) {
    const liveRate = await fetchLiveRate(apiKey, apiUrl, fetchImpl);

    if (liveRate !== null) {
        await writeRate(liveRate);
        return { rate: liveRate, source: 'live' };
    }

    const fallbackRate = await readFallback();

    if (fallbackRate === null) {
        throw new Error(
            `No live ${PAIR} rate available (offline or USD_ZAR_API_KEY unset) and no fallback rate stored in exchange_rates. ` +
            'Seed one via `php artisan db:seed --class=ExchangeRateSeeder` or set USD_ZAR_API_KEY.'
        );
    }

    return { rate: fallbackRate, source: 'fallback' };
}
