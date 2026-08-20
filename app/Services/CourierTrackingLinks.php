<?php

namespace App\Services;

/**
 * Maps the admin's free-text `courier_name` field (see Order::courier_name)
 * to that carrier's real, public tracking page — never a guessed deep-link
 * query parameter, since an unverified param would silently produce a
 * broken link. Courier Guy and DHL SA URLs were confirmed live; RAM and
 * DawnWing are best-effort from public listings and worth spot-checking
 * before relying on them (see PROGRESS.md).
 */
class CourierTrackingLinks
{
    private const KNOWN_COURIERS = [
        'courier guy' => 'https://thecourierguy.co.za/tracking/',
        'dhl' => 'https://www.dhl.com/za-en/home/tracking.html',
        'ram' => 'http://track.ramgroup.co.za/',
        'dawn wing' => 'http://www.dawnwing.co.za/business-tools/online-parcel-tracking/',
        'dawnwing' => 'http://www.dawnwing.co.za/business-tools/online-parcel-tracking/',
    ];

    /** Null when the courier isn't recognized — the tracking number still displays as plain text either way. */
    public static function urlFor(?string $courierName): ?string
    {
        if (! $courierName) {
            return null;
        }

        $needle = strtolower(trim($courierName));

        foreach (self::KNOWN_COURIERS as $key => $url) {
            if (str_contains($needle, $key)) {
                return $url;
            }
        }

        return null;
    }
}
