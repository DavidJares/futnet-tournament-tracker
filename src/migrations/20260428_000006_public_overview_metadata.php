<?php

declare(strict_types=1);

return [
    'version' => '20260428_000006_public_overview_metadata',
    'description' => 'Add public overview metadata fields to tournaments.',
    'statements' => [
        "ALTER TABLE tournaments
         ADD COLUMN public_title_override VARCHAR(200) NULL AFTER rotation_interval_seconds",

        "ALTER TABLE tournaments
         ADD COLUMN public_description TEXT NULL AFTER public_title_override",

        "ALTER TABLE tournaments
         ADD COLUMN public_logo_path VARCHAR(255) NULL AFTER public_description",

        "ALTER TABLE tournaments
         ADD COLUMN public_map_url VARCHAR(500) NULL AFTER public_logo_path",
    ],
];
