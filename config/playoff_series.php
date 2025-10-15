<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Playoff Series Configuration
    |--------------------------------------------------------------------------
    | This defines each round name and its "best-of" format.
    | You can adjust values per round easily without touching code.
    */

    'rounds' => [
        'round_of_16' => [
            'series_length' => 3, // Best-of-3
        ],
        'quarter_finals' => [
            'series_length' => 3, // Best-of-5
        ],
        'semi_finals' => [
            'series_length' => 5, // Best-of-5
        ],
        'interconference_semi_finals' => [
            'series_length' => 5, // Best-of-7
        ],
        'finals' => [
            'series_length' => 7, // Best-of-7
        ],
    ],

];
