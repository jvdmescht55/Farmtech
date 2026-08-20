import sharp from 'sharp';

/**
 * Deterministic background-normalization fallback for when AI enhancement
 * isn't available (no Gemini key configured, or the call failed/returned
 * nothing) — trims uniform-color borders using sharp's own edge-detection
 * trim, then centers the trimmed subject on a plain white square canvas.
 *
 * This is a real, honest improvement (dead whitespace/letterboxing removed,
 * consistent square framing) but it is NOT true background removal — sharp
 * has no subject-matting model, so a photo with a complex/busy background
 * keeps that background, just cropped and centered. See PROGRESS.md for why
 * true alpha-transparency cutout isn't implemented here.
 */
export async function normalizeToSquareCanvas(buffer, size = 1000) {
    let working = buffer;

    try {
        working = await sharp(buffer).trim().toBuffer();
    } catch {
        // A perfectly uniform-color image (trim would remove everything) or
        // any other trim failure — fall back to the untrimmed original.
    }

    return sharp(working)
        .resize({
            width: size,
            height: size,
            fit: 'contain',
            background: { r: 255, g: 255, b: 255, alpha: 1 },
        })
        .webp({ quality: 85 })
        .toBuffer();
}
