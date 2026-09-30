<?php

namespace App\Services\Herd;

class TierResult
{
    /** @param string[] $reasons */
    public function __construct(
        public readonly ?string $tier,
        public readonly array $reasons = [],
        public readonly bool $official = false,
    ) {}

    public function label(): string
    {
        return $this->tier ? (config('herd.tier_labels')[$this->tier] ?? $this->tier) : 'Unknown';
    }
}
