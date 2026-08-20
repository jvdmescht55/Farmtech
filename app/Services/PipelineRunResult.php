<?php

namespace App\Services;

/**
 * Structured result of a SourcingPipelineRunner::run() call, parsed from the
 * Node pipeline's `RESULT_JSON:` summary line rather than scraped from its
 * human-readable log output.
 */
class PipelineRunResult
{
    /**
     * @param array<int, array<string, mixed>> $items One entry per listing:
     *        {sku, title, verdict, status, productId, slug} on success,
     *        {sku, title, error} if that listing threw, or
     *        {sku, title, skipped, reason} if it was skipped (e.g. blacklisted supplier).
     * @param array{passed?: int, warned?: int, failed?: int, errored?: int} $counts
     * @param bool $parsed False if the pipeline output never contained a RESULT_JSON line
     *        (process crashed before finishing) — callers should treat this as "unknown outcome",
     *        not "zero items processed".
     */
    public function __construct(
        public readonly array $items,
        public readonly array $counts,
        public readonly string $rawOutput,
        public readonly bool $parsed,
    ) {}

    /** Items that actually landed in `products` with status=pending_review — i.e. need a human look. */
    public function pendingReviewItems(): array
    {
        return array_values(array_filter($this->items, fn (array $item) => ($item['status'] ?? null) === 'pending_review'));
    }

    public function rejectedItems(): array
    {
        return array_values(array_filter($this->items, fn (array $item) => ($item['status'] ?? null) === 'rejected'));
    }

    public function erroredItems(): array
    {
        return array_values(array_filter($this->items, fn (array $item) => isset($item['error'])));
    }
}
