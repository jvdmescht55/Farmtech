import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import sharp from 'sharp';
import { enhanceProductImage } from './imageEnhance.js';
import { normalizeToSquareCanvas } from './imageNormalize.js';
import { isS3Configured, uploadToS3 } from './s3Storage.js';
import { checkDimensions, checkArtifactsAndWatermarks } from './imageQuality.js';

const ALLOWED_MIME = new Set(['image/jpeg', 'image/png', 'image/webp']);
const MAX_DOWNLOAD_BYTES = 15 * 1024 * 1024; // 15MB guard against runaway/hostile responses

/**
 * Downloads each source image URL, validates its MIME type from the actual
 * response bytes (not just the URL extension — supplier CDNs frequently
 * hotlink-break or redirect to garbage), and normalizes every one onto a
 * consistent 1000x1000 white studio canvas before storing — locally under
 * uploadDir by default, or in S3/R2 when WORKER_FILESYSTEM_DISK=s3 (see
 * s3Storage.js; mirrors Laravel's FILESYSTEM_DISK so one .env drives both
 * sides). Every image gets Gemini's AI background/lighting cleanup (see
 * imageEnhance.js) when an API key is configured; when it isn't, or the AI
 * call fails for any reason, a deterministic trim-and-pad-to-square pass
 * (see imageNormalize.js) still runs — real border/whitespace cleanup, not
 * true background removal, but never a bare unprocessed photo.
 *
 * @returns {Promise<Array<{ original_url: string, local_path: string }>>}
 */
export async function downloadAndOptimizeImages(imageUrls, { uploadDir, sku, fetchImpl = fetch, geminiApiKey, geminiImageModel, geminiModel }) {
    const useS3 = isS3Configured();
    const destDir = useS3 ? null : path.resolve(uploadDir);
    if (destDir) await mkdir(destDir, { recursive: true });

    const results = [];

    for (const [index, url] of imageUrls.entries()) {
        try {
            const { rawBuffer, mime, width, height } = await fetchAndConvert(url, fetchImpl);

            const dimensionCheck = checkDimensions(width, height);
            if (!dimensionCheck.ok) {
                throw new Error(dimensionCheck.reason);
            }

            const artifactCheck = await checkArtifactsAndWatermarks(rawBuffer, mime, { apiKey: geminiApiKey, model: geminiModel });
            if (!artifactCheck.ok) {
                throw new Error(`rejected — ${artifactCheck.reason}`);
            }

            let finalBuffer = null;

            if (geminiApiKey) {
                const enhanced = await enhanceProductImage(rawBuffer, mime, { apiKey: geminiApiKey, model: geminiImageModel });
                if (enhanced) {
                    finalBuffer = enhanced;
                    console.log(`  [image-enhance] image ${index + 1} cleaned up (background/lighting)`);
                }
            }

            if (!finalBuffer) {
                finalBuffer = await normalizeToSquareCanvas(rawBuffer);
            }

            const filename = `${sku}-${index + 1}.webp`;

            if (useS3) {
                await uploadToS3(filename, finalBuffer);
            } else {
                await writeFile(path.join(destDir, filename), finalBuffer);
            }

            // "products/" prefix matches the local disk convention
            // ProductImage::getUrlAttribute() expects (asset('storage/' .
            // local_path), i.e. storage/app/public/products/<filename>) —
            // same convention the PHP staging pipeline uses.
            results.push({ original_url: url, local_path: `products/${filename}` });
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

    return { rawBuffer: buffer, mime: detectedMime, width: meta.width, height: meta.height };
}
