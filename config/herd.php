<?php

return [
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
