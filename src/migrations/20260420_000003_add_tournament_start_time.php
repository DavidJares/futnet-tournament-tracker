<?php

declare(strict_types=1);

return [
    'version' => '20260420_000003_add_tournament_start_time',
    'description' => 'Add optional tournament start time for scheduling.',
    'statements' => [
        "ALTER TABLE tournaments
         ADD COLUMN start_time TIME NULL AFTER event_date",
    ],
];
