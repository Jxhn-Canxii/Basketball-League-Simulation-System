<?php

return [

    // 8-team single elimination with play-ins
    'single_elim_8_with_playins' => [
        'type' => 1,
        'start' => 8,
        'round_sequence' => [
            'play_ins_elims_round_1',
            'play_ins_finals',
            'quarter_finals',
            'semi_finals',
            'finals',
        ],
    ],

    // 16-team single elimination with play-ins
    'single_elim_16_with_playins' => [
        'type' => 1,
        'start' => 16,
        'round_sequence' => [
            'play_ins_elims_round_1',
            'play_ins_elims_round_2',
            'play_ins_finals',
            'round_of_16',
            'quarter_finals',
            'semi_finals',
            'interconference_semi_finals',
            'finals',
        ],
    ],

    // Pure 8-team single elimination (no play-ins)
    'single_elim_8' => [
        'type' => 1,
        'start' => 8,
        'round_sequence' => [
            'quarter_finals',
            'semi_finals',
            'finals',
        ],
    ],

    // Pure 16-team single elimination (no play-ins)
    'single_elim_16' => [
        'type' => 1,
        'start' => 16,
        'round_sequence' => [
            'round_of_16',
            'quarter_finals',
            'semi_finals',
            'finals',
        ],
    ],

];
