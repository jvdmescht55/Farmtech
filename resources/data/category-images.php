<?php

/**
 * Real photographs (Wikimedia Commons, CC BY-SA), not AI-generated —
 * used for category tiles and hero backdrops so the site isn't just bare
 * product photos and empty panels. Every entry needs `credit` because
 * CC BY-SA requires attribution; the storefront footer renders these.
 * license_url is the exact license version each photo was published under.
 */
return [
    'hero_fallback' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0c/Cattle_at_a_kraal_in_Karamoja_04.jpg/1920px-Cattle_at_a_kraal_in_Karamoja_04.jpg',
        'credit' => 'EO3669',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:Cattle_at_a_kraal_in_Karamoja_04.jpg',
    ],
    // Chosen to actually show the equipment, not just generic livestock/farm
    // scenery — see PROGRESS.md for what Wikimedia Commons genuinely has
    // free-licensed coverage of and what it doesn't (niche B2B product shots
    // like a "digital indicator with red LED" are not well represented
    // there; these are the closest real, correctly-licensed matches found).
    'scales' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/Electronic_Weighing_Scale.jpg/1920px-Electronic_Weighing_Scale.jpg',
        'credit' => 'Aliva Sahoo',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:Electronic_Weighing_Scale.jpg',
    ],
    'ultrasound' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/5/5d/USG_Pada_sapi_Bali.jpg',
        'credit' => 'Langgeng Anggitobumi',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:USG_Pada_sapi_Bali.jpg',
    ],
    'rfid' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/a/aa/Cattle_ear_tag_%281%29.jpg',
        'credit' => 'Eliran t',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:Cattle_ear_tag_(1).jpg',
    ],
    'accessories' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e7/Gauteng-Sheep_Farming-001.jpg/1920px-Gauteng-Sheep_Farming-001.jpg',
        'credit' => 'NJR ZA',
        'license' => 'CC BY-SA 3.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:Gauteng-Sheep_Farming-001.jpg',
    ],
    'fencing' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/ea/Electric_fence_energiser_%28AM_2017.92.25-1%29.jpg/1280px-Electric_fence_energiser_%28AM_2017.92.25-1%29.jpg',
        'credit' => 'Auckland Museum',
        'license' => 'CC BY 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:Electric_fence_energiser_(AM_2017.92.25-1).jpg',
    ],
    'solar_pumps' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/Solar_powered_borehole.jpg/1280px-Solar_powered_borehole.jpg',
        'credit' => 'Nzili',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:Solar_powered_borehole.jpg',
    ],
];
