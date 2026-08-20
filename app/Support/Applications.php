<?php

namespace App\Support;

use App\Enums\Industry;
use App\Enums\ProductCategory;

/**
 * "Shop by application" — curated real links into existing categories/
 * industries for buyers who think in tasks ("weigh cattle"), not technical
 * category names. Shared between the homepage section and the header
 * search dropdown so the two don't drift out of sync.
 */
class Applications
{
    public static function all(): array
    {
        return [
            ['label' => 'Weigh livestock', 'description' => 'Scales, indicators and load cells', 'icon' => 'scale', 'target' => ProductCategory::Scales],
            ['label' => 'Track & identify animals', 'description' => 'RFID readers and ear tagging', 'icon' => 'rfid', 'target' => ProductCategory::Rfid],
            ['label' => 'Monitor irrigation', 'description' => 'Smart irrigation controllers', 'icon' => 'irrigation', 'target' => ProductCategory::SmartIrrigation],
            ['label' => 'Measure on a construction site', 'description' => 'Laser levels and survey equipment', 'icon' => 'laser', 'target' => Industry::Construction],
            ['label' => 'Power remote equipment', 'description' => 'Solar pumps and MPPT controllers', 'icon' => 'solar', 'target' => Industry::SolarPower],
            ['label' => 'Track fleet & industrial assets', 'description' => 'GPS trackers and industrial RFID', 'icon' => 'gps', 'target' => ProductCategory::FleetTrackers],
        ];
    }

    public static function url(array $application): string
    {
        return $application['target'] instanceof Industry
            ? route('industry.show', $application['target'])
            : route('category.show', $application['target']);
    }

    /** Representative real photo for this application's target category/industry — for the 3x2 image-card grid. */
    public static function image(array $application): array
    {
        $category = $application['target'] instanceof Industry
            ? $application['target']->categories()[0]
            : $application['target'];

        return $category->image();
    }

    /** Applications whose label matches the query — for the search dropdown's "Applications" group. */
    public static function matching(string $query): array
    {
        $needle = strtolower($query);

        return array_values(array_filter(
            self::all(),
            fn (array $app) => str_contains(strtolower(strip_tags($app['label'])), $needle)
        ));
    }
}
