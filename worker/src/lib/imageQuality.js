import { GoogleGenAI, Type } from '@google/genai';

export const MIN_DIMENSION_PX = 800;
const MIN_ASPECT_RATIO = 0.4; // e.g. rejects anything narrower than 4:10
const MAX_ASPECT_RATIO = 2.5; // e.g. rejects anything wider than 10:4

/**
 * Cheap, deterministic checks — no API call. Run these first so an obviously
 * bad image never reaches (and pays for) the AI artifact/watermark check.
 */
export function checkDimensions(width, height) {
    if (width < MIN_DIMENSION_PX || height < MIN_DIMENSION_PX) {
        return { ok: false, reason: `image is ${width}x${height}px, below the ${MIN_DIMENSION_PX}x${MIN_DIMENSION_PX}px minimum` };
    }

    const ratio = width / height;
    if (ratio < MIN_ASPECT_RATIO || ratio > MAX_ASPECT_RATIO) {
        return { ok: false, reason: `image aspect ratio ${ratio.toFixed(2)}:1 is outside the acceptable ${MIN_ASPECT_RATIO}–${MAX_ASPECT_RATIO} range` };
    }

    return { ok: true };
}

const ARTIFACT_PROMPT = `Look at this product photo, intended for an e-commerce listing. Answer
whether it is usable as-is: reject it if it has a visible watermark, logo overlay, or embedded
text banner from a marketplace/supplier; or if it shows heavy JPEG compression artifacts, blur,
or pixelation severe enough that product details are hard to make out. Do not reject it for
ordinary studio backgrounds, normal product photography lighting, or minor imperfections — only
for the specific problems above.`;

const ARTIFACT_SCHEMA = {
    type: Type.OBJECT,
    required: ['accept', 'reason'],
    properties: {
        accept: { type: Type.BOOLEAN },
        reason: { type: Type.STRING },
    },
};

/**
 * AI-backed check for watermarks/heavy compression artifacts — the one
 * quality signal that genuinely needs a vision model, not a pixel-math
 * heuristic. Never throws: on any failure (no API key, no quota, bad
 * response) it returns { ok: true }, same "degrade gracefully, don't block
 * the pipeline" principle as enhanceHeroImage — an unverified image is
 * still better than none, and this check is a bonus filter, not the only
 * one (dimension/aspect-ratio checks above already ran and are deterministic).
 */
export async function checkArtifactsAndWatermarks(buffer, mimeType, { apiKey, model }) {
    if (!apiKey) return { ok: true };

    try {
        const client = new GoogleGenAI({ apiKey });

        const response = await client.models.generateContent({
            model: model || 'gemini-2.5-flash',
            contents: [{
                role: 'user',
                parts: [
                    { text: ARTIFACT_PROMPT },
                    { inlineData: { mimeType, data: buffer.toString('base64') } },
                ],
            }],
            config: {
                responseMimeType: 'application/json',
                responseSchema: ARTIFACT_SCHEMA,
                temperature: 0,
            },
        });

        const text = response.text;
        if (!text) return { ok: true };

        const result = JSON.parse(text);
        if (result.accept === false) {
            return { ok: false, reason: result.reason || 'flagged for watermark/compression artifacts' };
        }

        return { ok: true };
    } catch (err) {
        console.warn(`  [image-quality] artifact check skipped: ${err.message}`);
        return { ok: true };
    }
}
