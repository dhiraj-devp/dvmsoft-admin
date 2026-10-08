<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Progress Score
    |--------------------------------------------------------------------------
    |
    | Transparent, weighted signals for staff progress overviews. Values are
    | percentages of the total score. Signals with no data are dropped and
    | remaining weights are redistributed so the result is never invented.
    |
    */

    'growth' => [
        'working_days' => [1, 2, 3, 4, 5],
        'calendar_weeks' => 4,
        'slipping_missed_days' => 2,
    ],

    'progress' => [
        'lookback_days' => 14,
        'minimum_updates' => 3,
        'low_score_threshold' => 70,
        'weights' => [
            'goal_completion' => 35,
            'on_time_completion' => 25,
            'daily_plan_completion' => 15,
            'daily_update_consistency' => 15,
            'manager_review_quality' => 10,
        ],
    ],

];
