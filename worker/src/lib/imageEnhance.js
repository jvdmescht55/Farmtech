import { GoogleGenAI } from '@google/genai';
import sharp from 'sharp';

const ENHANCE_PROMPT = `Clean up this real product photo for an e-commerce hero image: neutral
light-grey studio background, even product-photography lighting, centered composition, sharp
focus. Do not add, remove, or change any part of the physical product itself — same unit, same
labels, same colour, same accessories. This must still look like a photo of the exact item, not
an illustration or a different product.`;

/**
 * Enhances (not generates) the real first supplier photo — background/lighting/crop cleanup
 * only, via Gemini image editing. Deliberately edits the actual photo rather than generating a
 * fictional one from a text description, so the hero image still depicts the exact item a buyer
 * will receive. Returns null (never throws) on any failure — including the target account
 * simply not having image-model quota — so the pipeline always falls back to the original,
 * unedited photo rather than blocking or showing a broken image.
 */
export async function enhanceHeroImage(imageBuffer, mimeType, { apiKey, model }) {
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
        // normalizes it to the same webp pipeline as the original download.
        return await sharp(buffer)
            .resize({ width: 1600, height: 1600, fit: 'inside', withoutEnlargement: true })
            .webp({ quality: 85 })
            .toBuffer();
    } catch (err) {
        console.warn(`  [image-enhance] skipped: ${err.message}`);
        return null;
    }
}
