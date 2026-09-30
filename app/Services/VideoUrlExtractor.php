<?php

namespace App\Services;

use App\Models\Product;

/**
 * Pulls a single embedded listing-video URL out of what a product's scrape
 * already stored — its raw description HTML, its spec/attribute values, its
 * key-features list, and its image rows' original supplier URLs.
 *
 * Like SupplierContactExtractor, this makes NO network calls: it does not
 * hit video.alibaba.com, the Taobao video API, or the listing page. It only
 * re-reads text the catalog already holds, and returns null — never a
 * fabricated or guessed URL — when there's no real video link in there.
 *
 * In practice most Alibaba scrapes carry no usable video URL at all: the
 * listing player is fed an encrypted videoId through a JS API, not a plain
 * file link, so "no video found" for the large majority of products is the
 * honest, expected result. A match only happens when a direct .mp4/.webm (or
 * a video-CDN .mp4/.m3u8) URL was left in the description markup or a spec.
 *
 * Note on rendering: a matched URL usually points at Alibaba/Alicdn, so the
 * storefront <video> tag hotlinks a third-party asset — same trade-off the
 * catalog already accepts for supplier images (see ProductImage::getUrl).
 * It may 403 on hotlink protection or disappear; the player degrades to its
 * poster image if so.
 */
class VideoUrlExtractor
{
    /**
     * Tried in order against each stored text field. A "URL char" here is
     * anything that isn't whitespace, a quote, or a bracket/angle — enough to
     * carve a URL out of surrounding HTML / prose.
     *   1. a direct .mp4 / .webm file, optional query string;
     *   2. an Alibaba / Taobao video endpoint (player page or stream) on
     *      video.alibaba.com or cloud.video.taobao.com;
     *   3. an Alicdn .mp4 / .m3u8 stream.
     */
    private const PATTERNS = [
        "#https?://[^\\s\"'<>()\\]]+?\\.(?:mp4|webm)(?:\\?[^\\s\"'<>()\\]]*)?#i",
        "#https?://(?:[a-z0-9.-]+\\.)?(?:video\\.alibaba\\.com|cloud\\.video\\.taobao\\.com)/[^\\s\"'<>()\\]]+#i",
        "#https?://[a-z0-9.-]*alicdn\\.com/[^\\s\"'<>()\\]]+?\\.(?:mp4|m3u8)(?:\\?[^\\s\"'<>()\\]]*)?#i",
    ];

    public function extract(Product $product): ?string
    {
        foreach ($this->haystacks($product) as $text) {
            if ($url = $this->firstMatch($text)) {
                return $url;
            }
        }

        return null;
    }

    /** @return iterable<string> */
    private function haystacks(Product $product): iterable
    {
        // Raw HTML first — not strip_tags'd — so src="..." / <source> / data-video attrs are visible.
        yield (string) $product->description_html;

        foreach ((array) $product->specifications as $value) {
            if (is_string($value)) {
                yield $value;
            }
        }

        foreach ((array) $product->key_features as $value) {
            if (is_string($value)) {
                yield $value;
            }
        }

        if ($product->relationLoaded('images') || $product->exists) {
            foreach ($product->images as $image) {
                yield (string) $image->original_url;
            }
        }
    }

    private function firstMatch(string $text): ?string
    {
        if ($text === '') {
            return null;
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);

        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return rtrim($m[0], '.,;)"\'');
            }
        }

        return null;
    }
}
