<?php

return [
    /*
     * Herd Management software modules. Each is unlocked by a licence code
     * shipped with its device (licenses.module = key). Add future devices here.
     */
    // Placeholder slots shown as "coming soon" on the Herd Management hub.
    'upcoming' => [
        ['name' => 'Toestel 02', 'tagline' => 'Die volgende Farmtech-toestel. Dieselfde kuddeboek, nuwe data.', 'image' => 'koppie-dawn'],
        ['name' => 'Toestel 03', 'tagline' => 'Nog een op die tekenbord. Registreer belangstelling in die winkel.', 'image' => 'karoo-mist'],
    ],

    'modules' => [
        'rfid' => [
            'name' => 'RFID Scanner V1',
            'tagline' => 'Alles wat jou leser en skaal jou kan vertel — kuddeboek, gewigte, groei, vergelykings, sortering en katalogusse.',
            'route' => 'rfid.dashboard',
            'image' => 'merino-rams',
            'features' => ['Weegsessies & groei (g/dag)', 'Vergelyk groepe & sessies', 'Sorteer op gewig', 'SP / C / B stamboom', 'Veilingkatalogusse', 'Leser-sinch'],
        ],
    ],

    /*
     * Species biology used by the alerts engine and projections. Sources:
     * gestation ~147 d sheep / ~150 d goats / ~283 d cattle; lamb birth-weight
     * targets single 4.5–6 kg, twin 3.5–4.5 kg, >40% of lambs under 3 kg die;
     * litter mortality ~10% single / 15% twin / 33% triplet. Adjust freely.
     */
    'species' => [
        'sheep' => ['label' => 'Skaap', 'plural' => 'Skape', 'young' => 'lam', 'female' => 'Ooi', 'male' => 'Ram', 'gestation' => 147, 'low_birth_kg' => 3.0, 'wean_age' => 120, 'old_age_years' => 7, 'max_jump_pct' => 25],
        'goat' => ['label' => 'Bok', 'plural' => 'Bokke', 'young' => 'lammetjie', 'female' => 'Ooi', 'male' => 'Ram', 'gestation' => 150, 'low_birth_kg' => 2.5, 'wean_age' => 120, 'old_age_years' => 7, 'max_jump_pct' => 25],
        'cattle' => ['label' => 'Bees', 'plural' => 'Beeste', 'young' => 'kalf', 'female' => 'Koei', 'male' => 'Bul', 'gestation' => 283, 'low_birth_kg' => 25.0, 'wean_age' => 240, 'old_age_years' => 12, 'max_jump_pct' => 15],
    ],

    // Logbook event types. 'status' => the animal status this event sets.
    'event_types' => [
        'treatment' => ['label' => 'Behandeling', 'withdrawal' => true],
        'vaccination' => ['label' => 'Inenting', 'withdrawal' => true],
        'dosing' => ['label' => 'Doseer', 'withdrawal' => true],
        'mating' => ['label' => 'Paring / dekking', 'mate' => true],
        'pregnancy_scan' => ['label' => 'Dragtigheidskandering', 'result' => ['pregnant' => 'Dragtig', 'empty' => 'Leeg', 'twins' => 'Tweeling', 'triplets' => 'Drieling']],
        'birth' => ['label' => 'Geboorte (lam/kalf)', 'count' => true],
        'weaning' => ['label' => 'Speen'],
        'observation' => ['label' => 'Waarneming'],
        'sale' => ['label' => 'Verkoop', 'status' => 'sold'],
        'death' => ['label' => 'Vrek / dood', 'status' => 'dead'],
        'cull' => ['label' => 'Uitskot', 'status' => 'culled'],
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
     * EBV columns printed on the sale catalogue (value + accuracy %).
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
