import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import sharp from 'sharp';

const ALLOWED_MIME = new Set(['image/jpeg', 'image/png', 'image/webp']);
const MAX_DOWNLOAD_BYTES = 15 * 1024 * 1024; // 15MB guard against runaway/hostile responses

/**
 * Downloads each source image URL, validates its MIME type from the actual
 * response bytes (not just the URL extension — supplier CDNs frequently
 * hotlink-break or redirect to garbage), re-encodes to .webp, and writes it
 * into the shared uploads directory.
 *
 * @returns {Promise<Array<{ original_url: string, local_path: string }>>}
 */
export async function downloadAndOptimizeImages(imageUrls, { uploadDir, sku, fetchImpl = fetch }) {
    const destDir = path.resolve(uploadDir);
    await mkdir(destDir, { recursive: true });

    const results = [];

    for (const [index, url] of imageUrls.entries()) {
        try {
            const optimized = await fetchAndConvert(url, fetchImpl);
            const filename = `${sku}-${index + 1}.webp`;
            await writeFile(path.join(destDir, filename), optimized);
            results.push({ original_url: url, local_path: filename });
        } catch (err) {
            console.warn(`  [image] skipped ${url}: ${err.message}`);
        }
    }

    return results;
}

async function fetchAndConvert(url, fetchImpl) {
    const res = await fetchImpl(url, { signal: AbortSignal.timeout(15000) });

    if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
    }

    const contentType = res.headers.get('content-type')?.split(';')[0]?.trim();
    const buffer = Buffer.from(await res.arrayBuffer());

    if (buffer.byteLength > MAX_DOWNLOAD_BYTES) {
        throw new Error(`image exceeds ${MAX_DOWNLOAD_BYTES} byte limit`);
    }

    const meta = await sharp(buffer).metadata();
    const detectedMime = `image/${meta.format}`;

    if (contentType && !ALLOWED_MIME.has(contentType) && !ALLOWED_MIME.has(detectedMime)) {
        throw new Error(`unsupported MIME type ${contentType}`);
    }

    if (!ALLOWED_MIME.has(detectedMime)) {
        throw new Error(`unsupported image format detected: ${meta.format}`);
    }

    return sharp(buffer)
        .resize({ width: 1600, height: 1600, fit: 'inside', withoutEnlargement: true })
        .webp({ quality: 82 })
        .toBuffer();
}
