<?php

// app/Helpers/UtilityHelper.php

if (!function_exists('format_date')) {
    function format_date($date)
    {
        return \Carbon\Carbon::parse($date)->format('Y-m-d');
    }
}

if (!function_exists('get_current_season_id')) {
    function get_current_season_id()
    {
        return \App\Models\Seasons::orderBy('id', 'desc')->value('id');
    }
}

if (!function_exists('get_previous_season_id')) {
    function get_previous_season_id()
    {
        return \App\Models\Seasons::orderBy('id', 'desc')->skip(1)->value('id');
    }
}
