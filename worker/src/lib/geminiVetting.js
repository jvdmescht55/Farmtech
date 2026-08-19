import { GoogleGenAI } from '@google/genai';
import { VETTING_SYSTEM_PROMPT, VETTING_RESPONSE_SCHEMA, buildUserMessage } from '../prompts/vettingPrompt.js';

const REQUIRED_PRODUCT_FIELDS = ['title', 'short_description', 'description_html', 'category', 'hs_code', 'specs'];
const REQUIRED_COMPLIANCE_FIELDS = [
    'frequency_checked', 'icasa_status', 'plug_type_checked',
    'battery_transport_cert', 'risk_score', 'audit_verdict', 'rejection_reasons',
];

export async function vetListing(listing, { apiKey, model }) {
    if (!apiKey) {
        throw new Error('GEMINI_API_KEY is not set — cannot run AI vetting. Set it in .env.');
    }

    const client = new GoogleGenAI({ apiKey });

    const response = await client.models.generateContent({
        model,
        contents: buildUserMessage(listing),
        config: {
            systemInstruction: VETTING_SYSTEM_PROMPT,
            responseMimeType: 'application/json',
            responseSchema: VETTING_RESPONSE_SCHEMA,
            temperature: 0.2,
        },
    });

    const text = response.text;

    if (!text) {
        throw new Error('Gemini returned no text content — cannot parse vetting result.');
    }

    let result;
    try {
        result = JSON.parse(text);
    } catch (err) {
        throw new Error(`Gemini response was not valid JSON: ${err.message}`);
    }

    return validateResult(result);
}

function validateResult(result) {
    for (const field of REQUIRED_PRODUCT_FIELDS) {
        if (!(field in (result.product || {}))) {
            throw new Error(`Vetting result missing product.${field}`);
        }
    }

    for (const field of REQUIRED_COMPLIANCE_FIELDS) {
        if (!(field in (result.compliance || {}))) {
            throw new Error(`Vetting result missing compliance.${field}`);
        }
    }

    if (!['PASS', 'WARN', 'FAIL'].includes(result.compliance.audit_verdict)) {
        throw new Error(`Invalid audit_verdict: ${result.compliance.audit_verdict}`);
    }

    return result;
}
