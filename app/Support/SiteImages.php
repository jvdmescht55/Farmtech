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
        'dorper-ram' => ['alt' => 'Dorper ram in the Kalahari, South Africa', 'credit' => 'Attiewestraad', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Dorper_ram_in_the_Kalahari_-_South_Africa_.jpg'],
        'ear-tagging' => ['alt' => 'Farmer tagging a calf in a cattle crush', 'credit' => 'Loren Kerns', 'license' => 'CC BY 2.0', 'license_url' => 'https://creativecommons.org/licenses/by/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Ear_tagging_with_a_cattle_crush_2_(7277371954).jpg'],
        'eid-eartags' => ['alt' => 'Electronic ID and visual ear tags on a cow', 'credit' => 'Sandstein', 'license' => 'CC BY-SA 3.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Swiss_cow_ear_with_eartags.jpg'],
        'kraal' => ['alt' => 'Sheep and goats leaving the kraal, Northern Cape', 'credit' => 'Jklaasen', 'license' => 'CC BY-SA 3.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Goats_Sheep_leaving_kraal.jpg'],
        'nguni' => ['alt' => 'Nguni cattle grazing', 'credit' => 'Justinjerez', 'license' => 'CC BY-SA 3.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Nguni_cattle.jpg'],
        'boer-goat' => ['alt' => 'Boer goat with ear tag', 'credit' => 'Phin Hall', 'license' => 'CC BY-SA 2.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Boer_goat_with_ear_tag.jpg'],
        'lamb-tag' => ['alt' => 'Lamb with ear tag', 'credit' => 'Andrew Skowron / Otwarte Klatki', 'license' => 'CC BY 2.0', 'license_url' => 'https://creativecommons.org/licenses/by/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:White_lamb_with_ear_tag_looking_at_viewer_-_Fot._Andrew_Skowron_(32549210407).jpg'],
        'cattle-tag' => ['alt' => 'Black cow with a yellow ear tag', 'credit' => 'Julia Fiander / Unsplash', 'license' => 'Unsplash License', 'license_url' => 'https://unsplash.com/license', 'source' => 'https://unsplash.com'],
        'sheep-field' => ['alt' => 'Sheep grazing on a Gauteng farm', 'credit' => 'NJR ZA', 'license' => 'CC BY-SA 3.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Gauteng-Sheep_Farming-001.jpg'],
    ];

    public static function url(string $key, bool $small = false): string
    {
        return asset('images/site/'.$key.($small ? '-800' : '').'.webp');
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
