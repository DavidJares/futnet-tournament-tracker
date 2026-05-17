<?php

declare(strict_types=1);

return [
    'version' => '20260517_000008_public_view_theme',
    'description' => 'Add public view theme setting for tournaments.',
    'statements' => [
        "ALTER TABLE tournaments
         ADD COLUMN public_view_theme VARCHAR(20) NOT NULL DEFAULT 'dark' AFTER public_view_enabled",
    ],
];
