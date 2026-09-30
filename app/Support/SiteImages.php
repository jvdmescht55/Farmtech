<?php

namespace App\Support;

/**
 * Locally hosted photography (public/images/site, WebP) with the credit and
 * licence each one requires. Every CC BY / BY-SA photo must be credited
 * wherever it's shown — see the <x-photo-credits> component.
 */
class SiteImages
{
    public const IMAGES = [
        'windpomp-pink' => ['alt' => 'Windpump against a pink Karoo sunset, Northern Cape', 'credit' => 'South African Tourism', 'license' => 'CC BY 2.0', 'license_url' => 'https://creativecommons.org/licenses/by/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Sunset,_Karoo,_Northern_Cape,_South_Africa_(20355297928).jpg'],
        'windpomp-storm' => ['alt' => 'Windpump on golden veld under a storm sky, Nqweba, Eastern Cape', 'credit' => 'South African Tourism', 'license' => 'CC BY 2.0', 'license_url' => 'https://creativecommons.org/licenses/by/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Karoo_Landscape,_Nqweba,_Eastern_Cape,_South_Africa_(20324198338).jpg'],
        'karoo-mist' => ['alt' => 'Mist over the Karoo at first light, Eastern Cape', 'credit' => 'South African Tourism', 'license' => 'CC BY 2.0', 'license_url' => 'https://creativecommons.org/licenses/by/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Karoo,_Eastern_Cape,_South_Africa_(20323779729).jpg'],
        'dirt-road' => ['alt' => 'Gravel farm road at dusk, Northern Cape Karoo', 'credit' => 'South African Tourism', 'license' => 'CC BY 2.0', 'license_url' => 'https://creativecommons.org/licenses/by/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Sunset,_Karoo,_Northern_Cape,_South_Africa_(20541150895).jpg'],
        'golden-valley' => ['alt' => 'Golden light over a Karoo valley, Eastern Cape', 'credit' => 'South African Tourism', 'license' => 'CC BY 2.0', 'license_url' => 'https://creativecommons.org/licenses/by/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:The_Karoo,_Eastern_Cape,_South_Africa_(20484284626).jpg'],
        'koppie-dawn' => ['alt' => 'Karoo koppie silhouetted at sunrise', 'credit' => 'nairnbairn', 'license' => 'CC BY-SA 2.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Karoo_sunset9.jpg'],
        'tafelberg' => ['alt' => 'Flat-topped Karoo mountains over the veld', 'credit' => 'Bernard Dupont', 'license' => 'CC BY-SA 2.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Karoo_Landscape_(32611051802).jpg'],
        'merino-rams' => ['alt' => 'Merino rams in golden grass', 'credit' => 'Bernard Spragg', 'license' => 'Public domain', 'license_url' => 'https://creativecommons.org/publicdomain/mark/1.0/', 'source' => 'https://commons.wikimedia.org/wiki/File:Merino_sheep._(52908265984).jpg'],
        'sheep-portrait' => ['alt' => 'Sheep standing in a grass field', 'credit' => 'Bob Adams', 'license' => 'CC BY-SA 2.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Sheep_(18141426996).jpg'],
        'windmill-red' => ['alt' => 'Farm windmill silhouetted against a red sunset', 'credit' => "Charles O'Rear / US National Archives", 'license' => 'Public domain', 'license_url' => 'https://creativecommons.org/publicdomain/mark/1.0/', 'source' => 'https://commons.wikimedia.org/wiki/File:SEWARD_COUNTY_FARM_AND_WINDMILL_AT_SUNSET_-_NARA_-_547345.jpg'],
    ];

    public static function url(string $key, bool $small = false): string
    {
        return asset('images/site/'.$key.($small ? '-1200' : '').'.webp');
    }

    /** srcset for a full-bleed or large image. */
    public static function srcset(string $key): string
    {
        return self::url($key, true).' 1200w, '.self::url($key).' 2400w';
    }

    public static function alt(string $key): string
    {
        return self::IMAGES[$key]['alt'] ?? '';
    }

    /** @return array<string, array> */
    public static function only(array $keys): array
    {
        return array_intersect_key(self::IMAGES, array_flip($keys));
    }
}
