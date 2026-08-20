import { GoogleGenAI } from '@google/genai';
import sharp from 'sharp';

const ENHANCE_PROMPT = `Clean up this real product photo for an e-commerce catalog image: neutral
white or very light-grey studio background, even product-photography lighting, product centered
and cropped tight with a small even margin, sharp focus. Do not add, remove, or change any part of
the physical product itself — same unit, same labels, same colour, same accessories. This must
still look like a photo of the exact item, not an illustration or a different product.`;

/**
 * Enhances (not generates) a real supplier photo — background/lighting/crop cleanup only, via
 * Gemini image editing. Applied to every product image in the pipeline (not hero-only, as of
 * rev. 9), since a mixed catalog of cleaned and uncleaned photos looks worse than consistently
 * treating all of them. Deliberately edits the actual photo rather than generating a fictional
 * one from a text description, so the image still depicts the exact item a buyer will receive.
 * Returns null (never throws) on any failure — including the target account simply not having
 * image-model quota — so the pipeline always falls back to the deterministic
 * `normalizeToSquareCanvas` treatment (see imageNormalize.js) rather than blocking or showing a
 * broken image.
 */
export async function enhanceProductImage(imageBuffer, mimeType, { apiKey, model }) {
    if (!apiKey) return null;

    try {
        const client = new GoogleGenAI({ apiKey });

        const response = await client.models.generateContent({
            model: model || 'gemini-2.5-flash-image',
            contents: [{
                role: 'user',
                parts: [
                    { text: ENHANCE_PROMPT },
                    { inlineData: { mimeType, data: imageBuffer.toString('base64') } },
                ],
            }],
            config: { responseModalities: ['IMAGE'] },
        });

        const parts = response.candidates?.[0]?.content?.parts ?? [];
        const imagePart = parts.find((part) => part.inlineData?.data);

        if (!imagePart) {
            console.warn('  [image-enhance] no image returned by the model — keeping original photo');
            return null;
        }

        const buffer = Buffer.from(imagePart.inlineData.data, 'base64');

        // Round-trip through sharp: confirms it's a real decodable image and
        // normalizes it onto the same 1000x1000 white canvas as the
        // deterministic fallback, so hero and non-hero images, AI-cleaned or
        // not, all end up the same consistent size.
        return await sharp(buffer)
            .resize({
                width: 1000,
                height: 1000,
                fit: 'contain',
                background: { r: 255, g: 255, b: 255, alpha: 1 },
                withoutEnlargement: true,
            })
            .webp({ quality: 85 })
            .toBuffer();
    } catch (err) {
        console.warn(`  [image-enhance] skipped: ${err.message}`);
        return null;
    }
}
