<?php

declare(strict_types=1);

return [
    'version' => '20260422_000004_split_match_modes',
    'description' => 'Split tournament match mode into group stage and knockout modes.',
    'statements' => [
        "ALTER TABLE tournaments
         ADD COLUMN group_stage_mode ENUM('fixed_2_sets', 'best_of_3') NOT NULL DEFAULT 'fixed_2_sets' AFTER advancing_teams_count",

        "ALTER TABLE tournaments
         ADD COLUMN knockout_mode ENUM('fixed_2_sets', 'best_of_3') NOT NULL DEFAULT 'best_of_3' AFTER group_stage_mode",

        "UPDATE tournaments
         SET group_stage_mode = CASE
             WHEN match_mode IN ('fixed_2_sets', 'best_of_3') THEN match_mode
             ELSE 'fixed_2_sets'
         END",
    ],
];
