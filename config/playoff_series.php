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
            'series_length' => 7, // Best-of-7
        ],
        'quarter_finals' => [
            'series_length' => 7, // Best-of-7
        ],
        'semi_finals' => [ // conference finals
            'series_length' => 7, // Best-of-7
        ],
        'interconference_semi_finals' => [ // big 4
            'series_length' => 7, // Best-of-7
        ],
        'finals' => [
            'series_length' => 7, // Best-of-7
        ],
    ],

];
