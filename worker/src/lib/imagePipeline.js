import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import sharp from 'sharp';
import { enhanceHeroImage } from './imageEnhance.js';
import { isS3Configured, uploadToS3 } from './s3Storage.js';

const ALLOWED_MIME = new Set(['image/jpeg', 'image/png', 'image/webp']);
const MAX_DOWNLOAD_BYTES = 15 * 1024 * 1024; // 15MB guard against runaway/hostile responses

/**
 * Downloads each source image URL, validates its MIME type from the actual
 * response bytes (not just the URL extension — supplier CDNs frequently
 * hotlink-break or redirect to garbage), re-encodes to .webp, and stores it
 * — locally under uploadDir by default, or in S3/R2 when
 * WORKER_FILESYSTEM_DISK=s3 (see s3Storage.js; mirrors Laravel's
 * FILESYSTEM_DISK so one .env drives both sides). The first (hero/
 * thumbnail) image also gets an AI background/lighting cleanup pass — see
 * imageEnhance.js — with the original photo used as-is if that fails for
 * any reason.
 *
 * @returns {Promise<Array<{ original_url: string, local_path: string }>>}
 */
export async function downloadAndOptimizeImages(imageUrls, { uploadDir, sku, fetchImpl = fetch, geminiApiKey, geminiImageModel }) {
    const useS3 = isS3Configured();
    const destDir = useS3 ? null : path.resolve(uploadDir);
    if (destDir) await mkdir(destDir, { recursive: true });

    const results = [];

    for (const [index, url] of imageUrls.entries()) {
        try {
            const { webpBuffer, rawBuffer, mime } = await fetchAndConvert(url, fetchImpl);

            let finalBuffer = webpBuffer;

            if (index === 0 && geminiApiKey) {
                const enhanced = await enhanceHeroImage(rawBuffer, mime, { apiKey: geminiApiKey, model: geminiImageModel });
                if (enhanced) {
                    finalBuffer = enhanced;
                    console.log('  [image-enhance] hero image cleaned up (background/lighting)');
                }
            }

            const filename = `${sku}-${index + 1}.webp`;

            if (useS3) {
                await uploadToS3(filename, finalBuffer);
            } else {
                await writeFile(path.join(destDir, filename), finalBuffer);
            }

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

    const webpBuffer = await sharp(buffer)
        .resize({ width: 1600, height: 1600, fit: 'inside', withoutEnlargement: true })
        .webp({ quality: 82 })
        .toBuffer();

    return { webpBuffer, rawBuffer: buffer, mime: detectedMime };
}
