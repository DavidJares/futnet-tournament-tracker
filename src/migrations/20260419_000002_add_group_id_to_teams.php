<?php

declare(strict_types=1);

return [
    'version' => '20260419_000002_add_group_id_to_teams',
    'description' => 'Add optional group assignment to teams.',
    'statements' => [
        "ALTER TABLE teams
         ADD COLUMN group_id INT UNSIGNED NULL AFTER tournament_id",

        "ALTER TABLE teams
         ADD KEY idx_teams_group (group_id)",

        "ALTER TABLE teams
         ADD CONSTRAINT fk_teams_group
             FOREIGN KEY (group_id) REFERENCES groups (id)
             ON DELETE SET NULL",
    ],
];