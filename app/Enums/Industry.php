<?php

namespace App\Enums;

/**
 * Groups ProductCategory into commercial/industrial sectors — Farmtech
 * expanded beyond livestock-only agritech into construction, industrial
 * logistics, and solar power hardware. Purely a display/navigation grouping;
 * ProductCategory::hsCodeHint()/vetting rules stay the source of truth for
 * anything AI-vetting or customs-facing.
 */
enum Industry: string
{
    case Agriculture = 'agriculture';
    case Construction = 'construction';
    case IndustrialLogistics = 'industrial_logistics';
    case SolarPower = 'solar_power';
    case Hunting = 'hunting';

    public function label(): string
    {
        return match ($this) {
            self::Agriculture => 'Livestock Management',
            self::Construction => 'Site & Construction',
            self::IndustrialLogistics => 'Fleet & Asset Logistics',
            self::SolarPower => 'Solar & Water Infrastructure',
            self::Hunting => 'Hunting Equipment',
        };
    }

    /** Short URL segment for the domain hub route (e.g. /livestock) — separate from the DB-backing value, which stays stable for stored data. */
    public function domainSlug(): string
    {
        return match ($this) {
            self::Agriculture => 'livestock',
            self::Construction => 'construction',
            self::IndustrialLogistics => 'logistics',
            self::SolarPower => 'solar',
            self::Hunting => 'hunting-equipment',
        };
    }

    /** Badge text used on the storefront (e.g. Top 5 Trending strip) — deliberately shorter than label(). */
    public function badgeLabel(): string
    {
        return match ($this) {
            self::Agriculture => 'Livestock Tech',
            self::Construction => 'Construction Tech',
            self::IndustrialLogistics => 'Logistics Tech',
            self::SolarPower => 'Solar Tech',
            self::Hunting => 'Hunting Gear',
        };
    }

    public function categories(): array
    {
        return array_values(array_filter(ProductCategory::cases(), fn (ProductCategory $c) => $c->industry() === $this));
    }

    /** Built from the real category list rather than separately-authored marketing copy that could drift out of sync with it. */
    public function description(): string
    {
        $labels = array_map(fn (ProductCategory $c) => $c->shortLabel(), $this->categories());

        return implode(', ', $labels).'.';
    }

    /**
     * Storefront-facing cases only — industries with at least one currently
     * storefront-visible product. Keeps nav/hero/footer from linking to a
     * dead-end hub page for a vertical that's been fully deactivated (e.g.
     * Hunting), without deleting the underlying enum case/products, which
     * stay intact for admin views and order history.
     */
    public static function activeCases(): array
    {
        static $activeCategoryValues = null;

        if ($activeCategoryValues === null) {
            $activeCategoryValues = \App\Models\Product::storefrontVisible()
                ->select('category')->distinct()->pluck('category')
                ->map(fn (ProductCategory $c) => $c->value)->all();
        }

        return array_values(array_filter(
            self::cases(),
            fn (Industry $i) => array_intersect(
                array_map(fn (ProductCategory $c) => $c->value, $i->categories()),
                $activeCategoryValues
            ) !== []
        ));
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
