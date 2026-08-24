#!/usr/bin/env node
import 'dotenv/config';
import { readFile } from 'node:fs/promises';
import { GoogleGenAI, Type } from '@google/genai';
import { VETTING_SYSTEM_PROMPT } from './prompts/vettingPrompt.js';

/**
 * A much higher bar than normal listing vetting (geminiVetting.js) — this
 * decides whether an ALREADY-STAGED, ALREADY-PRICED product can go live on
 * the storefront with literally no human looking at it first. Reuses
 * VETTING_SYSTEM_PROMPT's category-specific compliance rules (frequency,
 * ICASA, IP rating, joule output, etc.) as the bar for "complete and
 * consistent", rather than duplicating that rule set in a second prompt.
 *
 * Called by app/Console/Commands/AutoPublishProducts.php the same way
 * SourcingPipelineRunner shells out to pipeline.js — a temp JSON file of
 * candidates in, a RESULT_JSON: line out.
 */
const AUTO_PUBLISH_INSTRUCTION = `
You are now deciding whether each of the following ALREADY-STAGED, ALREADY-PRICED products is ready to be
published to the live storefront completely automatically, with no human reviewing it first. This is a much
higher bar than a normal PASS/WARN/FAIL vetting call — only mark a product ready_to_auto_publish when you have
no real doubt at all.

Apply the same category-specific compliance rules described above (frequency, ICASA, IP rating, joule output,
etc.) to judge whether the given specifications are complete and internally consistent for this category.

One explicit, universal exception: every one of these listings is missing a manufacturer technical datasheet
PDF — that is expected (this pipeline has no way to attach one automatically) and must NEVER by itself count
against readiness or lower your confidence. Judge everything else on its own merits.

For each product, decide:
- ready_to_auto_publish: true only if brand, model, warranty, and enough real specifications are present to
  satisfy this category's rules above, there are at least 3 real photos, and nothing about the listing looks
  incomplete, contradictory, or non-compliant.
- confidence: "high" only when you have no real doubt at all; "medium" or "low" for anything less certain — a
  "medium" or "low" item must never be auto-published, so err toward a lower confidence rather than a higher
  one whenever you're not fully certain.
- reasons: 1-3 short, specific reasons for your verdict — name what's present or missing.

Respond with a JSON array, one object per product, in the exact same order given, each with keys:
id, ready_to_auto_publish, confidence, reasons.`;

const RESPONSE_SCHEMA = {
    type: Type.ARRAY,
    items: {
        type: Type.OBJECT,
        required: ['id', 'ready_to_auto_publish', 'confidence', 'reasons'],
        properties: {
            id: { type: Type.INTEGER },
            ready_to_auto_publish: { type: Type.BOOLEAN },
            confidence: { type: Type.STRING, enum: ['high', 'medium', 'low'] },
            reasons: { type: Type.ARRAY, items: { type: Type.STRING } },
        },
    },
};

async function main() {
    const fileArg = process.argv.find((a) => a.startsWith('--file='));
    if (!fileArg) {
        console.error('Usage: node autoPublishCheck.js --file=candidates.json');
        process.exitCode = 1;
        return;
    }

    const candidates = JSON.parse(await readFile(fileArg.slice('--file='.length), 'utf-8'));

    if (candidates.length === 0) {
        console.log('RESULT_JSON:' + JSON.stringify([]));
        return;
    }

    const apiKey = process.env.GEMINI_API_KEY;
    if (!apiKey) {
        throw new Error('GEMINI_API_KEY is not set — cannot run the auto-publish AI check.');
    }

    const client = new GoogleGenAI({ apiKey });
    const model = process.env.GEMINI_MODEL || 'gemini-2.5-flash';

    const response = await client.models.generateContent({
        model,
        contents: `${AUTO_PUBLISH_INSTRUCTION}\n\nProducts:\n${JSON.stringify(candidates, null, 2)}`,
        config: {
            systemInstruction: VETTING_SYSTEM_PROMPT,
            responseMimeType: 'application/json',
            responseSchema: RESPONSE_SCHEMA,
            temperature: 0.1,
        },
    });

    const text = response.text;
    if (!text) {
        throw new Error('Gemini returned no text content — cannot parse auto-publish check result.');
    }

    let results;
    try {
        results = JSON.parse(text);
    } catch (err) {
        throw new Error(`Gemini response was not valid JSON: ${err.message}`);
    }

    console.log('RESULT_JSON:' + JSON.stringify(results));
}

main().catch((err) => {
    console.error('Fatal auto-publish check error:', err);
    process.exitCode = 1;
});
