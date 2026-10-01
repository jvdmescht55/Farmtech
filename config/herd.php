<?php

return [
    /*
     * Herd Management software modules. Each is unlocked by a licence code
     * shipped with its device (licenses.module = key). Add future devices here.
     */
    /*
     * Herd Management sections — one per device family. A licence code that
     * ships with the device unlocks its section (licenses.module = key).
     * 'open' => true means any signed-in farmer can use it (custom devices).
     */
    'modules' => [
        'rfid' => [
            'name' => 'KraalTrac Pro',
            'kind' => 'Handheld EID reader & weigh logger',
            'tagline' => 'Scan the tag, punch in the weight — your herd book, growth figures and auction books build themselves.',
            'route' => 'rfid.dashboard',
            'image' => 'merino-rams',
            'device_kind' => 'handheld',
            'features' => ['Weigh sessions & daily gain', 'Compare rams, groups, seasons', 'Sort by weight', 'SP / C / B pedigree tiers', 'Auction books', 'Health & breeding records'],
        ],
        'watch' => [
            'name' => 'KraalTrac Watch',
            'kind' => 'Gate & water-point counter',
            'tagline' => 'Mount it at the trough or the gate. It counts who came through and shouts when an animal hasn\'t been to drink.',
            'route' => 'watch.dashboard',
            'image' => 'windpomp-storm',
            'device_kind' => 'watch',
            'features' => ['Head count per day', 'Missed-drink alerts', 'Visits per hour', 'Every water point & gate', 'Links to the herd book'],
        ],
        'custom' => [
            'name' => 'Custom devices',
            'kind' => 'Your own sensors & gadgets',
            'tagline' => 'Built your own tank gauge, rain meter or cold-room sensor? Plug it in here — you choose what it measures and when to get worried.',
            'route' => 'custom.index',
            'image' => 'karoo-mist',
            'device_kind' => 'custom',
            'open' => true,
            'features' => ['Any reading you like', 'Your own limits & alerts', 'Charts per device', 'Same simple device API'],
        ],
    ],

    /*
     * Species biology used by the alerts engine and projections. Sources:
     * gestation ~147 d sheep / ~150 d goats / ~283 d cattle; lamb birth-weight
     * targets single 4.5–6 kg, twin 3.5–4.5 kg, >40% of lambs under 3 kg die;
     * litter mortality ~10% single / 15% twin / 33% triplet. Adjust freely.
     */
    'species' => [
        'sheep' => ['label' => 'Sheep', 'plural' => 'Sheep', 'young' => 'lamb', 'female' => 'Ewe', 'male' => 'Ram', 'gestation' => 147, 'low_birth_kg' => 3.0, 'wean_age' => 120, 'old_age_years' => 7, 'max_jump_pct' => 25],
        'goat' => ['label' => 'Goat', 'plural' => 'Goats', 'young' => 'kid', 'female' => 'Doe', 'male' => 'Buck', 'gestation' => 150, 'low_birth_kg' => 2.5, 'wean_age' => 120, 'old_age_years' => 7, 'max_jump_pct' => 25],
        'cattle' => ['label' => 'Cattle', 'plural' => 'Cattle', 'young' => 'calf', 'female' => 'Cow', 'male' => 'Bull', 'gestation' => 283, 'low_birth_kg' => 25.0, 'wean_age' => 240, 'old_age_years' => 12, 'max_jump_pct' => 15],
    ],

    // Logbook event types. 'status' => the animal status this event sets.
    'event_types' => [
        'treatment' => ['label' => 'Treatment', 'withdrawal' => true],
        'vaccination' => ['label' => 'Vaccination', 'withdrawal' => true],
        'dosing' => ['label' => 'Dosing', 'withdrawal' => true],
        'mating' => ['label' => 'Mating', 'mate' => true],
        'pregnancy_scan' => ['label' => 'Pregnancy scan', 'result' => ['pregnant' => 'Pregnant', 'empty' => 'Empty', 'twins' => 'Twins', 'triplets' => 'Triplets']],
        'birth' => ['label' => 'Birth', 'count' => true],
        'weaning' => ['label' => 'Weaning'],
        'observation' => ['label' => 'Note'],
        'sale' => ['label' => 'Sold', 'status' => 'sold'],
        'death' => ['label' => 'Died', 'status' => 'dead'],
        'cull' => ['label' => 'Culled', 'status' => 'culled'],
    ],

    /*
     * Genetic tier ladder, lowest to highest. An offspring enters one step
     * above the weaker of its two parents, capped at the top tier — so a
     * commercial (CC) ewe x stud ram gives B, B x stud gives C, C x stud
     * gives SP. Matches Logix/SA Stud Book open-register grading-up, e.g.
     * Lot 67B: dam's dam CC 230734 -> dam B -> 67B is C.
     */
    'tiers' => ['CC', 'B', 'C', 'SP'],

    'tier_labels' => [
        'SP' => 'Stud (SP)',
        'C' => 'Grade C',
        'B' => 'Grade B',
        'CC' => 'Commercial',
    ],

    // ID prefixes that mark an animal as commercial/foundation stock.
    'commercial_prefixes' => ['CC'],

    /*
     * EBV columns printed on the auction book (value + accuracy %).
     * Keys are what CSV imports and the animal form use.
     */
    'ebvs' => [
        'wean_dir' => ['label' => 'Wean Dir', 'af' => 'Speen Dir'],
        'wean_mat' => ['label' => 'Wean Mat', 'af' => 'Speen Mat'],
        'pw_dir' => ['label' => 'Post Wean Dir', 'af' => 'Naspeen Dir'],
        'nlw' => ['label' => 'NLW%', 'af' => 'NLW%'],
        'rev' => ['label' => 'REV', 'af' => 'REV'],
        'vk' => ['label' => 'VK', 'af' => 'VK'],
    ],

    // Dam lamb record / Ooi lamrekord columns.
    'dam_record' => [
        'first' => '1st',
        'sp' => 'S.P.',
        'tl' => 'TL',
        'lb' => 'LB',
        'lw' => 'LW',
        'mli' => 'MLI',
        'epi' => 'EPI',
    ],

    'birth_types' => ['01' => 'Single', '02' => 'Twin', '03' => 'Triplet', '04' => 'Quad'],
];
