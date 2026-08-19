/** Simple exponential-backoff retry for transient network/API errors. */
export async function withRetry(fn, { attempts = 3, baseDelayMs = 1000, label = 'operation' } = {}) {
    let lastErr;

    for (let attempt = 1; attempt <= attempts; attempt++) {
        try {
            return await fn();
        } catch (err) {
            lastErr = err;
            const isLast = attempt === attempts;
            console.warn(`  [retry] ${label} failed (attempt ${attempt}/${attempts}): ${err.message}${isLast ? '' : ' — retrying...'}`);

            if (!isLast) {
                await sleep(baseDelayMs * 2 ** (attempt - 1));
            }
        }
    }

    throw lastErr;
}

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}
