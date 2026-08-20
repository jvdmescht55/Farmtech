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
    'smart_irrigation' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/c/c6/New_water-saving_irrigation_system_tested_at_NAVFAC_EXWC_%288099733341%29.jpg',
        'credit' => 'NAVFAC',
        'license' => 'CC BY 2.0',
        'license_url' => 'https://creativecommons.org/licenses/by/2.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:New_water-saving_irrigation_system_tested_at_NAVFAC_EXWC_(8099733341).jpg',
    ],
    'laser_levels' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/3/31/Construction_laser.jpg',
        'credit' => 'Jensens',
        'license' => 'Public Domain',
        'license_url' => 'https://creativecommons.org/publicdomain/mark/1.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:Construction_laser.jpg',
    ],
    'moisture_meters' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/7/79/Concrete_Moisture_Meter.jpg',
        'credit' => 'Wagner Meters',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:Concrete_Moisture_Meter.jpg',
    ],
    // Closest real match found — a ground-penetrating-radar subsurface
    // scanner in use, the same detection principle as a rebar cover meter.
    // Wikimedia Commons has no free-licensed photo of a rebar/cover meter
    // specifically; see PROGRESS.md.
    'rebar_detectors' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/32/Ground_Penetrating_Radar_%28GPR%29_is_a_popular_technique_that_is_used_in_archeology_to_identiy_areas_of_interest_and_potential_%28dcf1debf-6d1d-4557-a61f-a965091ed8a6%29.JPG/1280px-thumbnail.jpg',
        'credit' => 'NPS',
        'license' => 'Public Domain',
        'license_url' => 'https://creativecommons.org/publicdomain/mark/1.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:Ground_Penetrating_Radar_(GPR)_is_a_popular_technique_that_is_used_in_archeology_to_identiy_areas_of_interest_and_potential_(dcf1debf-6d1d-4557-a61f-a965091ed8a6).JPG',
    ],
    'theodolites' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e9/TS_by_The_sea.jpg/1280px-TS_by_The_sea.jpg',
        'credit' => 'Riccardo.salvini',
        'license' => 'CC BY 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:TS_by_The_sea.jpg',
    ],
    // Reuses the 'scales' photo — a real digital weighing indicator, just
    // not a crane/warehouse-specific shot; Commons has no free-licensed
    // photo of industrial platform/crane scale indicators. See PROGRESS.md.
    'platform_scales' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d6/Electronic_Weighing_Scale.jpg/1920px-Electronic_Weighing_Scale.jpg',
        'credit' => 'Aliva Sahoo',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:Electronic_Weighing_Scale.jpg',
    ],
    'fleet_trackers' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/3/33/DG-Tracker_Smile%2C_GPS_Vehicle_Tracker.jpg',
        'credit' => 'Nelso',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:DG-Tracker_Smile,_GPS_Vehicle_Tracker.jpg',
    ],
    'industrial_rfid' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/TransCore_RFID_reader_and_antenna.jpg/1280px-TransCore_RFID_reader_and_antenna.jpg',
        'credit' => 'z22',
        'license' => 'CC BY-SA 3.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:TransCore_RFID_reader_and_antenna.jpg',
    ],
    'mppt_controllers' => [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6d/SUOER_SOLAR_CHARGE_CONTROLLER.jpg/960px-SUOER_SOLAR_CHARGE_CONTROLLER.jpg',
        'credit' => 'Ranjithkumar Murugesan',
        'license' => 'CC0 1.0',
        'license_url' => 'https://creativecommons.org/publicdomain/zero/1.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:SUOER_SOLAR_CHARGE_CONTROLLER.jpg',
    ],
];
