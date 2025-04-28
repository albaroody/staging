<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Expiry for Staging Entries
    |--------------------------------------------------------------------------
    |
    | How many days should a staging entry remain valid before being considered
    | expired? Expired entries can optionally be cleaned up with an artisan command.
    |
    */
    'default_expiry_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Automatic Cleanup
    |--------------------------------------------------------------------------
    |
    | Should the package provide a scheduled command to automatically delete
    | expired staging entries after a threshold number of days?
    |
    */
    'cleanup_enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Cleanup Threshold Days
    |--------------------------------------------------------------------------
    |
    | After how many additional days beyond expiry should an expired staging entry
    | be permanently deleted?
    |
    */
    'cleanup_days_threshold' => 7,

    /*
    |--------------------------------------------------------------------------
    | Promotion Behavior
    |--------------------------------------------------------------------------
    |
    | How should the promotion process behave if something fails?
    | Options: 'fail' (throw error), 'skip' (log and continue others)
    |
    */
    'promotion_behavior' => 'fail',
];
