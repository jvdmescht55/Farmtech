<?php

namespace App\Services;

use App\Models\Product;

/**
 * Pulls a usable WhatsApp/mobile number for a product's supplier out of
 * whatever the scraper actually captured, and formats it to international
 * dialing form.
 *
 * Real Alibaba listing scrapes almost never expose a raw phone number —
 * Alibaba routes buyer/supplier contact through its own encrypted messaging
 * (see the scraper's `contactEncryptId` field), specifically to keep people
 * from doing exactly what this service does. The one place a real number
 * does turn up is when a seller pastes it directly into free text (a spec
 * value, the description) to route buyers off-platform — e.g. a
 * "Whatsapp for Discount" attribute. So this only ever looks in the
 * product's own stored text/attributes, and returns null — never a
 * fabricated or guessed number — when nothing is actually there.
 *
 * The same applies to the WeChat ID and direct sales email helpers below:
 * they read the product's already-stored spec/description text only (no
 * network calls, no Alibaba member-page fetch), preferring a value an admin
 * typed into the `supplier_wechat_id` / `supplier_email` column, and return
 * null rather than guess. A supplier's contact *name* is never inferred from
 * free text — that column is manual-entry only.
 */
class SupplierContactExtractor
{
    /** Spec/attribute keys worth checking for an embedded contact number. */
    private const CONTACT_KEY_PATTERN = '/\b(whats\s?app|wechat|phone|mobile|contact|tel)\b/i';

    /** A phone-shaped run of digits — 7 to 17 chars incl. separators, optional leading +. No regex delimiters — composed into two different patterns below. */
    private const PHONE_BODY = '(\+?\d[\d \-().]{5,16}\d)';

    /**
     * A WeChat ID sitting right after a "WeChat:" / "Wechat ID:" / "WX:"
     * label. WeChat IDs are 6–20 chars, must start with a letter, and allow
     * letters/digits/underscore/hyphen — the trailing capture is deliberately
     * tight so a stray sentence after the label isn't mistaken for an ID.
     */
    private const WECHAT_PATTERN = '/(?:we\s?chat|wechat\s*id|\bwx)\s*(?:id)?\s*[:：]\s*([A-Za-z][A-Za-z0-9_-]{5,19})\b/i';

    /** A plain email address. */
    private const EMAIL_PATTERN = '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/';

    /**
     * The number to actually use for this product: a manually-entered
     * `supplier_phone` (an admin looked it up and typed it in — trusted as
     * already-correct, so it's only cleaned of stray formatting, never
     * re-guessed a country code) takes priority over whatever auto-`extract()`
     * can pull from scraped data, since a human-sourced number is far more
     * likely to be real and current.
     */
    public function resolve(Product $product): ?string
    {
        $manual = trim((string) ($product->supplier_whatsapp ?: $product->supplier_phone));

        if ($manual !== '') {
            return $this->format($manual, $product);
        }

        return $this->extract($product);
    }

    public function extract(Product $product): ?string
    {
        $raw = $this->findInSpecifications($product)
            ?? $this->findInFreeText($product);

        return $raw ? $this->format($raw, $product) : null;
    }

    /**
     * The WeChat ID to use for this product: a manually-entered
     * `supplier_wechat_id` takes priority; otherwise whatever a
     * "WeChat:"/"WX:"-labelled value in the product's own stored text yields.
     * null when neither is present — never a guess.
     */
    public function wechatId(Product $product): ?string
    {
        $manual = trim((string) $product->supplier_wechat_id);

        if ($manual !== '') {
            return $manual;
        }

        return $this->matchInProductText($product, self::WECHAT_PATTERN);
    }

    /**
     * The direct sales email to use for this product: a manually-entered
     * `supplier_email` takes priority; otherwise the first address found in a
     * contact-labelled spec value or in the product's own free text. null
     * when neither is present.
     */
    public function email(Product $product): ?string
    {
        $manual = trim((string) $product->supplier_email);

        if ($manual !== '') {
            return $manual;
        }

        foreach ((array) $product->specifications as $key => $value) {
            if (is_string($key) && is_string($value)
                && preg_match(self::CONTACT_KEY_PATTERN, $key)
                && preg_match(self::EMAIL_PATTERN, $value, $m)) {
                return $m[0];
            }
        }

        return $this->matchInProductText($product, self::EMAIL_PATTERN);
    }

    /** First capture group (or whole match) of $pattern across the product's short description + description HTML, plain-text. */
    private function matchInProductText(Product $product, string $pattern): ?string
    {
        $haystacks = array_filter([
            $product->short_description,
            $product->description_html,
        ]);

        foreach ($haystacks as $text) {
            if (preg_match($pattern, strip_tags((string) $text), $m)) {
                return $m[1] ?? $m[0];
            }
        }

        return null;
    }

    /** Raw digits found, unformatted — exposed separately so callers can tell "found but unformatted" apart from "not found" if ever needed. */
    private function findInSpecifications(Product $product): ?string
    {
        foreach ((array) $product->specifications as $key => $value) {
            if (! is_string($value) || ! is_string($key)) {
                continue;
            }

            if (preg_match(self::CONTACT_KEY_PATTERN, $key) && preg_match('/'.self::PHONE_BODY.'/', $value, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    /** Fallback: a number sitting right after a contact keyword in the product's own free-text fields. */
    private function findInFreeText(Product $product): ?string
    {
        $haystacks = array_filter([
            $product->short_description,
            $product->description_html,
        ]);

        foreach ($haystacks as $text) {
            $plain = strip_tags((string) $text);

            if (preg_match('/(?:whats\s?app|wechat|mobile|contact|tel)\D{0,15}'.self::PHONE_BODY.'/i', $plain, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    /**
     * Normalizes to "+<countrycode><number>" form: strips everything but
     * digits and a leading '+', collapses "00" international-prefix dialing
     * to '+', strips a leading domestic '0', and — since the overwhelming
     * majority of this catalog's suppliers are mainland Chinese
     * manufacturers/trading companies (see Product::specifications
     * "Place of Origin" / supplier_name on nearly every listing) — assumes
     * a number with no country code already on it is a domestic Chinese
     * number and prefixes +86, per spec.
     */
    public function format(string $raw, ?Product $product = null): string
    {
        $digits = preg_replace('/[^\d+]/', '', $raw);

        if (str_starts_with($digits, '+')) {
            return $digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+'.ltrim(substr($digits, 2), '0');
        }

        $digits = ltrim($digits, '0');

        if (str_starts_with($digits, '86') && strlen($digits) >= 12) {
            return '+'.$digits;
        }

        return $this->isChineseMainlandSupplier($product) || $product === null
            ? '+86'.$digits
            : '+'.$digits;
    }

    private function isChineseMainlandSupplier(?Product $product): bool
    {
        if ($product === null) {
            return true;
        }

        $origin = (string) (($product->specifications ?? [])['Place of Origin'] ?? '');
        $haystack = strtolower($origin.' '.$product->supplier_name.' '.$product->supplier_url);

        if (str_contains($haystack, 'hong kong') || str_contains($haystack, 'taiwan')) {
            return false;
        }

        return str_contains($haystack, 'china')
            || str_contains($haystack, 'alibaba.com')
            || $origin !== ''
            || $product->supplier_name !== null;
    }
}
