<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quiniela 2026 Scoring System (stage-based)
    |--------------------------------------------------------------------------
    | exact_score   = predicted exact 90-min score
    | correct_winner = predicted the correct winner/draw (not exact score)
    */

    'tiers' => [
        'group' => [
            'exact_score'    => 5,
            'correct_winner' => 3,
        ],
        'round_of_32' => [
            'exact_score'    => 7,
            'correct_winner' => 5,
        ],
        'round_of_16' => [
            'exact_score'    => 7,
            'correct_winner' => 5,
        ],
        'quarter' => [
            'exact_score'    => 0,
            'correct_winner' => 0,
        ],
        'semi' => [
            'exact_score'    => 0,
            'correct_winner' => 0,
        ],
        'third_place' => [
            'exact_score'    => 0,
            'correct_winner' => 0,
        ],
        'final' => [
            'exact_score'    => 0,
            'correct_winner' => 0,
        ],
    ],

    // Fallback for any unknown stage
    'default' => [
        'exact_score'    => 5,
        'correct_winner' => 3,
    ],
];
