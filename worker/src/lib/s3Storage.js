import { S3Client, PutObjectCommand } from '@aws-sdk/client-s3';

/**
 * Mirrors Laravel's config/filesystems.php 's3' disk — same env var names
 * (AWS_ACCESS_KEY_ID etc.) so one .env configures both sides. Works for
 * both AWS S3 and Cloudflare R2 (R2 is S3-API-compatible; set AWS_ENDPOINT
 * to the R2 endpoint and AWS_USE_PATH_STYLE_ENDPOINT=true).
 *
 * Written to spec, not verified against a real bucket — no credentials
 * available in this environment. See PROGRESS.md.
 */
let client;

function getClient() {
    if (!client) {
        client = new S3Client({
            region: process.env.AWS_DEFAULT_REGION || 'auto',
            endpoint: process.env.AWS_ENDPOINT || undefined,
            forcePathStyle: process.env.AWS_USE_PATH_STYLE_ENDPOINT === 'true',
            credentials: {
                accessKeyId: process.env.AWS_ACCESS_KEY_ID,
                secretAccessKey: process.env.AWS_SECRET_ACCESS_KEY,
            },
        });
    }

    return client;
}

export function isS3Configured() {
    return process.env.WORKER_FILESYSTEM_DISK === 's3'
        && !!process.env.AWS_ACCESS_KEY_ID
        && !!process.env.AWS_BUCKET;
}

/** @param {string} filename - object key under the products/ prefix, e.g. "FT-SKU-1.webp" */
export async function uploadToS3(filename, buffer) {
    await getClient().send(new PutObjectCommand({
        Bucket: process.env.AWS_BUCKET,
        Key: `products/${filename}`,
        Body: buffer,
        ContentType: 'image/webp',
        ACL: process.env.AWS_ENDPOINT ? undefined : 'public-read', // R2 ignores/rejects ACLs; S3 needs it for a public bucket without a bucket policy
    }));
}
